<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FullReportExport implements Export, WithMultipleSheets
{
    protected array $data;

    public function __construct(ReportService $service)
    {
        $this->data = $service->build();
    }

    public function sheets(): array
    {
        return [
            new Sheets\OverviewSheet($this->data),
            new Sheets\FarmersSheet($this->data),
            new Sheets\FarmsSheet($this->data),
            new Sheets\FarmRecordsSheet($this->data),
            new Sheets\PredictionsSheet($this->data),
            new Sheets\BarangaySummarySheet($this->data),
            new Sheets\VarietySummarySheet($this->data),
        ];
    }
}