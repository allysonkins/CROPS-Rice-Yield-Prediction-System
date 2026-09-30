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
    /**
     * Chunk sizes — tuned for shared hosting with 30s max_execution_time
     * and 4MB MySQL max_allowed_packet.
     */
    private const STAGE_CHUNK  = 500;    // rows inserted into staging per batch
    private const COMMIT_CHUNK = 200;    // farmers created per transaction

    // ═════════════════════════════════════════════════════════
    // PREVIEW (streaming, stages to DB, returns summary)
    // ═════════════════════════════════════════════════════════

    /**
     * Stream the CSV row-by-row into farmer_import_rows. Never loads the
     * whole file into memory and never puts rows in the session.
     *
     * @return array{total:int,new:int,duplicate:int,invalid:int,skipped:int}
     */
    public function previewAndStage(string $absolutePath, FarmerImportBatch $batch): array
    {
        $handle = fopen($absolutePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open uploaded file.');
        }

        // Strip UTF-8 BOM if present (Excel / Windows exports)
        $firstLineRaw = fgets($handle);
        if ($firstLineRaw !== false && substr($firstLineRaw, 0, 3) === "\xEF\xBB\xBF") {
            $firstLineRaw = substr($firstLineRaw, 3);
        }
        rewind($handle);

        // Auto-detect delimiter (, ; \t)
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
                throw new \RuntimeException("Missing required column: {$col}");
            }
        }

        $hasParcelCols = in_array('parcel_no', $header, true)
            || in_array('parcel_barangay', $header, true);

        $format = $hasParcelCols ? 'parcel' : 'farmer';

        $summary = ['total' => 0, 'new' => 0, 'duplicate' => 0, 'invalid' => 0, 'skipped' => 0];
        $buffer  = [];
        $line    = 1;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            // Blank row?
            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }

            $row = array_combine(
                $header,
                array_pad($data, count($header), null)
            );

            // Sanitize every cell to UTF-8
            $row = array_map(fn($v) => $this->toUtf8((string) $v), $row);

            $parsed = $this->validateRow($row, $line, $format);
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

    /**
     * Legacy preview() — kept for backward compatibility with any caller
     * still using array-based flow. Delegates to validateRow().
     */
    public function preview(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open uploaded file.');
        }

        $firstLine = fgets($handle);
        $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        $header = array_map(fn($h) => $this->normalizeHeader($this->toUtf8((string) $h)), $header);

        $required = ['first_name', 'last_name', 'barangay'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                fclose($handle);
                throw new \RuntimeException("Missing required column: {$col}");
            }
        }

        $format = (in_array('parcel_no', $header, true) || in_array('parcel_barangay', $header, true))
            ? 'parcel' : 'farmer';

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count($data) === 1 && trim((string) $data[0]) === '') continue;

            $row = array_combine($header, array_pad($data, count($header), null));
            $row = array_map(fn($v) => $this->toUtf8((string) $v), $row);
            $rows[] = $this->validateRow($row, $line, $format);
        }
        fclose($handle);

        return $rows;
    }

    // ═════════════════════════════════════════════════════════
    // COMMIT (chunked, DB-driven)
    // ═════════════════════════════════════════════════════════

    /**
     * Commit a staged batch. Reads rows from farmer_import_rows in chunks,
     * grouping by farmer key, and creates users + farms inside
     * per-chunk transactions.
     *
     * @return array{createdUsers:int,createdFarms:int,skippedFarms:int}
     */
    public function commitBatch(FarmerImportBatch $batch): array
    {
        $createdUsers = 0;
        $createdFarms = 0;
        $skippedFarms = 0;

        // Collect unique farmer keys — one key per user we'll create
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
                    // Fetch this farmer's staged rows
                    $query = FarmerImportRow::where('batch_id', $batch->id)
                        ->where('status', 'new');

                    if (str_starts_with($key, 'rsbsa:')) {
                        $query->where('rsbsa_number', substr($key, 6));
                    } elseif (str_starts_with($key, 'phone:')) {
                        $query->where('phone', substr($key, 6));
                    } else {
                        // Name fallback (no RSBSA, no phone should have been
                        // caught by validation — but keep for safety)
                        $query->where('name', substr($key, 5));
                    }

                    $rows = $query->orderBy('line')->get();
                    if ($rows->isEmpty()) continue;

                    $first = $rows->first();

                    // Skip if user already exists (double-click safety)
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

    /**
     * Legacy commit() — accepts an array of preview rows (in-memory).
     * Still works, but the controller should prefer commitBatch().
     */
    public function commit(array $rows): array
    {
        $newRows = array_filter($rows, fn($r) => $r['status'] === 'new');

        $grouped = [];
        foreach ($newRows as $row) {
            $key = $row['rsbsa_number'] ?: strtolower($row['name']);
            $grouped[$key][] = $row;
        }

        $createdUsers = 0;
        $createdFarms = 0;
        $skippedFarms = 0;

        DB::transaction(function () use ($grouped, &$createdUsers, &$createdFarms, &$skippedFarms) {
            foreach ($grouped as $parcels) {
                $first = $parcels[0];

                // Coerce to object-like access
                $firstObj = (object) $first;

                if (User::where(function ($q) use ($first) {
                    if (!empty($first['rsbsa_number'])) $q->orWhere('rsbsa_number', $first['rsbsa_number']);
                    if (!empty($first['phone']))        $q->orWhere('phone', $first['phone']);
                })->exists()) {
                    continue;
                }

                $user = $this->createFarmer($firstObj);
                $createdUsers++;

                foreach ($parcels as $p) {
                    if (empty($p['is_rice'])) { $skippedFarms++; continue; }
                    if ($this->createParcelFarm($user, (object) $p, $firstObj)) {
                        $createdFarms++;
                    }
                }

                log_activity('created', 'Farmer imported from RSBSA', $user, [
                    'rsbsa_number' => $first['rsbsa_number'],
                    'parcels'      => count($parcels),
                    'source'       => 'RSBSA CSV import',
                ]);
            }
        });

        return compact('createdUsers', 'createdFarms', 'skippedFarms');
    }

    // ═════════════════════════════════════════════════════════
    // ROW VALIDATION (unchanged rules)
    // ═════════════════════════════════════════════════════════

    private function validateRow(array $row, int $line, string $format): array
    {
        $rsbsa   = trim((string) ($row['rsbsa_number'] ?? ''));
        $phone   = $this->normalizePhone((string) ($row['phone'] ?? $row['contact_number'] ?? ''));
        $first   = trim((string) ($row['first_name'] ?? ''));
        $middle  = trim((string) ($row['middle_name'] ?? ''));
        $last    = trim((string) ($row['last_name'] ?? ''));
        $brgy    = trim((string) ($row['barangay'] ?? ''));
        $sex     = trim((string) ($row['sex'] ?? ''));

        $parcelNo   = $format === 'parcel' ? trim((string) ($row['parcel_no'] ?? '')) : '';
        $parcelBrgy = $format === 'parcel' ? trim((string) ($row['parcel_barangay'] ?? '')) : '';
        $area       = $format === 'parcel'
            ? trim((string) ($row['area_ha'] ?? $row['farm_area_ha'] ?? $row['land_area_ha'] ?? ''))
            : trim((string) ($row['farm_area_ha'] ?? $row['land_area_ha'] ?? ''));
        $commodity  = $format === 'parcel' ? strtolower(trim((string) ($row['commodity'] ?? 'rice'))) : 'rice';

        $errors = [];
        if ($first === '' || $last === '') $errors[] = 'Missing name';
        if ($brgy === '')                 $errors[] = 'Missing barangay';

        // Rule: at least one login identifier must exist
        if ($rsbsa === '' && $phone === '') {
            $errors[] = 'Missing login identifier (need phone or RSBSA number)';
        }

        if ($phone !== '' && !preg_match('/^09\d{9}$/', $phone)) {
            $errors[] = 'Invalid PH mobile number';
        }
        if ($brgy !== '' && !in_array($brgy, config('santiago.barangays', []), true)) {
            $errors[] = "Unknown barangay: {$brgy}";
        }
        if ($parcelBrgy !== '' && !in_array($parcelBrgy, config('santiago.barangays', []), true)) {
            $errors[] = "Unknown parcel barangay: {$parcelBrgy}";
        }

        $status = 'new';

        if ($rsbsa && User::where('rsbsa_number', $rsbsa)->exists()) {
            $status = 'duplicate';
            $errors[] = 'RSBSA number already registered';
        }
        if ($phone && User::where('phone', $phone)->exists()) {
            $status = 'duplicate';
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

        $user->setPin($pin);

        return $user;
    }

    private function createParcelFarm(User $user, object $parcel, object $first): bool
    {
        $farmName = !empty($parcel->parcel_no)
            ? "Parcel {$parcel->parcel_no} — {$first->name}"
            : "{$first->name} Farm";

        // Re-upload protection
        if (Farm::where('user_id', $user->id)->where('name', $farmName)->exists()) {
            return false;
        }

        $centroid = config('santiago.centroids')[$parcel->parcel_barangay ?? ''] ?? [];

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