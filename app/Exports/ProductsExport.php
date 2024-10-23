<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use App\Models\Product; // Pastikan untuk mengganti dengan model yang sesuai

class ProductsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    public function collection()
    {
        return Product::with('category:id,name')
            ->select('sku', 'name', 'category_id', 'created_at', 'updated_at')
            ->get()
            ->map(function ($product) {
                return [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category_name' => $product->category->name ?? 'N/A',
                    'created_at' => $product->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Name',
            'Kategori',
            'Tanggal Dibuat',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Menentukan gaya untuk header
        $sheet->getStyle('A1:E1')->applyFromArray([
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
        foreach (range('A', 'E') as $column) {
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
