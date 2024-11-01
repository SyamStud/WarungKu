<?php

namespace App\Exports;

use App\Models\Debt;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class DebtsExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
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
        return Debt::where('store_id', Auth::user()->store->id)->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get()
            ->map(function ($debt) {
                return [
                    'name' => $debt->customer->name,
                    'phone' => $debt->customer->phone,
                    'address' => $debt->customer->address,
                    'total_amount' => $debt->total_amount,
                    'paid_amount' => $debt->paid_amount,
                    'remaining_amount' => $debt->remaining_amount,
                    'status' => $debt->status,
                    'last_payment_at' => $debt->last_payment_at,
                    'settled_at' => $debt->settled_at,
                    'created_at' => $debt->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Telepon',
            'Alamat',
            'Total Hutang',
            'Total Bayar',
            'Sisa Hutang',
            'Status',
            'Terakhir Bayar',
            'Tanggal Lunas',
            'Dibuat',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Menentukan gaya untuk header
        $sheet->getStyle('A1:J1')->applyFromArray([
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
        foreach (range('A', 'J') as $column) {
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
