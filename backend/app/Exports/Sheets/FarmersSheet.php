<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FarmersSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Farmers';
    }

    public function collection(): Collection
    {
        return $this->data['farmers'];
    }

    public function headings(): array
    {
        return ['Name', 'RSBSA Number', 'Phone', 'Barangay', 'Farms', 'Verified', 'Registered'];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $this->styleSheet($sheet, 'A', 'G');
        return [];
    }

    protected function styleSheet(Worksheet $sheet, string $firstCol, string $lastCol): void
    {
        $range = "{$firstCol}1:{$lastCol}1";
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF165B33']],
            'alignment' => ['horizontal' => 'left', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("{$firstCol}2:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => 'top', 'wrapText' => true],
            ]);
        }

        $sheet->setAutoFilter($range);
        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(20);
    }
}