<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\Province;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required foreign keys
        $adminRole = Role::where('name', 'Admin')->first();
        $alumniRole = Role::where('name', 'Alumni')->first();
        $province = Province::first();
        $city = City::first();
        $faculty = Faculty::first();
        $major = Major::first();

        if (! $adminRole || ! $alumniRole || ! $province || ! $city || ! $faculty || ! $major) {
            $this->command->error('Required data not found. Please run other seeders first.');
            $this->command->info('Run: php artisan db:seed');

            return;
        }

        // Create Admin test user with explicitly hashed password
        User::updateOrCreate(
            ['nim' => '202410101014'],
            [
                'name' => 'Admin Test',
                'email' => 'admin@test.com',
                'password' => Hash::make('password123'), // Explicitly hash
                'phone_number' => '081234567890',
                'enrollment_year' => 2020,
                'graduation_year' => 2024,
                'verification_status' => 'VERIFIED',
                'verification_file_url' => '/uploads/verification/test.pdf',
                'role_id' => $adminRole->id,
                'province_id' => $province->id,
                'city_id' => $city->id,
                'faculty_id' => $faculty->id,
                'major_id' => $major->id,
            ]
        );

        $this->command->info('Test user created successfully!');
        $this->command->info('NIM: 202410101014');
        $this->command->info('Password: password123');
    }
}
