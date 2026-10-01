<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmerImportBatch;
use App\Models\FarmerImportRow;
use App\Services\FarmerImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FarmerImportController extends Controller
{
    public function form()
    {
        return view('admin.farmers.import');
    }

    /**
     * STEP 1 — Upload & preview
     */
    public function preview(Request $request, FarmerImportService $service)
    {
        $request->validate([
            'csv' => 'required|file|max:51200',
        ]);

        $file = $request->file('csv');

        if (!$file->isValid()) {
            return back()->with('error', 'Upload failed: ' . $file->getErrorMessage());
        }

        $uuid       = (string) Str::uuid();
        $storedDir  = 'imports';
        $storedName = $uuid . '.csv';

        try {
            $path    = $file->storeAs($storedDir, $storedName, 'local');
            $absPath = Storage::disk('local')->path($path);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not save uploaded file: ' . $e->getMessage());
        }

        try {
            $batch = FarmerImportBatch::create([
                'uuid'              => $uuid,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path'       => $absPath,
                'status'            => 'pending',
            ]);

            $summary = $service->previewAndStage($absPath, $batch);

            $batch->update([
                'total_rows'      => $summary['total'],
                'new_count'       => $summary['new'],
                'duplicate_count' => $summary['duplicate'],
                'invalid_count'   => $summary['invalid'],
                'skipped_count'   => $summary['skipped'],
                'status'          => 'previewed',
            ]);

        } catch (\Throwable $e) {
            \Log::error('Farmer import preview failed', [
                'uuid'  => $uuid,
                'file'  => $absPath ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($batch)) {
                $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }

            return back()->with('error',
                'Import preview failed: ' . $e->getMessage() .
                ' — please check storage/logs/laravel.log for details.');
        }

        $rows = FarmerImportRow::where('batch_id', $batch->id)
            ->orderBy('line')
            ->limit(500)
            ->get()
            ->map(fn($r) => [
                'line'          => $r->line,
                'status'        => $r->status,
                'is_rice'       => $r->is_rice,
                'name'          => $r->name,
                'rsbsa_number'  => $r->rsbsa_number,
                'barangay'      => $r->barangay,
                'phone'         => $r->phone,
                'parcel_no'     => $r->parcel_no,
                'land_area_ha'  => $r->land_area_ha,
                'errors'        => $r->errors ? explode('; ', $r->errors) : [],
            ])
            ->all();

        return view('admin.farmers.import', [
            'rows'    => $rows,
            'summary' => $summary,
            'batch'   => $batch,
        ]);
    }

    /**
     * STEP 2 — Commit (single-shot, kept for compatibility)
     */
    public function commit(Request $request, FarmerImportService $service)
    {
        $uuid = $request->input('batch_uuid');
        if (!$uuid) {
            return redirect()->route('admin.farmers.import.form')
                ->with('error', 'Missing batch reference. Please upload the CSV again.');
        }

        $batch = FarmerImportBatch::where('uuid', $uuid)->first();
        if (!$batch || $batch->status === 'committed') {
            return redirect()->route('admin.farmers.import.form')
                ->with('error', 'Batch not found or already committed.');
        }

        if (!file_exists($batch->stored_path)) {
            $batch->update(['status' => 'failed', 'error_message' => 'Stored CSV file missing.']);
            return redirect()->route('admin.farmers.import.form')
                ->with('error', 'The uploaded CSV file is no longer available. Please re-upload.');
        }

        try {
            $result = $service->commitBatch($batch);
        } catch (\Throwable $e) {
            \Log::error('Farmer import commit failed', [
                'batch' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            return redirect()->route('admin.farmers.import.form')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }

        try { @unlink($batch->stored_path); } catch (\Throwable $e) {}
        FarmerImportRow::where('batch_id', $batch->id)->delete();
        $batch->update(['status' => 'committed']);

        $msg = "Imported {$result['createdUsers']} farmers and {$result['createdFarms']} farms.";
        if ($result['skippedFarms'] > 0) {
            $msg .= " Skipped {$result['skippedFarms']} non-rice parcels.";
        }
        $msg .= ' Login credentials are on the Credentials page.';

        return redirect()->route('admin.farmers.credentials')->with('success', $msg);
    }

    /**
     * STEP 2b — Chunked commit. Called repeatedly by the browser.
     */
    public function commitChunk(Request $request, FarmerImportService $service)
    {
        @set_time_limit(120);

        $uuid = $request->input('batch_uuid');
        if (!$uuid) {
            return response()->json(['success' => false, 'error' => 'Missing batch reference.'], 400);
        }

        $batch = FarmerImportBatch::where('uuid', $uuid)->first();
        if (!$batch) {
            return response()->json(['success' => false, 'error' => 'Batch not found.'], 404);
        }

        if ($batch->status === 'committed') {
            return response()->json([
                'success'          => true,
                'createdUsers'     => 0,
                'createdFarms'     => 0,
                'skippedFarms'     => 0,
                'remaining'        => 0,
                'alreadyCommitted' => true,
            ]);
        }

        try {
            $result = $service->commitChunk($batch, 40);

            if ($result['remaining'] === 0) {
                try { @unlink($batch->stored_path); } catch (\Throwable $e) {}
                FarmerImportRow::where('batch_id', $batch->id)->delete();
                $batch->update(['status' => 'committed']);
            }

            return response()->json([
                'success'      => true,
                'createdUsers' => $result['createdUsers'],
                'createdFarms' => $result['createdFarms'],
                'skippedFarms' => $result['skippedFarms'],
                'remaining'    => $result['remaining'],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Farmer import commit-chunk failed', [
                'batch' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function credentials()
    {
        return redirect()->route('admin.farmers.credentials');
    }

    public function template()
    {
        $rows = [
            ['rsbsa_number','first_name','middle_name','last_name','sex','phone','barangay',
             'parcel_no','parcel_barangay','area_ha','commodity'],
            ['RSBSA-0001','Juan','Dela','Cruz','Male','09171234567','Baluarte',
             '1','Baluarte','1.50','Rice'],
            ['RSBSA-0001','Juan','Dela','Cruz','Male','09171234567','Baluarte',
             '2','Sagana','0.80','Rice'],
            ['RSBSA-0002','Maria','','Santos','Female','09181234568','Rizal',
             '1','Rizal','2.20','Rice'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $r) fputcsv($out, $r);
            fclose($out);
        }, 'rsbsa_template.csv', ['Content-Type' => 'text/csv']);
    }

    public function diagnostics()
    {
        $checks = [
            'PHP Version'                  => PHP_VERSION,
            'max_execution_time'           => ini_get('max_execution_time') . ' s',
            'memory_limit'                 => ini_get('memory_limit'),
            'upload_max_filesize'          => ini_get('upload_max_filesize'),
            'post_max_size'                => ini_get('post_max_size'),
            'max_input_time'               => ini_get('max_input_time') . ' s',
            'max_input_vars'               => ini_get('max_input_vars'),
            'MySQL max_allowed_packet'     => $this->mysqlPacketSize(),
            'Storage writable'             => is_writable(storage_path()) ? 'YES' : 'NO',
            'storage/app writable'         => is_writable(storage_path('app')) ? 'YES' : 'NO',
            'storage/logs writable'        => is_writable(storage_path('logs')) ? 'YES' : 'NO',
            'bootstrap/cache writable'     => is_writable(base_path('bootstrap/cache')) ? 'YES' : 'NO',
            'Session driver'               => config('session.driver'),
            'Session cookie size limit'    => '4096 bytes',
            'mbstring extension'           => extension_loaded('mbstring') ? 'YES' : 'NO',
            'fileinfo extension'           => extension_loaded('fileinfo') ? 'YES' : 'NO',
            'intl extension'               => extension_loaded('intl') ? 'YES' : 'NO',
            'PDO MySQL'                    => extension_loaded('pdo_mysql') ? 'YES' : 'NO',
        ];

        return response()->json($checks, 200, [], JSON_PRETTY_PRINT);
    }

    private function mysqlPacketSize(): string
    {
        try {
            $row = \DB::selectOne('SHOW VARIABLES LIKE "max_allowed_packet"');
            if ($row && isset($row->Value)) {
                return round($row->Value / 1024 / 1024, 2) . ' MB';
            }
        } catch (\Throwable $e) {}
        return 'unknown';
    }
}