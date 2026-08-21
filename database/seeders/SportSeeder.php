<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    /**
     * Seed the canonical platform sport catalog.
     */
    public function run(): void
    {
        $sports = [
            [
                'name' => 'Football',
                'code' => 'football',
                'description' => 'Association football for full-size or small-sided turf play.',
                'is_active' => true,
            ],
            [
                'name' => 'Cricket',
                'code' => 'cricket',
                'description' => 'Standard cricket formats supported on eligible grounds and nets.',
                'is_active' => true,
            ],
            [
                'name' => 'Box Cricket',
                'code' => 'box_cricket',
                'description' => 'Compact-format cricket commonly played on enclosed turfs.',
                'is_active' => true,
            ],
            [
                'name' => 'Badminton',
                'code' => 'badminton',
                'description' => 'Indoor or covered badminton court bookings.',
                'is_active' => true,
            ],
            [
                'name' => 'Tennis',
                'code' => 'tennis',
                'description' => 'Singles or doubles tennis court bookings.',
                'is_active' => true,
            ],
        ];

        foreach ($sports as $sport) {
            Sport::query()->updateOrCreate(
                ['code' => $sport['code']],
                [
                    'name' => $sport['name'],
                    'description' => $sport['description'],
                    'is_active' => $sport['is_active'],
                ],
            );
        }
    }
}
