<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverviewSheet implements FromArray, WithTitle, WithStyles
{
    protected array $data;
    protected array $sections;
    protected array $rows         = [];
    protected array $sectionRows  = [];

    public function __construct(array $data, array $sections = [])
    {
        $this->data     = $data;
        $this->sections = $sections;
        $this->buildRows();
    }

    public function title(): string
    {
        return 'Overview';
    }

    public function array(): array
    {
        return $this->rows;
    }

    protected function buildRows(): void
    {
        $o = $this->data['overview'];

        $includeOverview = empty($this->sections) || in_array('overview', $this->sections, true);
        $includeClass    = empty($this->sections) || in_array('class_distribution', $this->sections, true);

        $rows = [
            ['CROPS — Rice Yield Prediction Report'],
            ['Santiago City Agriculture Office'],
            ['Generated', $this->data['generated_at']->format('F d, Y h:i A')],
            [],
        ];

        if ($includeOverview) {
            $this->sectionRows[] = count($rows) + 1;
            $rows[] = ['FARMER METRICS'];
            $rows[] = ['Total Farmers',        $o['total_farmers']];
            $rows[] = ['Verified Farmers',     $o['verified_farmers']];
            $rows[] = ['Pending Verification', $o['pending_farmers']];
            $rows[] = [];

            $this->sectionRows[] = count($rows) + 1;
            $rows[] = ['LAND METRICS'];
            $rows[] = ['Total Farms',            $o['total_farms']];
            $rows[] = ['Total Area (ha)',        $o['total_area_ha']];
            $rows[] = ['Farm Records',           $o['total_farm_records']];
            $rows[] = ['  Vegetative (growing)', $o['vegetative_records']];
            $rows[] = ['  Harvested',            $o['harvested_records']];
            $rows[] = [];

            $this->sectionRows[] = count($rows) + 1;
            $rows[] = ['VARIETY METRICS'];
            $rows[] = ['Rice Varieties in DB', $o['total_varieties']];
            $rows[] = [];

            $this->sectionRows[] = count($rows) + 1;
            $rows[] = ['PREDICTION METRICS'];
            $rows[] = ['Total Predictions', $o['total_predictions']];
        }

        if ($includeClass) {
            $rows[] = [];
            $this->sectionRows[] = count($rows) + 1;
            $rows[] = ['PREDICTION CLASS DISTRIBUTION'];
            $rows[] = ['High',   $o['yield_class_counts']['High']   ?? 0];
            $rows[] = ['Medium', $o['yield_class_counts']['Medium'] ?? 0];
            $rows[] = ['Low',    $o['yield_class_counts']['Low']    ?? 0];
        }

        $this->rows = $rows;
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

        // Section header rows (dynamic)
        foreach ($this->sectionRows as $r) {
            $sheet->mergeCells("A{$r}:B{$r}");
            $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
                'alignment' => ['horizontal' => 'left', 'vertical' => 'center', 'indent' => 1],
            ]);
            $sheet->getRowDimension($r)->setRowHeight(20);
        }

        // Left-align all values in column B
        $sheet->getStyle('B1:B' . max(1, count($this->rows)))
              ->getAlignment()->setHorizontal('left');

        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(28);

        return [];
    }
}