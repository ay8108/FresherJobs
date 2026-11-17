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
    Schema::create('job_posts', function (Blueprint $table) {
        $table->id();

        // Original job information
        $table->string('title');
        $table->string('company_name')->nullable();
        $table->text('description')->nullable();
        $table->string('location')->nullable();

        // Job details
        $table->string('employment_type')->nullable();
        $table->string('experience_level')->nullable();
        $table->string('salary')->nullable();

        // Source information
        $table->string('source_name');
        $table->text('source_url');
        $table->string('source_job_id')->nullable();

        // AI-generated information
        $table->string('ai_role')->nullable();
        $table->string('ai_city')->nullable();
        $table->decimal('ai_confidence', 5, 2)->nullable();
        $table->text('ai_raw_response')->nullable();

        // Duplicate tracking
        $table->string('dedup_hash', 64)->unique();

        // Dates
        $table->timestamp('posted_at')->nullable();
        $table->timestamp('last_seen_at')->nullable();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('job_posts');
}
};
