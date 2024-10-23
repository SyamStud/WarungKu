<?php

namespace App\Exports;

use App\Models\Restock;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class RestocksExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Restock::where('status', '!=', 'sold-out')
        ->with('productVariant.product', 'productVariant.unit:id,name')
            ->get()
            ->map(function ($restock) {
                return [
                    'name' => $restock->productVariant->product->name,
                    'variants' => $restock->productVariant->quantity . " " . $restock->productVariant->unit->name,
                    'quantity' => $restock->quantity ,
                    'difference' => $restock->difference,
                    'cost' => $restock->cost,
                    'status' => $restock->status,
                    'created_at' => $restock->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Variasi',
            'Kuantitas',
            'Selisih',
            'Harga Beli',
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
