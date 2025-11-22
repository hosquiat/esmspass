<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, json, encrypted
            $table->timestamps();
        });

        // Insert default settings
        DB::table('backup_settings')->insert([
            [
                'key' => 'filesystem_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'filesystem_retention_days',
                'value' => '30',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_drive_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_drive_folder_id',
                'value' => null,
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'google_drive_credentials',
                'value' => null,
                'type' => 'encrypted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'backup_schedule',
                'value' => 'daily',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'last_backup_at',
                'value' => null,
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
