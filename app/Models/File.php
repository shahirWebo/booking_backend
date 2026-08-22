<?php

namespace App\Models;

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property FilePurpose $purpose
 * @property FileStatus $status
 * @property int|null $created_by_user_id
 * @property int|null $vendor_id
 * @property string $logical_disk
 * @property string $object_key
 * @property string|null $original_name
 * @property string|null $detected_mime_type
 * @property string|null $canonical_extension
 * @property int|null $size_bytes
 * @property string|null $checksum_sha256
 * @property Carbon|null $uploaded_at
 * @property Carbon|null $scanned_at
 * @property Carbon|null $ready_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $deleted_at
 * @property string|null $rejection_code
 * @property-read User|null $createdBy
 * @property-read Vendor|null $vendor
 */
#[Fillable([
    'purpose',
    'status',
    'created_by_user_id',
    'vendor_id',
    'logical_disk',
    'object_key',
    'original_name',
    'detected_mime_type',
    'canonical_extension',
    'size_bytes',
    'checksum_sha256',
    'uploaded_at',
    'scanned_at',
    'ready_at',
    'rejected_at',
    'deleted_at',
    'rejection_code',
])]
class File extends Model
{
    protected function casts(): array
    {
        return [
            'purpose' => FilePurpose::class,
            'status' => FileStatus::class,
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
            'scanned_at' => 'datetime',
            'ready_at' => 'datetime',
            'rejected_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<LocationImage, $this>
     */
    public function locationImages(): HasMany
    {
        return $this->hasMany(LocationImage::class);
    }
}
