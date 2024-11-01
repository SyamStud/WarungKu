<?php

namespace App\Exports;

use App\Models\DebtItem;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class DebtItemsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
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
        return DebtItem::where('store_id', Auth::user()->store->id)->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get()
            ->map(function ($item) {
                return [
                    'transaction_code' => $item->transactionItem ? $item->transactionItem->transaction->transaction_code : DebtItem::where('id', '<', $item->id)->orderBy('id', 'desc')->first()->transactionItem->transaction->transaction_code,
                    'name' => $item->debt->customer->name,
                    'product_name' => $item->transactionItem ? $item->transactionItem->product->name : 'TAX',
                    'quantity' => $item->transactionItem ? $item->transactionItem->quantity : 1,
                    'total_amount' => $item->total_amount,
                    'paid_amount' => $item->paid_amount,
                    'remaining_amount' => $item->remaining_amount,
                    'status' => $item->status,
                    'last_payment_at' => $item->last_payment_at,
                    'settled_at' => $item->settled_at,
                    'created_at' => $item->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kode Transaksi',
            'Nama Pelanggan',
            'Nama Produk',
            'Jumlah',
            'Total Harga',
            'Total Bayar',
            'Sisa Hutang',
            'Status',
            'Terakhir Bayar',
            'Tanggal Lunas',
            'Tanggal Dibuat',
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
