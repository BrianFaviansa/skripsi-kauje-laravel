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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nim')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('place_date_of_birth')->nullable();
            $table->string('phone_number')->unique();
            $table->integer('enrollment_year');
            $table->integer('graduation_year');
            $table->string('instance')->nullable();
            $table->string('position')->nullable();
            $table->string('verification_file_url');
            $table->string('profile_picture_url')->nullable();
            $table->text('refresh_token')->nullable();
            $table->enum('verification_status', ['PENDING', 'VERIFIED', 'REJECTED'])->default('PENDING');
            $table->foreignUuid('role_id')->constrained('roles');
            $table->foreignUuid('province_id')->constrained('provinces');
            $table->foreignUuid('city_id')->constrained('cities');
            $table->foreignUuid('faculty_id')->constrained('faculties');
            $table->foreignUuid('major_id')->constrained('majors');
            $table->timestamps();

            // Indexes
            $table->index('verification_status');
            $table->index('role_id');
            $table->index('province_id');
            $table->index('city_id');
            $table->index('faculty_id');
            $table->index('major_id');
            $table->index('created_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
