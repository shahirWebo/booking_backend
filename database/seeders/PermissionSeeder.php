<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PermissionSeeder extends Seeder
{
    /** @var array<string, array{string, string}> */
    private const PERMISSIONS = [
        'access_admin' => ['Access Admin', 'Access protected platform administration interfaces.'],
        'manage_roles_and_permissions' => ['Manage Roles and Permissions', 'Manage platform role and permission assignments.'],
        'view_customers' => ['View Customers', 'View authorized customer account and activity information.'],
        'manage_customer_status' => ['Manage Customer Status', 'Block or restore customer account access.'],
        'view_vendors' => ['View Vendors', 'View authorized vendor, onboarding, and operational information.'],
        'review_vendors' => ['Review Vendors', 'Approve or reject submitted vendor applications.'],
        'suspend_vendors' => ['Suspend Vendors', 'Suspend vendor commerce access with an authorized reason.'],
        'reactivate_vendors' => ['Reactivate Vendors', 'Reactivate an eligible suspended vendor.'],
        'view_all_turfs' => ['View All Turfs', 'View turf records across vendors for platform operations.'],
        'manage_sports' => ['Manage Sports', 'Create, update, and delete platform sport master data.'],
        'manage_amenities' => ['Manage Amenities', 'Create, update, and delete platform amenity master data.'],
        'manage_turf_status' => ['Manage Turf Status', 'Activate or deactivate turfs through platform operations.'],
        'view_all_bookings' => ['View All Bookings', 'View authorized bookings across the platform.'],
        'cancel_any_booking' => ['Cancel Any Booking', 'Cancel an eligible booking through an audited platform workflow.'],
        'reschedule_any_booking' => ['Reschedule Any Booking', 'Reschedule an eligible booking through an audited platform workflow.'],
        'add_booking_notes' => ['Add Booking Notes', 'Add authorized internal notes to booking records.'],
        'view_all_payments' => ['View All Payments', 'View platform-safe payment and refund information.'],
        'manage_refunds' => ['Manage Refunds', 'Approve or initiate authorized refund workflows.'],
        'view_platform_finance' => ['View Platform Finance', 'View platform financial summaries and transaction records.'],
        'manage_commissions' => ['Manage Commissions', 'Manage platform and vendor commission configuration.'],
        'manage_settlements' => ['Manage Settlements', 'Create and progress authorized vendor settlements.'],
        'reconcile_payments' => ['Reconcile Payments', 'Perform controlled payment and refund reconciliation work.'],
        'manage_support_tickets' => ['Manage Support Tickets', 'Assign, reply to, and resolve support tickets.'],
        'moderate_reviews' => ['Moderate Reviews', 'Moderate reported customer reviews.'],
        'manage_platform_coupons' => ['Manage Platform Coupons', 'Manage platform-funded coupons and promotions.'],
        'view_platform_reports' => ['View Platform Reports', 'View authorized platform dashboards and reports.'],
        'view_audit_logs' => ['View Audit Logs', 'Search authorized platform audit history.'],
        'manage_system_settings' => ['Manage System Settings', 'Manage protected platform configuration.'],
        'manage_notifications' => ['Manage Notifications', 'Manage protected notification templates and operations.'],
        'access_vendor_portal' => ['Access Vendor Portal', 'Access the vendor application for an active membership.'],
        'manage_vendor_profile' => ['Manage Vendor Profile', 'Manage permitted fields for the assigned vendor.'],
        'manage_vendor_staff' => ['Manage Vendor Staff', 'Manage staff within server-approved role and scope limits.'],
        'manage_locations' => ['Manage Locations', 'Manage locations within the assigned vendor and staff scope.'],
        'manage_turfs' => ['Manage Turfs', 'Manage turfs within the assigned vendor and staff scope.'],
        'manage_availability' => ['Manage Availability', 'Manage operating availability and slot blocks within scope.'],
        'manage_pricing' => ['Manage Pricing', 'Manage turf pricing within the assigned vendor scope.'],
        'view_vendor_bookings' => ['View Vendor Bookings', 'View bookings within the assigned vendor and location scope.'],
        'create_walk_in_bookings' => ['Create Walk-In Bookings', 'Create authorized manual bookings within scope.'],
        'cancel_vendor_bookings' => ['Cancel Vendor Bookings', 'Cancel eligible bookings within the assigned scope.'],
        'reschedule_vendor_bookings' => ['Reschedule Vendor Bookings', 'Reschedule eligible bookings within the assigned scope.'],
        'check_in_bookings' => ['Check In Bookings', 'Check in eligible bookings within the assigned scope.'],
        'complete_bookings' => ['Complete Bookings', 'Complete eligible bookings within the assigned scope.'],
        'mark_no_show' => ['Mark Booking No-Show', 'Mark eligible bookings as no-show within the assigned scope.'],
        'record_offline_payments' => ['Record Offline Payments', 'Record authorized cash or external payment markers.'],
        'manage_vendor_coupons' => ['Manage Vendor Coupons', 'Manage vendor-funded coupons within the assigned scope.'],
        'reply_to_reviews' => ['Reply to Reviews', 'Reply to reviews for the assigned vendor.'],
        'view_vendor_finance' => ['View Vendor Finance', 'View financial records for the assigned vendor.'],
        'view_vendor_settlements' => ['View Vendor Settlements', 'View settlement records for the assigned vendor.'],
        'view_vendor_reports' => ['View Vendor Reports', 'View and export reports for the assigned vendor scope.'],
    ];

    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'super_admin' => self::ALL_PERMISSION_CODES,
        'admin_operations' => [
            'access_admin',
            'view_customers',
            'manage_customer_status',
            'view_vendors',
            'review_vendors',
            'suspend_vendors',
            'reactivate_vendors',
            'view_all_turfs',
            'manage_sports',
            'manage_amenities',
            'manage_turf_status',
            'view_all_bookings',
            'cancel_any_booking',
            'reschedule_any_booking',
            'add_booking_notes',
            'view_all_payments',
            'manage_support_tickets',
            'moderate_reviews',
            'manage_platform_coupons',
            'view_platform_reports',
            'view_audit_logs',
            'manage_notifications',
        ],
        'admin_support' => [
            'access_admin',
            'view_customers',
            'view_vendors',
            'view_all_turfs',
            'view_all_bookings',
            'add_booking_notes',
            'view_all_payments',
            'manage_support_tickets',
        ],
        'admin_finance' => [
            'access_admin',
            'view_customers',
            'view_vendors',
            'view_all_bookings',
            'view_all_payments',
            'manage_refunds',
            'view_platform_finance',
            'manage_commissions',
            'manage_settlements',
            'reconcile_payments',
            'view_platform_reports',
        ],
        'vendor_owner' => self::ALL_VENDOR_PERMISSION_CODES,
        'vendor_manager' => [
            'access_vendor_portal',
            'manage_vendor_profile',
            'manage_vendor_staff',
            'manage_locations',
            'manage_turfs',
            'manage_availability',
            'manage_pricing',
            'view_vendor_bookings',
            'create_walk_in_bookings',
            'cancel_vendor_bookings',
            'reschedule_vendor_bookings',
            'check_in_bookings',
            'complete_bookings',
            'mark_no_show',
            'record_offline_payments',
            'manage_vendor_coupons',
            'reply_to_reviews',
            'view_vendor_reports',
        ],
        'vendor_staff' => [
            'access_vendor_portal',
            'manage_availability',
            'view_vendor_bookings',
            'create_walk_in_bookings',
            'cancel_vendor_bookings',
            'reschedule_vendor_bookings',
            'check_in_bookings',
            'complete_bookings',
            'mark_no_show',
            'record_offline_payments',
        ],
        'vendor_accountant' => [
            'access_vendor_portal',
            'view_vendor_bookings',
            'view_vendor_finance',
            'view_vendor_settlements',
            'view_vendor_reports',
        ],
        'customer' => [],
    ];

    private const ALL_PERMISSION_CODES = [
        'access_admin',
        'manage_roles_and_permissions',
        'view_customers',
        'manage_customer_status',
        'view_vendors',
        'review_vendors',
        'suspend_vendors',
        'reactivate_vendors',
        'view_all_turfs',
        'manage_sports',
        'manage_amenities',
        'manage_turf_status',
        'view_all_bookings',
        'cancel_any_booking',
        'reschedule_any_booking',
        'add_booking_notes',
        'view_all_payments',
        'manage_refunds',
        'view_platform_finance',
        'manage_commissions',
        'manage_settlements',
        'reconcile_payments',
        'manage_support_tickets',
        'moderate_reviews',
        'manage_platform_coupons',
        'view_platform_reports',
        'view_audit_logs',
        'manage_system_settings',
        'manage_notifications',
        'access_vendor_portal',
        'manage_vendor_profile',
        'manage_vendor_staff',
        'manage_locations',
        'manage_turfs',
        'manage_availability',
        'manage_pricing',
        'view_vendor_bookings',
        'create_walk_in_bookings',
        'cancel_vendor_bookings',
        'reschedule_vendor_bookings',
        'check_in_bookings',
        'complete_bookings',
        'mark_no_show',
        'record_offline_payments',
        'manage_vendor_coupons',
        'reply_to_reviews',
        'view_vendor_finance',
        'view_vendor_settlements',
        'view_vendor_reports',
    ];

    private const ALL_VENDOR_PERMISSION_CODES = [
        'access_vendor_portal',
        'manage_vendor_profile',
        'manage_vendor_staff',
        'manage_locations',
        'manage_turfs',
        'manage_availability',
        'manage_pricing',
        'view_vendor_bookings',
        'create_walk_in_bookings',
        'cancel_vendor_bookings',
        'reschedule_vendor_bookings',
        'check_in_bookings',
        'complete_bookings',
        'mark_no_show',
        'record_offline_payments',
        'manage_vendor_coupons',
        'reply_to_reviews',
        'view_vendor_finance',
        'view_vendor_settlements',
        'view_vendor_reports',
    ];

    /**
     * Seed the canonical permission catalog and default role grants.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $permissionIds = [];

            foreach (self::PERMISSIONS as $code => [$name, $description]) {
                $permission = Permission::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'description' => $description,
                    ],
                );

                $permissionIds[$code] = $permission->id;
            }

            foreach (self::ROLE_PERMISSIONS as $roleCode => $permissionCodes) {
                $role = Role::query()->where('code', $roleCode)->firstOrFail();
                $role->permissions()->sync(
                    array_map(
                        static function (string $permissionCode) use ($permissionIds): int {
                            if (! isset($permissionIds[$permissionCode])) {
                                throw new LogicException("Unknown canonical permission [{$permissionCode}].");
                            }

                            return $permissionIds[$permissionCode];
                        },
                        $permissionCodes,
                    ),
                );
            }
        });
    }
}
