<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Major;
use App\Models\Faculty;
use Illuminate\Support\Str;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $majors = [
            ['name' => 'Sistem Informasi', 'faculty' => 'Ilmu Komputer'],
            ['name' => 'Teknologi Informasi', 'faculty' => 'Ilmu Komputer'],
            ['name' => 'Informatika', 'faculty' => 'Ilmu Komputer'],
            ['name' => 'Matematika', 'faculty' => 'MIPA'],
            ['name' => 'Fisika', 'faculty' => 'MIPA'],
            ['name' => 'Kimia', 'faculty' => 'MIPA'],
            ['name' => 'Biologi', 'faculty' => 'MIPA'],
            ['name' => 'Agribisnis', 'faculty' => 'Pertanian'],
            ['name' => 'Agroteknologi', 'faculty' => 'Pertanian'],
            ['name' => 'Agronomi', 'faculty' => 'Pertanian'],
            ['name' => 'Proteksi Tanaman', 'faculty' => 'Pertanian'],
            ['name' => 'Ilmu Tanah', 'faculty' => 'Pertanian'],
            ['name' => 'Penyuluhan Pertanian', 'faculty' => 'Pertanian'],
            ['name' => 'Peternakan', 'faculty' => 'Pertanian'],
            ['name' => 'Ilmu Pertanian', 'faculty' => 'Pertanian'],
            ['name' => 'Teknologi Hasil Pertanian', 'faculty' => 'Teknologi Pertanian'],
            ['name' => 'Teknik Pertanian', 'faculty' => 'Teknologi Pertanian'],
            ['name' => 'Teknologi Industri Pertanian', 'faculty' => 'Teknologi Pertanian'],
            ['name' => 'Ilmu Administrasi', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Administrasi Negara', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Administrasi Bisnis', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Kesejahteraan Sosial', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Hubungan Internasional', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Sosiologi', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Perpajakan', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Usaha Perjalanan Wisata', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'D3 Usaha Perjalanan Wisata', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'D3 Perpajakan', 'faculty' => 'Ilmu Sosial Ilmu Politik'],
            ['name' => 'Sastra Indonesia', 'faculty' => 'Ilmu Budaya'],
            ['name' => 'Sastra Inggris', 'faculty' => 'Ilmu Budaya'],
            ['name' => 'Ilmu Sejarah', 'faculty' => 'Ilmu Budaya'],
            ['name' => 'Film dan Televisi', 'faculty' => 'Ilmu Budaya'],
            ['name' => 'Ekonomi Pembangunan', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'Manajemen', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'Akuntansi', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'Ekonomi Syariah', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'D3 Administrasi Keuangan', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'D3 Kesekretariatan', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'D3 Manajemen Perusahaan', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'D3 Akuntansi', 'faculty' => 'Ekonomi dan Bisnis'],
            ['name' => 'Ilmu Hukum', 'faculty' => 'Hukum'],
            ['name' => 'Pendidikan Bahasa dan Sastra Indonesia', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Bahasa Inggris', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Matematika', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Biologi', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Fisika', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Kimia', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Guru Sekolah Dasar', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Jasmani, Kesehatan dan Rekreasi', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Ekonomi', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Geografi', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Sejarah', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Pancasila dan Kewarganegaraan', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Luar Sekolah', 'faculty' => 'KIP'],
            ['name' => 'Administrasi Pendidikan', 'faculty' => 'KIP'],
            ['name' => 'Program Bimbingan & Konseling', 'faculty' => 'KIP'],
            ['name' => 'Pendidikan Dokter', 'faculty' => 'Kedokteran'],
            ['name' => 'Profesi Dokter', 'faculty' => 'Kedokteran'],
            ['name' => 'Pendidikan Dokter Gigi', 'faculty' => 'Kedokteran Gigi'],
            ['name' => 'Profesi Dokter Gigi', 'faculty' => 'Kedokteran Gigi'],
            ['name' => 'Keperawatan', 'faculty' => 'Keperawatan'],
            ['name' => 'Profesi Ners', 'faculty' => 'Keperawatan'],
            ['name' => 'D3 Keperawatan', 'faculty' => 'Keperawatan'],
            ['name' => 'Kesehatan Masyarakat', 'faculty' => 'Kesehatan Masyarakat'],
            [
                'name' => 'Profesi Ahli Kesehatan Masyarakat',
                'faculty' => 'Kesehatan Masyarakat',
            ],
            ['name' => 'Gizi', 'faculty' => 'Kesehatan Masyarakat'],
            ['name' => 'Farmasi', 'faculty' => 'Farmasi'],
            ['name' => 'Profesi Apoteker', 'faculty' => 'Farmasi'],
            ['name' => 'Teknik Mesin', 'faculty' => 'Teknik'],
            ['name' => 'Teknik Elektro', 'faculty' => 'Teknik'],
            ['name' => 'Teknik Sipil', 'faculty' => 'Teknik'],
            ['name' => 'Perencanaan Wilayah dan Kota', 'faculty' => 'Teknik'],
            ['name' => 'Teknik Kimia', 'faculty' => 'Teknik'],
            ['name' => 'Teknik Lingkungan', 'faculty' => 'Teknik'],
            ['name' => 'Teknik Konstruksi Perkapalan', 'faculty' => 'Teknik'],
            ['name' => 'Teknik Pertambangan', 'faculty' => 'Teknik'],
            ['name' => 'Teknik Perminyakan', 'faculty' => 'Teknik'],
            ['name' => 'D3 Teknik Elektronika', 'faculty' => 'Teknik'],
            ['name' => 'D3 Teknik Mesin', 'faculty' => 'Teknik'],
            ['name' => 'D3 Teknik Sipil', 'faculty' => 'Teknik'],
        ];

        $facultyMap = Faculty::pluck('id', 'name');

        foreach ($majors as $major) {
            if (! isset($facultyMap[$major['faculty']])) {
                continue;
            }

            Major::firstOrCreate(
                ['name' => $major['name'], 'faculty_id' => $facultyMap[$major['faculty']]]
            );
        }
    }
}
