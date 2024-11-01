<?php

namespace App\Exports;

use App\Models\DebtPaymentItem;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class DebtPaymentsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    protected $startDate;
    protected $endDate;

    // Terima tanggal dari controller
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate . ' 00:00:00';
        $this->endDate = $endDate . ' 23:59:59';
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return DebtPaymentItem::where('store_id', Auth::user()->store->id)->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get()
            ->map(function ($debtPayment) {
                return [
                    'name' => $debtPayment->debtItem->debt->customer->name,
                    'product' => $debtPayment->debtItem->transactionItem ? $debtPayment->debtItem->transactionItem->product->name : 'TAX',
                    'total_debt' => $debtPayment->debtItem->total_amount,
                    'total_payment' => $debtPayment->amount,
                    'remaining_debt' => $debtPayment->remaining_debt,
                    'created_at' => $debtPayment->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nama Pelanggan',
            'Produk',
            'Total Hutang',
            'Total Pembayaran',
            'Sisa Hutang',
            'Tanggal Pembayaran',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Menentukan gaya untuk header
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'], // Warna font putih
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '808080'], // Warna abu-abu
            ],
        ]);

        // Menyesuaikan lebar kolom berdasarkan isi data
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    public function columnFormats(): array
    {
        return [
            'A' => '@', // Format teks untuk kolom A
            'B' => '@', // Format teks untuk kolom B
            'C' => '@', // Format teks untuk kolom C
        ];
    }
}
