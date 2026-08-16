<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Users\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $mobile_number
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property UserStatus $status
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'mobile_number', 'email', 'password', 'status'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * The roles assigned to the user.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * The vendor memberships for this user.
     *
     * @return HasMany<VendorMembership, $this>
     */
    public function vendorMemberships(): HasMany
    {
        return $this->hasMany(VendorMembership::class);
    }

    /**
     * The vendors this user has active memberships in.
     *
     * @return BelongsToMany<Vendor, $this>
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_memberships', 'user_id', 'vendor_id')
            ->select('vendors.*')
            ->where('vendor_memberships.status', 'active');
    }

    /**
     * Determine whether the user currently holds a role with the given stable code.
     */
    public function hasRole(string $roleCode): bool
    {
        return $roleCode !== ''
            && $this->roles()->where('roles.code', $roleCode)->exists();
    }

    /**
     * Determine whether any current role grants the given stable permission code.
     */
    public function hasPermission(string $permissionCode): bool
    {
        return $permissionCode !== ''
            && $this->roles()
                ->whereHas('permissions', function (Builder $query) use ($permissionCode): void {
                    $query->where('permissions.code', $permissionCode);
                })
                ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
