<?php

namespace App\Exports;

use App\Models\Commission;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping; 
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CommissionsExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithCustomCsvSettings
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }


    public function query()
    {
        return $this->query->with(['serviceCommissions', 'bookingCommissions']);
    }


    public function map($commission): array
    {
        return [
            $commission->id,
            $commission->commission_type == 'fixed' ? 'ثابت' : 'نسبة مئوية',
            $commission->calculation_level == 'service' ? 'خدمة' : 'حجز',
            $commission->description ?? '-',
            $commission->serviceCommissions->count(),
            $commission->bookingCommissions->count(),
            $commission->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'الرقم',
            'نوع العمولة',
            'مستوى الحساب',
            'الوصف',
            'عدد الخدمات المرتبطة',
            'عدد الحجوزات المرتبطة',
            'تاريخ الإنشاء',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 15,
            'C' => 15,
            'D' => 30,
            'E' => 20,
            'F' => 20,
            'G' => 20,
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'use_bom' => true,
            'delimiter' => ',',
            'enclosure' => '"',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'name' => 'Cairo',
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F766E']
                ],
            ],
            'A:G' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'font' => [
                    'name' => 'Cairo',
                    'size' => 11,
                ],
            ],
        ];
    }
}