<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\FarmerImportBatch;
use App\Models\FarmerImportRow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FarmerImportService
{
    private const STAGE_CHUNK  = 500;
    private const COMMIT_CHUNK = 40;

    // ═════════════════════════════════════════════════════════
    // PREVIEW (streaming, stages to DB, returns summary)
    // ═════════════════════════════════════════════════════════

    public function previewAndStage(string $absolutePath, FarmerImportBatch $batch): array
    {
        $handle = fopen($absolutePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open uploaded file.');
        }

        // Strip UTF-8 BOM if present
        $firstLineRaw = fgets($handle);
        if ($firstLineRaw !== false && substr($firstLineRaw, 0, 3) === "\xEF\xBB\xBF") {
            $firstLineRaw = substr($firstLineRaw, 3);
        }
        rewind($handle);

        // Auto-detect delimiter
        $comma = substr_count((string) $firstLineRaw, ',');
        $semi  = substr_count((string) $firstLineRaw, ';');
        $tab   = substr_count((string) $firstLineRaw, "\t");

        $delimiter = ',';
        if ($semi > $comma && $semi >= $tab) $delimiter = ';';
        elseif ($tab > $comma && $tab > $semi) $delimiter = "\t";

        $headerRaw = fgetcsv($handle, 0, $delimiter);
        if (!$headerRaw) {
            fclose($handle);
            throw new \RuntimeException('CSV appears to be empty.');
        }

        $header = array_map(
            fn($h) => $this->normalizeHeader($this->toUtf8((string) $h)),
            $headerRaw
        );

        $required = ['first_name', 'last_name', 'barangay'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                fclose($handle);
                throw new \RuntimeException(
                    "Missing required column: {$col}. Found: " . implode(', ', $header)
                );
            }
        }

        $hasParcelCols = in_array('parcel_no', $header, true)
            || in_array('parcel_barangay', $header, true);

        $format = $hasParcelCols ? 'parcel' : 'farmer';

        $summary     = ['total' => 0, 'new' => 0, 'duplicate' => 0, 'invalid' => 0, 'skipped' => 0];
        $buffer      = [];
        $line        = 1;
        $headerCount = count($header);

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }

            // Trim extras + pad missing so array_combine never fails
            $data = array_slice($data, 0, $headerCount);
            $data = array_pad($data, $headerCount, null);

            $row = array_combine($header, $data);
            $row = array_map(fn($v) => $this->toUtf8((string) $v), $row);

            $parsed   = $this->validateRow($row, $line, $format);
            $buffer[] = $parsed;

            $summary['total']++;
            $summary[$parsed['status']]++;
            if (!$parsed['is_rice']) $summary['skipped']++;

            if (count($buffer) >= self::STAGE_CHUNK) {
                $this->flushStage($batch->id, $buffer);
                $buffer = [];
            }
        }

        if (!empty($buffer)) {
            $this->flushStage($batch->id, $buffer);
        }

        fclose($handle);
        return $summary;
    }

    // ═════════════════════════════════════════════════════════
    // COMMIT — single-shot (kept for backward compatibility)
    // ═════════════════════════════════════════════════════════

    public function commitBatch(FarmerImportBatch $batch): array
    {
        $createdUsers = 0;
        $createdFarms = 0;
        $skippedFarms = 0;

        $keys = FarmerImportRow::where('batch_id', $batch->id)
            ->where('status', 'new')
            ->select('rsbsa_number', 'phone', 'name')
            ->get()
            ->map(fn($r) => $this->farmerKey($r->rsbsa_number, $r->phone, $r->name))
            ->unique()
            ->values()
            ->all();

        foreach (array_chunk($keys, self::COMMIT_CHUNK) as $keyChunk) {
            DB::transaction(function () use (
                $keyChunk, $batch,
                &$createdUsers, &$createdFarms, &$skippedFarms
            ) {
                foreach ($keyChunk as $key) {
                    $query = FarmerImportRow::where('batch_id', $batch->id)
                        ->where('status', 'new');

                    if (str_starts_with($key, 'rsbsa:')) {
                        $query->where('rsbsa_number', substr($key, 6));
                    } elseif (str_starts_with($key, 'phone:')) {
                        $query->where('phone', substr($key, 6));
                    } else {
                        $query->where('name', substr($key, 5));
                    }

                    $rows = $query->orderBy('line')->get();
                    if ($rows->isEmpty()) continue;

                    $first = $rows->first();

                    $existing = User::where(function ($q) use ($first) {
                        if ($first->rsbsa_number) $q->orWhere('rsbsa_number', $first->rsbsa_number);
                        if ($first->phone)        $q->orWhere('phone', $first->phone);
                    })->first();

                    if ($existing) continue;

                    $user = $this->createFarmer($first);
                    $createdUsers++;

                    foreach ($rows as $r) {
                        if (!$r->is_rice) { $skippedFarms++; continue; }
                        if ($this->createParcelFarm($user, $r, $first)) {
                            $createdFarms++;
                        }
                    }

                    log_activity('created', 'Farmer imported from RSBSA', $user, [
                        'rsbsa_number' => $first->rsbsa_number,
                        'parcels'      => $rows->count(),
                        'source'       => 'RSBSA CSV import',
                    ]);
                }
            });

            gc_collect_cycles();
        }

        return compact('createdUsers', 'createdFarms', 'skippedFarms');
    }

    // ═════════════════════════════════════════════════════════
    // COMMIT — chunked (called repeatedly by the browser)
    // ═════════════════════════════════════════════════════════

    /**
     * Process the next chunk of unique farmer keys. Each call:
     *   - finds up to $limit unprocessed farmer keys
     *   - creates the user + parcels inside per-farmer transactions
     *   - marks the corresponding staging rows processed_at = now()
     */
    public function commitChunk(FarmerImportBatch $batch, int $limit = 40): array
    {
        // Scan a window of unprocessed rows, collect up to $limit unique keys
        $scan = FarmerImportRow::where('batch_id', $batch->id)
            ->where('status', 'new')
            ->whereNull('processed_at')
            ->orderBy('line')
            ->limit(2000)
            ->get(['id', 'line', 'rsbsa_number', 'phone', 'name']);

        if ($scan->isEmpty()) {
            return ['createdUsers' => 0, 'createdFarms' => 0, 'skippedFarms' => 0, 'remaining' => 0];
        }

        $keys = [];
        foreach ($scan as $r) {
            $k = $this->farmerKey($r->rsbsa_number, $r->phone, $r->name);
            if (!isset($keys[$k])) {
                $keys[$k] = true;
                if (count($keys) >= $limit) break;
            }
        }
        $keys = array_keys($keys);

        $createdUsers = 0;
        $createdFarms = 0;
        $skippedFarms = 0;

        foreach ($keys as $key) {
            DB::transaction(function () use (
                $key, $batch,
                &$createdUsers, &$createdFarms, &$skippedFarms
            ) {
                $query = FarmerImportRow::where('batch_id', $batch->id)
                    ->where('status', 'new')
                    ->whereNull('processed_at');

                if (str_starts_with($key, 'rsbsa:')) {
                    $query->where('rsbsa_number', substr($key, 6));
                } elseif (str_starts_with($key, 'phone:')) {
                    $query->where('phone', substr($key, 6));
                } else {
                    $query->where('name', substr($key, 5));
                }

                $rows = $query->orderBy('line')->get();
                if ($rows->isEmpty()) return;

                $first = $rows->first();
                $ids   = $rows->pluck('id')->all();

                $exists = User::where(function ($q) use ($first) {
                    if ($first->rsbsa_number) $q->orWhere('rsbsa_number', $first->rsbsa_number);
                    if ($first->phone)        $q->orWhere('phone', $first->phone);
                })->exists();

                if ($exists) {
                    FarmerImportRow::whereIn('id', $ids)->update(['processed_at' => now()]);
                    return;
                }

                $user = $this->createFarmer($first);
                $createdUsers++;

                foreach ($rows as $r) {
                    if (!$r->is_rice) { $skippedFarms++; continue; }
                    if ($this->createParcelFarm($user, $r, $first)) {
                        $createdFarms++;
                    }
                }

                log_activity('created', 'Farmer imported from RSBSA', $user, [
                    'rsbsa_number' => $first->rsbsa_number,
                    'parcels'      => $rows->count(),
                    'source'       => 'RSBSA CSV import',
                ]);

                FarmerImportRow::whereIn('id', $ids)->update(['processed_at' => now()]);
            });
        }

        $remaining = FarmerImportRow::where('batch_id', $batch->id)
            ->where('status', 'new')
            ->whereNull('processed_at')
            ->count();

        return compact('createdUsers', 'createdFarms', 'skippedFarms', 'remaining');
    }

    // ═════════════════════════════════════════════════════════
    // ROW VALIDATION
    // ═════════════════════════════════════════════════════════

    private function validateRow(array $row, int $line, string $format): array
    {
        $rsbsa  = trim((string) ($row['rsbsa_number'] ?? ''));
        $phone  = $this->normalizePhone((string) ($row['phone'] ?? $row['contact_number'] ?? ''));
        $first  = trim((string) ($row['first_name'] ?? ''));
        $middle = trim((string) ($row['middle_name'] ?? ''));
        $last   = trim((string) ($row['last_name'] ?? ''));
        $brgy   = trim((string) ($row['barangay'] ?? ''));
        $sex    = trim((string) ($row['sex'] ?? ''));

        $parcelNo   = $format === 'parcel' ? trim((string) ($row['parcel_no'] ?? '')) : '';
        $parcelBrgy = $format === 'parcel' ? trim((string) ($row['parcel_barangay'] ?? '')) : '';
        $area       = $format === 'parcel'
            ? trim((string) ($row['area_ha'] ?? $row['farm_area_ha'] ?? $row['land_area_ha'] ?? ''))
            : trim((string) ($row['farm_area_ha'] ?? $row['land_area_ha'] ?? ''));
        $commodity  = $format === 'parcel' ? strtolower(trim((string) ($row['commodity'] ?? 'rice'))) : 'rice';

        $errors = [];
        if ($first === '' || $last === '') $errors[] = 'Missing name';
        if ($brgy === '')                 $errors[] = 'Missing barangay';

        if ($rsbsa === '' && $phone === '') {
            $errors[] = 'Missing login identifier (need phone or RSBSA number)';
        }

        if ($phone !== '' && !preg_match('/^09\d{9}$/', $phone)) {
            $errors[] = 'Invalid PH mobile number';
        }

        // ── Barangay validation (case-insensitive, skips if config empty) ──
        $barangays = config('santiago.barangays', []);

        if (!empty($barangays)) {
            static $brgyMap = null;
            if ($brgyMap === null) {
                $brgyMap = [];
                foreach ($barangays as $b) {
                    $brgyMap[strtolower(trim($b))] = $b;
                }
            }

            if ($brgy !== '') {
                $key = strtolower($brgy);
                if (!isset($brgyMap[$key])) {
                    $errors[] = "Unknown barangay: {$brgy}";
                } else {
                    $brgy = $brgyMap[$key];
                }
            }

            if ($parcelBrgy !== '') {
                $key = strtolower($parcelBrgy);
                if (!isset($brgyMap[$key])) {
                    $errors[] = "Unknown parcel barangay: {$parcelBrgy}";
                } else {
                    $parcelBrgy = $brgyMap[$key];
                }
            }
        }

        $status = 'new';

        if ($rsbsa && User::where('rsbsa_number', $rsbsa)->exists()) {
            $status   = 'duplicate';
            $errors[] = 'RSBSA number already registered';
        }
        if ($phone && User::where('phone', $phone)->exists()) {
            $status   = 'duplicate';
            $errors[] = 'Phone already registered';
        }
        if (!empty($errors) && $status !== 'duplicate') {
            $status = 'invalid';
        }

        $isRice = ($commodity === '' || $commodity === 'rice');

        return [
            'line'            => $line,
            'status'          => $status,
            'errors'          => $errors,
            'is_rice'         => $isRice,
            'commodity'       => $commodity,
            'name'            => trim("{$first} {$middle} {$last}"),
            'first_name'      => $first,
            'middle_name'     => $middle ?: null,
            'last_name'       => $last,
            'rsbsa_number'    => $rsbsa,
            'phone'           => $phone,
            'barangay'        => $brgy,
            'sex'             => $sex,
            'parcel_no'       => $parcelNo,
            'parcel_barangay' => $parcelBrgy ?: $brgy,
            'land_area_ha'    => is_numeric($area) ? (float) $area : null,
        ];
    }

    // ═════════════════════════════════════════════════════════
    // HELPERS
    // ═════════════════════════════════════════════════════════

    private function createFarmer(object $first): User
    {
        $pin   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $email = !empty($first->rsbsa_number)
            ? "{$first->rsbsa_number}@crops.local"
            : 'farmer' . Str::random(8) . '@crops.local';

        $baseEmail = $email;
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $email = str_replace('@crops.local', "-{$i}@crops.local", $baseEmail);
            $i++;
        }

        $user = User::create([
            'name'               => $first->name,
            'email'              => $email,
            'phone'              => $first->phone ?: null,
            'rsbsa_number'       => $first->rsbsa_number ?: null,
            'barangay'           => $first->barangay,
            'password'           => Hash::make($pin),
            'role'               => 'farmer',
            'email_verified_at'  => now(),
            'verified_by_cao_at' => now(),
            'verified_by_cao_id' => auth()->id(),
        ]);

        if (method_exists($user, 'setPin')) {
            $user->setPin($pin);
        }

        return $user;
    }

    private function createParcelFarm(User $user, object $parcel, object $first): bool
    {
        $farmName = !empty($parcel->parcel_no)
            ? "Parcel {$parcel->parcel_no} — {$first->name}"
            : "{$first->name} Farm";

        if (Farm::where('user_id', $user->id)->where('name', $farmName)->exists()) {
            return false;
        }

        $centroid = config('santiago.centroids', [])[$parcel->parcel_barangay ?? ''] ?? [];

        Farm::create([
            'user_id'      => $user->id,
            'name'         => $farmName,
            'barangay'     => $parcel->parcel_barangay ?: $first->barangay,
            'land_area_ha' => $parcel->land_area_ha ?: 1.00,
            'soil_type'    => 'Clay Loam',
            'latitude'     => $centroid['lat'] ?? null,
            'longitude'    => $centroid['lng'] ?? null,
        ]);

        return true;
    }

    private function farmerKey(?string $rsbsa, ?string $phone, ?string $name): string
    {
        if (!empty($rsbsa)) return 'rsbsa:' . $rsbsa;
        if (!empty($phone)) return 'phone:' . $phone;
        return 'name:' . strtolower((string) $name);
    }

    private function flushStage(int $batchId, array $buffer): void
    {
        $now = now();

        $insert = array_map(function ($r) use ($batchId, $now) {
            return [
                'batch_id'        => $batchId,
                'line'            => $r['line'],
                'status'          => $r['status'],
                'is_rice'         => $r['is_rice'],
                'rsbsa_number'    => $r['rsbsa_number'] ?: null,
                'name'            => $r['name'],
                'first_name'      => $r['first_name'] ?: null,
                'middle_name'     => $r['middle_name'] ?? null,
                'last_name'       => $r['last_name'] ?: null,
                'sex'             => $r['sex'] ?: null,
                'phone'           => $r['phone'] ?: null,
                'barangay'        => $r['barangay'] ?: null,
                'parcel_no'       => $r['parcel_no'] ?: null,
                'parcel_barangay' => $r['parcel_barangay'] ?: null,
                'land_area_ha'    => $r['land_area_ha'],
                'commodity'       => $r['commodity'] ?: null,
                'errors'          => !empty($r['errors']) ? implode('; ', $r['errors']) : null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }, $buffer);

        FarmerImportRow::insert($insert);
    }

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') return '';
        if (str_starts_with($digits, '639')) $digits = '0' . substr($digits, 2);
        if (str_starts_with($digits, '9') && strlen($digits) === 10) $digits = '0' . $digits;
        return $digits;
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/[^a-z0-9_]+/', '_', $h);
        return trim($h, '_');
    }

    private function toUtf8(string $s): string
    {
        if ($s === '') return '';
        if (mb_check_encoding($s, 'UTF-8')) return $s;
        return mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    }
}