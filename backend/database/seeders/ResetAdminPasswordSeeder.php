<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('email', 'admin@cao.gov.ph')->first();
        if ($user) {
            $user->password = Hash::make('password');
            $user->save();
            $this->command->info('Admin password reset successfully.');
        } else {
            $this->command->warn('Admin user not found.');
        }
    }
}