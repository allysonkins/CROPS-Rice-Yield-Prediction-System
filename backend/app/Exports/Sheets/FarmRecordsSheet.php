<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FarmRecordsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Farm Records';
    }

    public function collection(): Collection
    {
        return $this->data['farm_records'];
    }

    public function headings(): array
    {
        return [
            'Farm', 'Barangay', 'Year', 'Season', 'Variety', 'Seeding Method',
            'Fertilizer (kg/ha)', 'Historical Yield', 'Predicted Yield',
            'Predicted Class', 'Actual Yield', 'Status',
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $range = 'A1:L1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("A2:L{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => 'top', 'wrapText' => true],
            ]);
        }

        $sheet->setAutoFilter($range);
        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(16);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->getColumnDimension('K')->setWidth(14);
        $sheet->getColumnDimension('L')->setWidth(14);

        return [];
    }
}