<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FarmerController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // CRUD
    // ═══════════════════════════════════════════════════════════

    public function index()
    {
        $farmers = User::where('role', 'farmer')
            ->with('farms')
            ->orderBy('name')
            ->get();

        return view('admin.farmers.index', compact('farmers'));
    }

    public function create()
    {
        return view('admin.farmers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|unique:users,email',
            'password'     => 'required|min:8',
            'phone'        => 'nullable|string|regex:/^09\d{9}$/|unique:users,phone',
            'rsbsa_number' => 'nullable|string|max:50|unique:users,rsbsa_number',
            'barangay'     => 'nullable|string|max:255',
        ], [
            'phone.regex' => 'Phone must be a valid PH mobile number (e.g. 09171234567).',
        ]);

        // Auto-generate a placeholder email when none provided
        $email = $request->email;
        if (empty($email)) {
            $email = $this->generatePlaceholderEmail($request);
        }

        $farmer = User::create([
            'name'              => $request->name,
            'email'             => $email,
            'password'          => Hash::make($request->password),
            'phone'             => $request->phone ?: null,
            'rsbsa_number'      => $request->rsbsa_number ?: null,
            'role'              => 'farmer',
            'barangay'          => $request->barangay,
            'email_verified_at' => now(),
            'verified_by_cao_at'=> now(),           // admin-created = auto-verified
            'verified_by_cao_id'=> auth()->id(),
        ]);

        log_activity('created', 'Farmer account created', $farmer, [
            'email'        => $farmer->email,
            'name'         => $farmer->name,
            'rsbsa_number' => $farmer->rsbsa_number,
            'phone'        => $farmer->phone,
            'barangay'     => $farmer->barangay,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farmer created successfully!',
                'data'    => $farmer,
            ]);
        }

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer created successfully!');
    }

    public function edit($id)
    {
        $farmer = User::where('role', 'farmer')->with('farms')->findOrFail($id);
        return view('admin.farmers.edit', compact('farmer'));
    }

    public function update(Request $request, $id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|unique:users,email,' . $id,
            'password'     => 'nullable|min:8',
            'phone'        => 'nullable|string|regex:/^09\d{9}$/|unique:users,phone,' . $id,
            'rsbsa_number' => 'nullable|string|max:50|unique:users,rsbsa_number,' . $id,
            'barangay'     => 'nullable|string|max:255',
        ], [
            'phone.regex' => 'Phone must be a valid PH mobile number (e.g. 09171234567).',
        ]);

        $oldData = $farmer->only(['name', 'email', 'phone', 'rsbsa_number', 'barangay']);

        $farmer->name         = $request->name;
        $farmer->phone        = $request->phone ?: null;
        $farmer->rsbsa_number = $request->rsbsa_number ?: null;
        $farmer->barangay     = $request->barangay;

        // Handle email: keep placeholder if admin never set one
        if ($request->filled('email')) {
            $farmer->email = $request->email;
        }

        // If admin sets a new password, clear the stored PIN (it's now stale)
        if ($request->filled('password')) {
            $farmer->password         = Hash::make($request->password);
            $farmer->pin_encrypted    = null;
            $farmer->pin_generated_at = null;
        }

        $farmer->save();

        log_activity('updated', 'Farmer account updated', $farmer, [
            'old' => $oldData,
            'new' => $farmer->only(['name', 'email', 'phone', 'rsbsa_number', 'barangay']),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Farmer updated successfully!',
                'data'    => $farmer,
            ]);
        }

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer updated successfully!');
    }

    public function destroy($id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);

        if ($farmer->id === auth()->id()) {
            return redirect()->route('admin.farmers.index')
                ->with('error', 'You cannot delete your own account!');
        }

        log_activity('deleted', 'Farmer account deleted', $farmer, [
            'email'        => $farmer->email,
            'name'         => $farmer->name,
            'rsbsa_number' => $farmer->rsbsa_number,
            'phone'        => $farmer->phone,
            'barangay'     => $farmer->barangay,
        ]);

        $farmer->delete();

        return redirect()->route('admin.farmers.index')
            ->with('success', 'Farmer deleted successfully!');
    }

    // ═══════════════════════════════════════════════════════════
    // CAO VERIFICATION QUEUE
    // ═══════════════════════════════════════════════════════════

    public function pending()
    {
        $pending = User::where('role', 'farmer')
            ->whereNull('verified_by_cao_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.farmers.pending', compact('pending'));
    }

    public function verify($id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);

        if ($farmer->verified_by_cao_at) {
            return back()->with('error', 'This farmer is already verified.');
        }

        $farmer->verified_by_cao_at = now();
        $farmer->verified_by_cao_id = auth()->id();
        $farmer->save();

        log_activity('verified', 'Farmer verified by CAO', $farmer, [
            'verified_by' => auth()->user()->name,
            'farmer'      => $farmer->name,
        ]);

        return back()->with('success', "{$farmer->name} has been verified.");
    }

    public function reject($id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);

        log_activity('rejected', 'Farmer registration rejected by CAO', $farmer, [
            'rejected_by' => auth()->user()->name,
            'farmer'      => $farmer->name,
            'phone'       => $farmer->phone,
        ]);

        $farmer->delete();

        return back()->with('success', 'Farmer registration rejected and removed.');
    }

    // ═══════════════════════════════════════════════════════════
    // CREDENTIALS / PIN MANAGEMENT
    // ═══════════════════════════════════════════════════════════

    /**
     * Master credentials page — shows every farmer with their current PIN.
     * Supports filters: with_pin, never_logged_in
     */
    public function credentials(Request $request)
    {
        $query = User::where('role', 'farmer');

        if ($request->input('filter') === 'with_pin') {
            $query->whereNotNull('pin_encrypted');
        }

        if ($request->input('filter') === 'never_logged_in') {
            $query->whereDoesntHave('activityLogs', function ($q) {
                $q->where('action', 'login');
            });
        }

        $farmers = $query->orderBy('name')->get();

        return view('admin.farmers.credentials', compact('farmers'));
    }

    /**
     * Generate a fresh PIN for a farmer and store it encrypted.
     * The plaintext is flashed so the CAO can see it immediately.
     */
    public function resetPin($id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);

        $newPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $farmer->password = Hash::make($newPin);
        $farmer->setPin($newPin);  // encrypted + persisted

        log_activity('updated', 'Farmer PIN reset by CAO', $farmer, [
            'farmer'   => $farmer->name,
            'reset_by' => auth()->user()->name,
        ]);

        return redirect()
            ->route('admin.farmers.credentials')
            ->with('reset_pin', $newPin)
            ->with('reset_pin_farmer', $farmer->name)
            ->with('success', "New PIN generated for {$farmer->name}.");
    }
    
    /**
 * Chunked generation of missing PINs.
 * Called repeatedly by the credentials page until all PINs are set.
 * Processes up to $chunkSize farmers per request so no single request
 * exceeds shared-hosting timeouts.
 */
public function generatePinsChunk(Request $request)
{
    if (auth()->user()->role !== 'admin') {
        return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
    }

    $chunkSize = 200;

    try {
        $farmers = \App\Models\User::where('role', 'farmer')
            ->whereNull('pin_encrypted')
            ->orderBy('id')
            ->limit($chunkSize)
            ->get();

        $generated = 0;
        foreach ($farmers as $farmer) {
            $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $farmer->setPin($pin);   // encrypts + saves pin_encrypted & pin_generated_at
            $generated++;
        }

        $remaining = \App\Models\User::where('role', 'farmer')
            ->whereNull('pin_encrypted')
            ->count();

        return response()->json([
            'success'   => true,
            'generated' => $generated,
            'remaining' => $remaining,
        ]);

    } catch (\Throwable $e) {
        \Log::error('Generate all PINs failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Standalone printable slip for one farmer.
     */
    public function slip($id)
    {
        $farmer = User::where('role', 'farmer')->findOrFail($id);
        return view('admin.farmers.slip', compact('farmer'));
    }

    // ═══════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════

    private function generatePlaceholderEmail(Request $request): string
    {
        if ($request->filled('rsbsa_number')) {
            $slug = Str::slug($request->rsbsa_number, '');
            $localPart = "rsbsa-{$slug}";
        } elseif ($request->filled('phone')) {
            $localPart = "phone-{$request->phone}";
        } else {
            $localPart = 'farmer-' . Str::lower(Str::random(10));
        }

        $email = "{$localPart}@crops.local";
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$localPart}-{$i}@crops.local";
            $i++;
        }

        return $email;
    }

    private function isPlaceholderEmail(?string $email): bool
    {
        return $email && Str::endsWith($email, '@crops.local');
    }
}