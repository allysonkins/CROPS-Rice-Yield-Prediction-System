<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LowFarmsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Needs Attention';
    }

    public function collection(): Collection
    {
        return $this->data['low_farms'];
    }

    public function headings(): array
    {
        return ['#', 'Farm', 'Barangay', 'Farmer', 'Variety', 'Yield', 'Class'];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $range = 'A1:G1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFB91C1C']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("A2:G{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => 'top'],
            ]);
        }

        $sheet->setAutoFilter($range);
        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(24);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(12);

        return [];
    }
}