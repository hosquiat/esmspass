<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates a default admin user with email/password authentication.
     * It's safe to run multiple times - it won't create duplicates.
     */
    public function run(): void
    {
        $email = env('DEFAULT_ADMIN_EMAIL', 'admin@example.com');
        $password = env('DEFAULT_ADMIN_PASSWORD', 'changeme');

        // Only create if admin user doesn't exist
        if (!User::where('email', $email)->exists()) {
            User::create([
                'name' => 'Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            $this->command->info("✓ Admin user created: {$email}");
            $this->command->warn("  Default password: {$password}");
            $this->command->warn("  Please change the password after first login!");
        } else {
            $this->command->info("→ Admin user already exists: {$email}");
        }
    }
}
