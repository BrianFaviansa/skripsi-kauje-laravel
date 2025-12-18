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
        Schema::create('jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('content');
            $table->string('company');
            $table->enum('job_type', ['LOKER', 'MAGANG']);
            $table->dateTime('open_from');
            $table->dateTime('open_until');
            $table->string('registration_link')->nullable();
            $table->string('image_url')->nullable();
            $table->foreignUuid('posted_by_id')->constrained('users');
            $table->foreignUuid('job_field_id')->constrained('job_fields');
            $table->foreignUuid('province_id')->constrained('provinces');
            $table->foreignUuid('city_id')->constrained('cities');
            $table->timestamps();

            $table->index('job_type');
            $table->index('province_id');
            $table->index('city_id');
            $table->index('job_field_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
