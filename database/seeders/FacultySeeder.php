<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;
use Illuminate\Support\Str;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculties = [
            'Ilmu Komputer',
            'MIPA',
            'Pertanian',
            'Teknologi Pertanian',
            'Ilmu Sosial Ilmu Politik',
            'Ilmu Budaya',
            'Ekonomi dan Bisnis',
            'Hukum',
            'KIP',
            'Kedokteran',
            'Kedokteran Gigi',
            'Keperawatan',
            'Kesehatan Masyarakat',
            'Farmasi',
            'Teknik',
        ];

        foreach ($faculties as $faculty) {
            Faculty::firstOrCreate(
                ['name' => $faculty]
            );
        }
    }
}
