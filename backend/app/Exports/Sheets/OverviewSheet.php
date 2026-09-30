<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverviewSheet implements FromArray, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Overview';
    }

    public function array(): array
    {
        $o = $this->data['overview'];

        return [
            ['CROPS — Rice Yield Prediction Report'],
            ['Santiago City Agriculture Office'],
            ['Generated', $this->data['generated_at']->format('F d, Y h:i A')],
            [],
            ['FARMER METRICS'],
            ['Total Farmers',           $o['total_farmers']],
            ['Verified Farmers',        $o['verified_farmers']],
            ['Pending Verification',    $o['pending_farmers']],
            [],
            ['LAND METRICS'],
            ['Total Farms',             $o['total_farms']],
            ['Total Area (ha)',         $o['total_area_ha']],
            ['Farm Records',            $o['total_farm_records']],
            ['  Vegetative (growing)',  $o['vegetative_records']],
            ['  Harvested',             $o['harvested_records']],
            [],
            ['VARIETY METRICS'],
            ['Rice Varieties in DB',    $o['total_varieties']],
            [],
            ['PREDICTION METRICS'],
            ['Total Predictions',       $o['total_predictions']],
            [],
            ['PREDICTION CLASS DISTRIBUTION'],
            ['High',    $o['yield_class_counts']['High']   ?? 0],
            ['Medium',  $o['yield_class_counts']['Medium'] ?? 0],
            ['Low',     $o['yield_class_counts']['Low']    ?? 0],
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        // Title block
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF165B33']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => 'left'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Section header rows
        $sectionRows = [5, 10, 17, 20, 23];
        foreach ($sectionRows as $r) {
            $sheet->mergeCells("A{$r}:B{$r}");
            $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
                'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'indent' => 1],
            ]);
            $sheet->getRowDimension($r)->setRowHeight(20);
        }

        // Soft value alignment for KPI rows
        $kpiRows = [6, 7, 8, 11, 12, 13, 14, 15, 18, 21, 24, 25, 26];
        foreach ($kpiRows as $r) {
            $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal('left');
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(28);

        return [];
    }
}