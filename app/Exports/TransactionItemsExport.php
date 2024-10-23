<?php

namespace App\Exports;

use App\Models\TransactionItem;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;


class TransactionItemsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
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
        return TransactionItem::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with(['productVariant.product', 'restock' ,'productVariant.unit:id,name', 'transaction'])
            ->get()
            ->map(function ($stock) {
                return [
                    'transaction_code' => $stock->transaction->transaction_code,
                    'name' => $stock->productVariant->product->name,
                    'variants' => $stock->productVariant->quantity . " " . $stock->productVariant->unit->name,
                    'quantity' => $stock->quantity,
                    'price' => $stock->price,
                    'discount' => $stock->discount,
                    'discounted_price' => $stock->discounted_price,
                    'total_price' => $stock->total_price,
                    'discounted_total_price' => $stock->discounted_total_price,
                    'stock' => $stock->restock->id,
                    'profit' => $stock->profit,
                    'created_at' => $stock->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kode Transaksi',
            'Nama Produk',
            'Variasi',
            'Jumlah',
            'Harga',
            'Diskon',
            'Harga Setelah Diskon',
            'Total Harga',
            'Total Harga Setelah Diskon',
            'ID Stok',
            'Keuntungan',
            'Tanggal Transaksi',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Menentukan gaya untuk header
        $sheet->getStyle('A1:L1')->applyFromArray([
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
        foreach (range('A', 'L') as $column) {
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
