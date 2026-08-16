<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the canonical platform and vendor roles.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'code' => 'super_admin',
                'description' => 'Full platform administration role reserved for trusted operators.',
            ],
            [
                'name' => 'Admin Operations',
                'code' => 'admin_operations',
                'description' => 'Platform operations role for day-to-day marketplace administration.',
            ],
            [
                'name' => 'Admin Support',
                'code' => 'admin_support',
                'description' => 'Platform support role for authorized customer and vendor assistance.',
            ],
            [
                'name' => 'Admin Finance',
                'code' => 'admin_finance',
                'description' => 'Platform finance role for authorized payment, refund, and settlement work.',
            ],
            [
                'name' => 'Vendor Owner',
                'code' => 'vendor_owner',
                'description' => 'Vendor role with ownership-level authority for its assigned vendor.',
            ],
            [
                'name' => 'Vendor Manager',
                'code' => 'vendor_manager',
                'description' => 'Vendor role for managing authorized operations and staff scope.',
            ],
            [
                'name' => 'Vendor Staff',
                'code' => 'vendor_staff',
                'description' => 'Vendor role for assigned day-to-day operational work.',
            ],
            [
                'name' => 'Vendor Accountant',
                'code' => 'vendor_accountant',
                'description' => 'Vendor role for authorized financial reporting and accounting work.',
            ],
            [
                'name' => 'Customer',
                'code' => 'customer',
                'description' => 'Customer role for discovering and booking sports venues.',
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['code' => $role['code']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ],
            );
        }
    }
}
