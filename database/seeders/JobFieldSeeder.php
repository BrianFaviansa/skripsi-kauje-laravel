<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobField;
use Illuminate\Support\Str;

class JobFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            'Teknologi Informasi',
            'Pemasaran Digital',
            'Desain Grafis',
            'Keuangan & Akuntansi',
            'Sumber Daya Manusia (HR)',
            'Pendidikan',
            'Kesehatan',
            'Manufaktur & Teknik',
            'Penjualan & Pengembangan Bisnis',
            'Administrasi & Operasional',
        ];

        foreach ($fields as $field) {
            JobField::updateOrCreate(
                ['name' => $field],
                ['id' => Str::uuid()]
            );
        }
    }
}
