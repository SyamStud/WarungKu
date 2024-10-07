<?php

namespace App\Http\Controllers\Pos;

use Inertia\Inertia;
use App\Models\Customer;
use App\Models\DebtItem;
use Mike42\Escpos\Printer;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use App\Models\DebtPaymentItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Mike42\Escpos\CapabilityProfile;
use Illuminate\Support\Facades\Validator;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class DebtPaymentController extends Controller
{
    public function index(Request $request)
    {
        if ($this->isMobileDevice($request)) {
            return Inertia::render('Pos/DebtPaymentsMobile');
        }

        return Inertia::render('Pos/DebtPayments');
    }

    private function isMobileDevice(Request $request)
    {
        $userAgent = strtolower($request->header('User-Agent'));

        // Deteksi perangkat mobile berdasarkan user-agent string
        return preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|opera mini|opera mobi|iemobile|windows phone|palm|webos)/i', $userAgent);
    }

    public function storePayment(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'payment_code' => 'required',
                'payment_amount' => 'required',
                'payment_method' => 'required',
                'customer_id' => 'required',
            ]
        );

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first()
            ]);
        }

        $customer = Customer::find($request->customer_id);

        $payment = new DebtPayment();
        $payment->payment_code = $request->payment_code;
        $payment->customer_id = $request->customer_id;
        $payment->amount = $request->payment_amount;
        $payment->paid_at = now();
        $payment->payment_method = $request->payment_method;
        $payment->user_id = Auth::user()->id;
        $payment->save();

        $debtItems = DebtItem::where('customer_id', $request->customer_id)
            ->where('status', '!=', 'paid')
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingDebt = $customer->total_debt - $payment->amount;

        // $this->printDebtReceipt($request->payment_code, $debtItems, $request->payment_amount, $remainingDebt, $customer->name);


        $remainingPayment = $payment->amount;

        foreach ($debtItems as $debtItem) {
            if ($remainingPayment <= 0) {
                break;
            }

            $paymentForThisItem = min($remainingPayment, $debtItem->remaining_amount);

            $debtItem->paid_amount += $paymentForThisItem;
            $debtItem->remaining_amount -= $paymentForThisItem;
            $remainingPayment -= $paymentForThisItem;

            if ($debtItem->remaining_amount <= 0) {
                $debtItem->status = 'paid';
                $debtItem->settled_at = now();
            } elseif ($debtItem->paid_amount > 0) {
                $debtItem->status = 'partial';
            }

            $debtItem->last_payment_at = now();
            $debtItem->save();

            // Create a new DebtPaymentItem to track this payment
            $paymentItem = new DebtPaymentItem();
            $paymentItem->debt_payment_id = $payment->id;
            $paymentItem->debt_item_id = $debtItem->id;
            $paymentItem->amount = $paymentForThisItem;
            $paymentItem->remaining_debt = $debtItem->remaining_amount;
            $paymentItem->save();
        }


        $customer->total_debt = $customer->total_debt - $payment->amount;
        $customer->save();

        // If there's any remaining payment, we might want to handle it
        // (e.g., create a credit for the customer or refund)
        if ($remainingPayment > 0) {
            // Handle excess payment...
        }



        return response()->json([
            'status' => 'success',
            'message' => 'Payment has been successfully added.'
        ]);
    }

    public function printDebtReceipt($paymentCode, $debtItems, $paymentAmount, $remainingDebt, $customerName)
    {
        // Menghubungkan ke printer dengan nama printer
        $profile = CapabilityProfile::load('simple');
        $connector = new WindowsPrintConnector("TP806");
        $printer = new Printer($connector, $profile);

        // Nama dan informasi toko
        $tokoName = "WARUNG AFIQ";
        $tokoAddress = "Jl. Raya No.123, Jakarta\n";

        // Informasi struk
        $kasir = "Kasir: Budi";
        $tanggal = date("d-m-Y H:i:s");
        $nomorStruk = "Nomor: " . $paymentCode;

        // Pengaturan format
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->text($tokoName . "\n");
        $printer->setTextSize(1, 1);
        $printer->text($tokoAddress . "\n\n");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Nama Pelanggan: " . $customerName . "\n");
        $printer->text($kasir . "\n");
        $printer->text('Tanggal: ' . $tanggal . "\n");
        $printer->text($nomorStruk . "\n");
        $printer->text(str_repeat("-", 47) . "\n"); // Garis pemisah lebih lebar

        // Header kolom barang yang dihutang
        $printer->setEmphasis(true);
        $printer->text(str_pad("Item Hutang", 30) . str_pad("Hutang Tersisa", 16, ' ', STR_PAD_LEFT) . " \n");
        // $printer->text(str_pad("Barang", 30) . str_pad("Hutang", 16, ' ', STR_PAD_LEFT) . " \n"); // Header dengan margin kanan
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 47) . "\n"); // Garis pemisah lebih lebar

        $totalDebt = 0;
        foreach ($debtItems as $item) {
            // Nama barang dicetak di baris pertama
            $printer->text($item->transactionItem->product->name . "\n");

            // Detail barang: Qty x Harga, Total Hutang
            $qty = "  " . $item->transactionItem->quantity . " pcs x " . number_format($item->transactionItem->price, 0, ',', '.'); // Indentasi tambahan
            $subtotal = number_format($item->remaining_amount, 0, ',', '.'); // Total hutang untuk barang

            // Menampilkan detail hutang dengan margin kanan
            $printer->text(
                str_pad($qty, 30) . // Format Qty pcs x Harga dengan indentasi
                    str_pad($subtotal, 16, ' ', STR_PAD_LEFT) . " \n" // Subtotal dengan margin kanan
            );

            $totalDebt += $item->remaining_amount;
        }

        $printer->text(str_repeat("-", 47) . "\n");

        // Total Hutang Awal
        $printer->setEmphasis(true);
        $printer->text(str_pad("Total Hutang", 30) . str_pad(number_format($totalDebt, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 47) . "\n\n");

        // Pembayaran yang Dilakukan
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(true);
        $printer->text(str_pad("Pembayaran", 30) . str_pad(number_format($paymentAmount, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->setEmphasis(false);

        // Sisa Hutang
        $printer->setEmphasis(true);
        $printer->text(str_pad("Sisa Hutang", 30) . str_pad(number_format($remainingDebt, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 47) . "\n\n");

        // Ucapan terima kasih dan keterangan
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Terima Kasih Atas Pembayaran Hutang Anda!\n");
        $printer->text("Pastikan sisa hutang dibayarkan sesuai\ndengan kesepakatan.\n\n");
        $printer->feed(3); // Jarak sebelum potong kertas
        $printer->cut();
        $printer->close();
    }
}
