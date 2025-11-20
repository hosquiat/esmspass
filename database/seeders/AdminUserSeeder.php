<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates admin users based on the ADMIN_EMAILS environment variable.
     * It's safe to run multiple times - it won't create duplicates.
     */
    public function run(): void
    {
        $adminEmailsString = env('ADMIN_EMAILS', '');

        if (empty($adminEmailsString)) {
            $this->command->warn('No admin emails configured in ADMIN_EMAILS environment variable.');
            $this->command->info('Add admin emails to .env file: ADMIN_EMAILS=admin@example.com,admin2@example.com');
            return;
        }

        $adminEmails = explode(',', $adminEmailsString);
        $adminEmails = array_map('trim', $adminEmails);
        $adminEmails = array_filter($adminEmails);

        foreach ($adminEmails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->command->warn("Skipping invalid email: {$email}");
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => explode('@', $email)[0],
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ]
            );

            if ($user->wasRecentlyCreated) {
                $this->command->info("✓ Created admin user: {$email}");
            } else {
                // Update role to admin if user already exists
                if ($user->role !== 'admin') {
                    $user->update(['role' => 'admin']);
                    $this->command->info("✓ Updated user to admin: {$email}");
                } else {
                    $this->command->info("→ Admin user already exists: {$email}");
                }
            }
        }

        $this->command->info("\n" . count($adminEmails) . " admin email(s) processed.");
        $this->command->warn("\nNote: These users must still authenticate via Google OAuth to access the application.");
    }
}
