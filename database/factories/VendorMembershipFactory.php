<?php

namespace Database\Factories;

use App\Domain\Vendors\Enums\VendorMembershipStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorMembership>
 */
class VendorMembershipFactory extends Factory
{
    protected $model = VendorMembership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'user_id' => User::factory(),
            'role' => 'vendor_staff',
            'status' => VendorMembershipStatus::Active->value,
        ];
    }

    /**
     * State: owner role.
     */
    public function owner(): static
    {
        return $this->state(fn () => [
            'role' => 'vendor_owner',
        ]);
    }

    /**
     * State: manager role.
     */
    public function manager(): static
    {
        return $this->state(fn () => [
            'role' => 'vendor_manager',
        ]);
    }

    /**
     * State: accountant role.
     */
    public function accountant(): static
    {
        return $this->state(fn () => [
            'role' => 'vendor_accountant',
        ]);
    }

    /**
     * State: inactive membership.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => VendorMembershipStatus::Inactive->value,
        ]);
    }
}
