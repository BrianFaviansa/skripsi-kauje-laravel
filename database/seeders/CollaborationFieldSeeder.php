<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CollaborationField;
use Illuminate\Support\Str;

class CollaborationFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            "Penelitian",
            "Mentoring",
            "Event",
            "Proyek",
            "Webinar",
            "Workshop",
        ];

        foreach ($fields as $field) {
            CollaborationField::firstOrCreate(
                ['name' => $field]
            );
        }
    }
}
