<?php

namespace App\Exports;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ProductVariantsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return ProductVariant::where('store_id', Auth::user()->store->id)->with('product.category:id,name', 'unit:id,name')
            ->get()
            ->map(function ($variant) {
                return [
                    'sku' => $variant->product->sku,
                    'name' => $variant->product->name,
                    'variants' => $variant->quantity . " " . $variant->unit->name,
                    'category_name' => $variant->product->category->name ?? 'N/A',
                    'status' => $variant->status == 'active' ? 'Aktif' : 'Tidak Aktif',
                    'created_at' => $variant->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Name',
            'Variasi',
            'Kategori',
            'Status',
            'Tanggal Dibuat',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Menentukan gaya untuk header
        $sheet->getStyle('A1:G1')->applyFromArray([
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
        foreach (range('A', 'G') as $column) {
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
