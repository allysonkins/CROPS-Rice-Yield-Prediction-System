<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComparisonExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    protected array $filters;
    protected array $data;
    protected array $humanFilters;

    /** Row numbers (1-based) that should be styled as section banners */
    protected array $sectionHeaderRows = [];

    /** Row number of the year-table column header */
    protected int $tableHeaderRow = 0;

    public function __construct(array $filters, array $data, array $humanFilters)
    {
        $this->filters      = $filters;
        $this->data         = $data;
        $this->humanFilters = $humanFilters;
    }

    public function title(): string
    {
        return 'Yield Comparison';
    }

    public function array(): array
    {
        $rows = [];

        // ── Title block ──
        $rows[] = ['CROPS — Rice Yield Comparison Report'];
        $rows[] = ['City Agriculture Office · Santiago City'];
        $rows[] = ['Generated', now()->format('F d, Y h:i A')];
        $rows[] = ['Reference', 'CROPS-CMP-' . substr((string) now()->getTimestamp(), -6)];
        $rows[] = [];

        // ── Filters ──
        $this->sectionHeaderRows[] = count($rows) + 1;
        $rows[] = ['FILTERS APPLIED'];

        if (empty($this->humanFilters)) {
            $rows[] = ['(none — showing all records)'];
        } else {
            foreach ($this->humanFilters as $k => $v) {
                $rows[] = [$k, $v];
            }
        }
        $rows[] = [];

        // ── Summary ──
        $overall = is_array($this->data['overall'] ?? null) ? $this->data['overall'] : [];

        $this->sectionHeaderRows[] = count($rows) + 1;
        $rows[] = ['SUMMARY'];
        $rows[] = ['Records Compared', $overall['matched'] ?? 0];
        $rows[] = ['Avg. Difference (t/ha)', $overall['mae'] ?? '—'];
        $rows[] = ['Years Compared', $overall['years'] ?? 0];
        $rows[] = [];

        // ── Year-by-year table ──
        $this->sectionHeaderRows[] = count($rows) + 1;
        $rows[] = ['YEAR-BY-YEAR BREAKDOWN'];

        $this->tableHeaderRow = count($rows) + 1;
        $rows[] = [
            'Year',
            'Records',
            'Avg. Predicted (t/ha)',
            'Avg. Actual (t/ha)',
            'Difference (t/ha)',
            'Matched',
        ];

        foreach (($this->data['perYear'] ?? []) as $row) {
            $delta = $row['delta'] ?? null;

            $rows[] = [
                $row['year'] ?? '',
                $row['records'] ?? 0,
                ($row['avgPred'] ?? null) !== null ? number_format((float) $row['avgPred'], 2) : '—',
                ($row['avgAct']  ?? null) !== null ? number_format((float) $row['avgAct'],  2) : '—',
                $delta !== null
                    ? (($delta > 0 ? '+' : '') . number_format((float) $delta, 2))
                    : '—',
                $row['matched'] ?? 0,
            ];
        }

        if (!empty($this->data['missingYears'])) {
            $rows[] = [];
            $rows[] = ['Missing years (no records found)', implode(', ', $this->data['missingYears'])];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 22,
            'C' => 22,
            'D' => 22,
            'E' => 20,
            'F' => 12,
        ];
    }

    /**
     * @return array
     */
    public function styles(Worksheet $sheet): ?array
    {
        // Guard against tiny sheets
        $highestRow = max($sheet->getHighestRow(), 1);

        // ── Title block (rows 1 & 2) ──
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['argb' => 'FF165B33']],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['argb' => 'FF64748B']],
        ]);

        // ── Section banners (dynamic) ──
        foreach ($this->sectionHeaderRows as $row) {
            if ($row < 1 || $row > $highestRow) {
                continue;
            }
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
                'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(20);
        }

        // ── Year table header + body (dynamic) ──
        if ($this->tableHeaderRow > 0 && $this->tableHeaderRow <= $highestRow) {
            $r = $this->tableHeaderRow;

            $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10.5],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
                'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
            ]);
            $sheet->getRowDimension($r)->setRowHeight(22);

            $firstDataRow = $r + 1;

            if ($firstDataRow <= $highestRow) {
                $sheet->getStyle("A{$firstDataRow}:F{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color'       => ['argb' => 'FFE2E8F0'],
                        ],
                    ],
                ]);
                // Right-align numeric columns B..F
                $sheet->getStyle("B{$firstDataRow}:F{$highestRow}")
                      ->getAlignment()->setHorizontal('right');
            }

            $sheet->freezePane("A{$firstDataRow}");
        }

        return [];
    }
}