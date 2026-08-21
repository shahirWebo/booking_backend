<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $profile_image_path
 * @property array<int, int>|null $preferred_sport_ids
 * @property string|null $default_location_label
 * @property bool $email_notifications_enabled
 * @property bool $sms_notifications_enabled
 * @property bool $marketing_notifications_enabled
 * @property Carbon|null $account_deletion_requested_at
 * @property string|null $account_deletion_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'profile_image_path',
    'preferred_sport_ids',
    'default_location_label',
    'email_notifications_enabled',
    'sms_notifications_enabled',
    'marketing_notifications_enabled',
    'account_deletion_requested_at',
    'account_deletion_reason',
])]
class CustomerProfile extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'preferred_sport_ids' => '[]',
        'email_notifications_enabled' => true,
        'sms_notifications_enabled' => true,
        'marketing_notifications_enabled' => false,
    ];

    /**
     * The user that owns this customer profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profileImageUrl(): ?string
    {
        if ($this->profile_image_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_image_path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_sport_ids' => 'array',
            'email_notifications_enabled' => 'boolean',
            'sms_notifications_enabled' => 'boolean',
            'marketing_notifications_enabled' => 'boolean',
            'account_deletion_requested_at' => 'immutable_datetime',
        ];
    }
}
