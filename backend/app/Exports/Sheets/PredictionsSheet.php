<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PredictionsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Predictions';
    }

    public function collection(): Collection
    {
        return $this->data['predictions'];
    }

    public function headings(): array
    {
        return [
            'Farm', 'Variety', 'Season', 'Year',
            'Predicted Yield (t/ha)', 'Class', 'Confidence', 'Model', 'Generated',
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $range = 'A1:I1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("A2:I{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => 'top'],
            ]);
        }

        $sheet->setAutoFilter($range);
        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(20);

        return [];
    }
}