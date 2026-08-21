<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Seed the canonical platform amenity catalog.
     */
    public function run(): void
    {
        $amenities = [
            [
                'name' => 'Parking',
                'code' => 'parking',
                'description' => 'Vehicle parking is available for players and visitors.',
            ],
            [
                'name' => 'Washroom',
                'code' => 'washroom',
                'description' => 'On-site restroom facilities are available.',
            ],
            [
                'name' => 'Changing Room',
                'code' => 'changing_room',
                'description' => 'Dedicated changing space is available before and after play.',
            ],
            [
                'name' => 'Shower',
                'code' => 'shower',
                'description' => 'Shower facilities are available on-site.',
            ],
            [
                'name' => 'Locker',
                'code' => 'locker',
                'description' => 'Secure lockers are available for personal belongings.',
            ],
            [
                'name' => 'Floodlights',
                'code' => 'floodlights',
                'description' => 'Floodlighting supports evening and low-light play.',
            ],
        ];

        foreach ($amenities as $amenity) {
            Amenity::query()->updateOrCreate(
                ['code' => $amenity['code']],
                [
                    'name' => $amenity['name'],
                    'description' => $amenity['description'],
                ],
            );
        }
    }
}
