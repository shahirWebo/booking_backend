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
                'icon_asset_key' => 'sports/icons/football.png',
                'icon_alt_text' => 'Football sport icon',
                'image_asset_key' => 'sports/images/football.png',
                'image_alt_text' => 'Football sport image',
            ],
            [
                'name' => 'Cricket',
                'code' => 'cricket',
                'description' => 'Standard cricket formats supported on eligible grounds and nets.',
                'is_active' => true,
                'icon_asset_key' => 'sports/icons/cricket.png',
                'icon_alt_text' => 'Cricket sport icon',
                'image_asset_key' => 'sports/images/cricket.png',
                'image_alt_text' => 'Cricket sport image',
            ],
            [
                'name' => 'Box Cricket',
                'code' => 'box_cricket',
                'description' => 'Compact-format cricket commonly played on enclosed turfs.',
                'is_active' => true,
                'icon_asset_key' => 'sports/icons/box_cricket.png',
                'icon_alt_text' => 'Box cricket sport icon',
                'image_asset_key' => 'sports/images/box_cricket.png',
                'image_alt_text' => 'Box cricket sport image',
            ],
            [
                'name' => 'Badminton',
                'code' => 'badminton',
                'description' => 'Indoor or covered badminton court bookings.',
                'is_active' => true,
                'icon_asset_key' => 'sports/icons/badminton.png',
                'icon_alt_text' => 'Badminton sport icon',
                'image_asset_key' => 'sports/images/badminton.png',
                'image_alt_text' => 'Badminton sport image',
            ],
            [
                'name' => 'Tennis',
                'code' => 'tennis',
                'description' => 'Singles or doubles tennis court bookings.',
                'is_active' => true,
                'icon_asset_key' => 'sports/icons/tennis.png',
                'icon_alt_text' => 'Tennis sport icon',
                'image_asset_key' => 'sports/images/tennis.png',
                'image_alt_text' => 'Tennis sport image',
            ],
        ];

        foreach ($sports as $sport) {
            Sport::query()->updateOrCreate(
                ['code' => $sport['code']],
                [
                    'name' => $sport['name'],
                    'description' => $sport['description'],
                    'is_active' => $sport['is_active'],
                    'icon_asset_key' => $sport['icon_asset_key'],
                    'icon_alt_text' => $sport['icon_alt_text'],
                    'image_asset_key' => $sport['image_asset_key'],
                    'image_alt_text' => $sport['image_alt_text'],
                ],
            );
        }
    }
}
