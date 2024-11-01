<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;


class TransactionsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
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
        return Transaction::where('store_id', Auth::user()->store->id)->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get()
            ->map(function ($stock) {
                return [
                    'transaction_code' => $stock->transaction_code,
                    'total_price' => $stock->total_price,
                    'discount' => $stock->discount,
                    'tax' => $stock->tax,
                    'grand_total' => $stock->grand_total,
                    'total_payment' => $stock->total_payment,
                    'total_change' => $stock->total_change,
                    'total_profit' => $stock->total_profit,
                    'payment_method' => $stock->payment_method,
                    'cashier' => $stock->user->name,
                    'created_at' => $stock->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kode Transaksi',
            'Total Harga',
            'Diskon',
            'Pajak',
            'Total Harga Akhir',
            'Total Pembayaran',
            'Kembalian',
            'Total Keuntungan',
            'Metode Pembayaran',
            'Kasir',
            'Tanggal Transaksi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Menentukan gaya untuk header
        $sheet->getStyle('A1:K1')->applyFromArray([
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
        foreach (range('A', 'K') as $column) {
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
