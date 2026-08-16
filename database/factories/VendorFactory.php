<?php

namespace Database\Factories;

use App\Domain\Vendors\Enums\VendorStatus;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => VendorStatus::Draft->value,
        ];
    }

    /**
     * State: vendor is approved.
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => VendorStatus::Approved->value,
        ]);
    }

    /**
     * State: vendor is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => VendorStatus::Suspended->value,
        ]);
    }

    /**
     * State: vendor is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => VendorStatus::Inactive->value,
        ]);
    }
}
