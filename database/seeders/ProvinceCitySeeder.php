<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\City;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class ProvinceCitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = Http::withoutVerifying()->timeout(20)->get('https://wilayah.id/api/provinces.json');

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch provinces');
        }

        foreach ($response->json('data') as $province) {
            $provinceModel = Province::updateOrCreate(
                ['name' => $province['name']],
                ['id' => Str::uuid()]
            );

            $cities = Http::withoutVerifying()->timeout(20)
                ->get("https://wilayah.id/api/regencies/{$province['code']}.json")
                ->json('data');

            foreach ($cities as $city) {
                City::updateOrCreate(
                    ['name' => $city['name']],
                    [
                        'id' => Str::uuid(),
                        'province_id' => $provinceModel->id,
                    ]
                );
            }

            usleep(500_000);
        }
    }
}
