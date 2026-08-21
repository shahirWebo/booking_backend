<?php

namespace App\Http\Middleware;

use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $this->resolveUserPayload($user),
                'roles' => fn (): array => $this->resolveRoleCodes($user),
                'permissions' => fn (): array => $this->resolvePermissionCodes($user),
                'preferredSurface' => fn (): ?string => $this->resolvePreferredSurface($user),
                'sessionMode' => $user ? 'cookie' : 'guest',
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function resolveRoleCodes(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return $user->roles()
            ->orderBy('roles.code')
            ->pluck('roles.code')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolvePermissionCodes(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return Role::query()
            ->select('permissions.code')
            ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->join('role_permissions', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('user_roles.user_id', $user->getKey())
            ->distinct()
            ->orderBy('permissions.code')
            ->pluck('permissions.code')
            ->all();
    }

    private function resolvePreferredSurface(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $roleCodes = $this->resolveRoleCodes($user);

        if ($this->containsRolePrefix($roleCodes, 'super_admin')
            || $this->containsRolePrefix($roleCodes, 'admin_')) {
            return 'admin';
        }

        if ($this->containsRolePrefix($roleCodes, 'vendor_')) {
            return 'vendor';
        }

        if (in_array('customer', $roleCodes, true)) {
            return 'customer';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveUserPayload(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        /** @var CustomerProfile|null $profile */
        $profile = $user->relationLoaded('customerProfile')
            ? $user->customerProfile
            : $user->customerProfile()->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile_number' => $user->mobile_number,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'avatar' => $profile?->profileImageUrl(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, string>  $roleCodes
     */
    private function containsRolePrefix(array $roleCodes, string $prefix): bool
    {
        foreach ($roleCodes as $roleCode) {
            if (str_starts_with($roleCode, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
