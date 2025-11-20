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
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // password, contact, code, note
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('tags')->nullable(); // JSON array of tags
            $table->string('group')->nullable(); // Client, project, or system name
            $table->text('data')->nullable(); // Type-specific fields stored as encrypted text
            $table->boolean('is_archived')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('type');
            $table->index('is_archived');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
