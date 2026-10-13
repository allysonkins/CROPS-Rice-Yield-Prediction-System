<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FullReportExport implements Export, WithMultipleSheets
{
    protected array $data;
    protected array $sections;

    public function __construct(ReportService $service, array $filters = [], array $sections = [])
    {
        $this->data     = $service->withFilters($filters)->withSections($sections)->build();
        $this->sections = $sections;
    }

    public function sheets(): array
    {
        $include = fn(string $key) => empty($this->sections) || in_array($key, $this->sections, true);

        $sheets = [];

        // Overview sheet carries BOTH overview + class_distribution
        if ($include('overview') || $include('class_distribution')) {
            $sheets[] = new Sheets\OverviewSheet($this->data, $this->sections);
        }

        if ($include('by_barangay'))  $sheets[] = new Sheets\BarangaySummarySheet($this->data);
        if ($include('by_variety'))   $sheets[] = new Sheets\VarietySummarySheet($this->data);
        if ($include('top_farms'))    $sheets[] = new Sheets\TopFarmsSheet($this->data);
        if ($include('low_farms'))    $sheets[] = new Sheets\LowFarmsSheet($this->data);
        if ($include('farm_records')) $sheets[] = new Sheets\FarmRecordsSheet($this->data);
        if ($include('predictions'))  $sheets[] = new Sheets\PredictionsSheet($this->data);
        if ($include('farmers'))      $sheets[] = new Sheets\FarmersSheet($this->data);
        if ($include('farms'))        $sheets[] = new Sheets\FarmsSheet($this->data);

        // Safety net — never return zero sheets
        if (empty($sheets)) {
            $sheets[] = new Sheets\OverviewSheet($this->data, ['overview', 'class_distribution']);
        }

        return $sheets;
    }
}