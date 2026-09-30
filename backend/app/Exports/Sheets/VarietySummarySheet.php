<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VarietySummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'By Variety';
    }

    public function collection(): Collection
    {
        return $this->data['by_variety'];
    }

    public function headings(): array
    {
        return ['Variety', 'Classification', 'Records', 'Avg Yield (t/ha)', 'Max Yield (t/ha)', 'Avg Predicted'];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $range = 'A1:F1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("A2:F{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => 'top', 'wrapText' => true],
            ]);
        }

        $sheet->setAutoFilter($range);
        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(16);

        return [];
    }
}