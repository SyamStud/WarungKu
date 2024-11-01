<?php

namespace App\Exports;

use App\Models\Store;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class StoreApplicationExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
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
        return Store::where('status', 'pending')->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with('users')
            ->get()
            ->map(function ($application) {
                return [
                    'name' => $application->name,
                    'address' => $application->address,
                    'phone' => $application->phone,
                    'email' => $application->email,
                    'website' => $application->website,
                    'owner' => $application->users->filter(function ($user) {
                        return $user->hasRole('admin');
                    })->sortBy('created_at')->first()->email,
                    'created_at' => $application->created_at->format('d F Y H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nama Toko',
            'Alamat',
            'Telepon',
            'Email',
            'Website',
            'Pemilik',
            'Tanggal Pengajuan',
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
