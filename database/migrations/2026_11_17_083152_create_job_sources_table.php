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
    Schema::create('job_sources', function (Blueprint $table) {
        $table->id();

        $table->string('company_name');
        $table->string('source_type');
        $table->string('board_token');
        $table->text('api_base_url');

        $table->boolean('is_active')->default(true);
        $table->timestamp('last_fetched_at')->nullable();

        $table->timestamps();

        $table->unique(['source_type', 'board_token']);
    });
}

public function down(): void
{
    Schema::dropIfExists('job_sources');
}
};
