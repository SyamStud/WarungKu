<?php

namespace App\Exports;

use App\Models\StockMovement;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class StockMovementsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
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
        return StockMovement::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with('productVariant.product', 'productVariant.unit:id,name')
            ->get()
            ->map(function ($stock) {
                return [
                    'name' => $stock->productVariant->product->name,
                    'variants' => $stock->productVariant->quantity . " " . $stock->productVariant->unit->name,
                    'quantity' => $stock->quantity,
                    'reference' => $stock->reference,
                    'type' => $stock->type,
                    'created_at' => $stock->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Variasi',
            'Kuantitas',
            'Keterengan',
            'Tipe',
            'Tanggal Dibuat',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Menentukan gaya untuk header
        $sheet->getStyle('A1:H1')->applyFromArray([
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
        foreach (range('A', 'H') as $column) {
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
