<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $is_active
 * @property string|null $icon_asset_key
 * @property string|null $icon_alt_text
 * @property string|null $image_asset_key
 * @property string|null $image_alt_text
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'code',
    'description',
    'is_active',
    'icon_asset_key',
    'icon_alt_text',
    'image_asset_key',
    'image_alt_text',
])]
class Sport extends Model
{
    /**
     * @var array<string, bool>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
