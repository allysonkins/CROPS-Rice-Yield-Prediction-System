<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FarmerImportBatch;
use App\Models\FarmerImportRow;
use App\Models\User;
use App\Models\Farm;
use App\Services\FarmerImportService;
use Illuminate\Support\Str;

$svc = app(FarmerImportService::class);

function ok($label, $cond, $detail = '') {
    $mark = $cond ? "\033[32m✓\033[0m" : "\033[31m✗\033[0m";
    echo "  {$mark} {$label}";
    if ($detail) echo "  \033[90m{$detail}\033[0m";
    echo PHP_EOL;
    return $cond;
}

function section($title) {
    echo PHP_EOL . "\033[1;36m▶ {$title}\033[0m" . PHP_EOL;
}

$totalPassed = 0;
$totalFailed = 0;

function assertCase($label, $cond) {
    global $totalPassed, $totalFailed;
    if ($cond) $totalPassed++;
    else       $totalFailed++;
    ok($label, $cond);
}

// ─────────────────────────────────────────────────────────
// Cleanup any leftover test data
// ─────────────────────────────────────────────────────────
User::where('rsbsa_number', 'like', 'RSBSA-TEST-%')->delete();
FarmerImportRow::whereHas('batch', fn($q) => $q->where('original_filename', 'like', 'test-%'))->delete();
FarmerImportBatch::where('original_filename', 'like', 'test-%')->delete();

// ─────────────────────────────────────────────────────────
// TEST 1 — Missing column rejection
// ─────────────────────────────────────────────────────────
section('TEST 1 — Missing required column');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp, "first_name,last_name\nJuan,Cruz\n");

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-missing-col.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$threw = false;
try {
    $svc->previewAndStage($tmp, $batch);
} catch (\Throwable $e) {
    $threw = true;
    assertCase(
        'Rejects CSV missing "barangay" column',
        str_contains($e->getMessage(), 'Missing required column')
    );
}
if (!$threw) assertCase('Rejects CSV missing "barangay" column', false, 'no exception thrown');
unlink($tmp);

// ─────────────────────────────────────────────────────────
// TEST 2 — Happy path
// ─────────────────────────────────────────────────────────
section('TEST 2 — Happy path (2 farmers, 3 parcels)');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp,
    "rsbsa_number,first_name,middle_name,last_name,sex,phone,barangay,parcel_no,parcel_barangay,area_ha,commodity\n" .
    "RSBSA-TEST-001,Juan,Dela,Cruz,Male,09170000001,Baluarte,1,Baluarte,1.50,Rice\n" .
    "RSBSA-TEST-001,Juan,Dela,Cruz,Male,09170000001,Baluarte,2,Sagana,0.80,Rice\n" .
    "RSBSA-TEST-002,Maria,,Santos,Female,09170000002,Rizal,1,Rizal,2.20,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-happy.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
$batch->update([
    'total_rows' => $summary['total'],
    'new_count' => $summary['new'],
    'status' => 'previewed',
]);

assertCase('Summary total = 3',       $summary['total'] === 3);
assertCase('Summary new = 3',         $summary['new'] === 3);
assertCase('Summary invalid = 0',     $summary['invalid'] === 0);
assertCase('Summary duplicate = 0',   $summary['duplicate'] === 0);
assertCase('Summary skipped = 0',     $summary['skipped'] === 0);

$stagedCount = FarmerImportRow::where('batch_id', $batch->id)->count();
assertCase('3 rows staged in DB',     $stagedCount === 3);

// Commit
$result = $svc->commitBatch($batch);
assertCase('Created 2 users',         $result['createdUsers'] === 2, "got {$result['createdUsers']}");
assertCase('Created 3 farms',         $result['createdFarms'] === 3, "got {$result['createdFarms']}");

$juan = User::where('rsbsa_number', 'RSBSA-TEST-001')->first();
assertCase('Juan exists',             $juan !== null);
assertCase('Juan has 2 farms',        $juan && $juan->farms()->count() === 2);
assertCase('Juan has PIN',            $juan && !empty($juan->pin));
assertCase('Juan is CAO-verified',    $juan && $juan->verified_by_cao_at !== null);

$maria = User::where('rsbsa_number', 'RSBSA-TEST-002')->first();
assertCase('Maria has 1 farm',        $maria && $maria->farms()->count() === 1);
assertCase('Maria has unique email',  $maria && str_contains($maria->email, '@crops.local'));

// ─────────────────────────────────────────────────────────
// TEST 3 — Invalid phone rejected
// ─────────────────────────────────────────────────────────
section('TEST 3 — Invalid phone number');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp,
    "rsbsa_number,first_name,last_name,phone,barangay,commodity\n" .
    "RSBSA-TEST-003,Pedro,Reyes,1234567,Baluarte,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-badphone.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
assertCase('Invalid = 1',             $summary['invalid'] === 1);
$badRow = FarmerImportRow::where('batch_id', $batch->id)->first();
assertCase('Row has error message',   $badRow && str_contains($badRow->errors, 'Invalid PH mobile'));

// ─────────────────────────────────────────────────────────
// TEST 4 — No login identifier
// ─────────────────────────────────────────────────────────
section('TEST 4 — No phone AND no RSBSA');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp,
    "rsbsa_number,first_name,last_name,phone,barangay,commodity\n" .
    ",Pedro,Reyes,,Baluarte,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-nologin.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
assertCase('Invalid = 1 (missing login id)', $summary['invalid'] === 1);
$badRow = FarmerImportRow::where('batch_id', $batch->id)->first();
assertCase('Error mentions login identifier',
    $badRow && str_contains($badRow->errors, 'login identifier'));

// ─────────────────────────────────────────────────────────
// TEST 5 — Unknown barangay
// ─────────────────────────────────────────────────────────
section('TEST 5 — Unknown barangay');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp,
    "rsbsa_number,first_name,last_name,phone,barangay,commodity\n" .
    "RSBSA-TEST-004,Pedro,Reyes,09170000004,Atlantis,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-badbrgy.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
assertCase('Invalid = 1 (unknown barangay)', $summary['invalid'] === 1);
$badRow = FarmerImportRow::where('batch_id', $batch->id)->first();
assertCase('Error mentions unknown barangay',
    $badRow && str_contains($badRow->errors, 'Unknown barangay'));

// ─────────────────────────────────────────────────────────
// TEST 6 — Non-rice commodity skipped
// ─────────────────────────────────────────────────────────
section('TEST 6 — Non-rice parcels');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp,
    "rsbsa_number,first_name,last_name,phone,barangay,parcel_no,parcel_barangay,area_ha,commodity\n" .
    "RSBSA-TEST-005,Cornelius,Farmer,09170000005,Baluarte,1,Baluarte,1.00,Corn\n" .
    "RSBSA-TEST-005,Cornelius,Farmer,09170000005,Baluarte,2,Baluarte,1.00,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-nonrice.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
assertCase('New = 2',                 $summary['new'] === 2);
assertCase('Skipped = 1 (non-rice)',  $summary['skipped'] === 1);

$result = $svc->commitBatch($batch);
$cornelius = User::where('rsbsa_number', 'RSBSA-TEST-005')->first();
assertCase('1 user created',          $result['createdUsers'] === 1);
assertCase('1 farm created (rice only)', $result['createdFarms'] === 1);
assertCase('Cornelius has 1 farm',    $cornelius && $cornelius->farms()->count() === 1);

// ─────────────────────────────────────────────────────────
// TEST 7 — Duplicate detection
// ─────────────────────────────────────────────────────────
section('TEST 7 — Duplicate detection');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp,
    "rsbsa_number,first_name,last_name,phone,barangay,commodity\n" .
    "RSBSA-TEST-001,Juan,Cruz,09170000001,Baluarte,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-dup.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
assertCase('Duplicate = 1',           $summary['duplicate'] === 1);
assertCase('New = 0',                 $summary['new'] === 0);

// ─────────────────────────────────────────────────────────
// TEST 8 — BOM handling
// ─────────────────────────────────────────────────────────
section('TEST 8 — Excel BOM export');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
file_put_contents($tmp,
    "\xEF\xBB\xBF" .  // UTF-8 BOM
    "rsbsa_number,first_name,last_name,phone,barangay,commodity\n" .
    "RSBSA-TEST-006,Bom,Test,09170000006,Baluarte,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-bom.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
assertCase('BOM does not break header', $summary['new'] === 1);
$row = FarmerImportRow::where('batch_id', $batch->id)->first();
assertCase('Name parsed correctly',     $row && $row->first_name === 'Bom');

// ─────────────────────────────────────────────────────────
// TEST 9 — Double-click safety
// ─────────────────────────────────────────────────────────
section('TEST 9 — Double-click protection');

// Rerun same commit — should not create duplicate users
$juanBefore = User::where('rsbsa_number', 'RSBSA-TEST-001')->count();
$result2 = $svc->commitBatch($batch); // batch 6, already committed
$juanAfter = User::where('rsbsa_number', 'RSBSA-TEST-001')->count();
assertCase('No duplicate Juan on 2nd commit', $juanBefore === $juanAfter);

// ─────────────────────────────────────────────────────────
// TEST 10 — Non-UTF8 (Windows-1252)
// ─────────────────────────────────────────────────────────
section('TEST 10 — Windows-1252 encoding');

$tmp = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
// "Jose Nuñez" with ñ in Windows-1252 (byte 0xF1)
file_put_contents($tmp,
    "rsbsa_number,first_name,last_name,phone,barangay,commodity\n" .
    "RSBSA-TEST-007,Jose,Nu\xF1ez,09170000007,Baluarte,Rice\n"
);

$batch = FarmerImportBatch::create([
    'uuid' => (string) Str::uuid(),
    'original_filename' => 'test-cp1252.csv',
    'stored_path' => $tmp,
    'status' => 'pending',
]);

$summary = $svc->previewAndStage($tmp, $batch);
$row = FarmerImportRow::where('batch_id', $batch->id)->first();
assertCase('New = 1',                 $summary['new'] === 1);
assertCase('ñ converted to UTF-8',    $row && $row->last_name === 'Nuñez');

// ─────────────────────────────────────────────────────────
// Cleanup
// ─────────────────────────────────────────────────────────
User::where('rsbsa_number', 'like', 'RSBSA-TEST-%')->delete();
FarmerImportRow::whereHas('batch', fn($q) => $q->where('original_filename', 'like', 'test-%'))->delete();
FarmerImportBatch::where('original_filename', 'like', 'test-%')->delete();

// ─────────────────────────────────────────────────────────
// Report
// ─────────────────────────────────────────────────────────
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo "\033[1m  RESULTS:\033[0m {$totalPassed} passed, {$totalFailed} failed" . PHP_EOL;
echo str_repeat('=', 60) . PHP_EOL;

exit($totalFailed > 0 ? 1 : 0);