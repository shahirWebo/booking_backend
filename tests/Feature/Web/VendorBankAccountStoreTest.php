<?php

use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Models\VendorMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('an active vendor owner can store one encrypted bank account for a draft submission', function (): void {
    [$user, $vendor] = vendorOwnerWithDraft();

    $this->actingAs($user)
        ->post(route('vendor.onboarding.bank-accounts.store', $vendor), bankAccountPayload())
        ->assertRedirect(route('vendor.onboarding.show'));

    $account = VendorBankAccount::query()->sole();

    expect($account->vendor_id)->toBe($vendor->id)
        ->and($account->account_holder_name)->toBe('Acme Sports Private Limited')
        ->and($account->bank_name)->toBe('Example Bank')
        ->and($account->account_number_encrypted)->toBe('1234567890123456')
        ->and($account->account_number_last_four)->toBe('3456')
        ->and($account->routing_code_encrypted)->toBe('EXAM0000123')
        ->and($account->country_code)->toBe('IN')
        ->and($account->currency)->toBe('INR')
        ->and($account->submission_version)->toBe(1)
        ->and($account->status)->toBe('active');

    $stored = DB::table('vendor_bank_accounts')->find($account->id);

    expect($stored->account_number_encrypted)->not->toBe('1234567890123456')
        ->and($stored->routing_code_encrypted)->not->toBe('EXAM0000123');
});

test('the onboarding page returns only a masked bank account summary', function (): void {
    [$user, $vendor] = vendorOwnerWithDraft();

    VendorBankAccount::query()->create([
        'vendor_id' => $vendor->id,
        'account_holder_name' => 'Acme Sports Private Limited',
        'bank_name' => 'Example Bank',
        'account_number_encrypted' => '1234567890123456',
        'account_number_last_four' => '3456',
        'routing_code_encrypted' => 'EXAM0000123',
        'country_code' => 'IN',
        'currency' => 'INR',
    ]);

    $this->actingAs($user)
        ->get(route('vendor.onboarding.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('bankAccounts.0.bank_name', 'Example Bank')
            ->where('bankAccounts.0.account_number_last_four', '3456')
            ->missing('bankAccounts.0.account_number_encrypted')
            ->missing('bankAccounts.0.routing_code_encrypted')
            ->missing('bankAccounts.0.account_holder_name'),
        );
});

test('vendor bank account details require a valid Indian account number and IFSC', function (): void {
    [$user, $vendor] = vendorOwnerWithDraft();

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->post(route('vendor.onboarding.bank-accounts.store', $vendor), [
            ...bankAccountPayload(),
            'account_number' => '1234',
            'routing_code' => 'not-an-ifsc',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors(['account_number', 'routing_code']);

    expect(VendorBankAccount::query()->count())->toBe(0);
});

test('a vendor submission cannot have more than one active bank account', function (): void {
    [$user, $vendor] = vendorOwnerWithDraft();

    VendorBankAccount::query()->create([
        'vendor_id' => $vendor->id,
        'account_holder_name' => 'Acme Sports Private Limited',
        'bank_name' => 'Example Bank',
        'account_number_encrypted' => '1234567890123456',
        'account_number_last_four' => '3456',
        'routing_code_encrypted' => 'EXAM0000123',
        'country_code' => 'IN',
        'currency' => 'INR',
    ]);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->post(route('vendor.onboarding.bank-accounts.store', $vendor), [
            ...bankAccountPayload(),
            'account_number' => '9876543210987654',
        ])
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('bank_account');

    expect(VendorBankAccount::query()->count())->toBe(1);
});

test('a user cannot store a bank account for another vendor', function (): void {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = Vendor::factory()->create();

    $this->actingAs($user)
        ->post(route('vendor.onboarding.bank-accounts.store', $vendor), bankAccountPayload())
        ->assertForbidden();

    expect(VendorBankAccount::query()->count())->toBe(0);
});

test('vendor bank account details cannot be stored after the draft state', function (): void {
    [$user, $vendor] = vendorOwnerWithDraft();
    $vendor->update(['status' => 'approved']);

    $this->actingAs($user)
        ->from(route('vendor.onboarding.show'))
        ->post(route('vendor.onboarding.bank-accounts.store', $vendor), bankAccountPayload())
        ->assertRedirect(route('vendor.onboarding.show'))
        ->assertSessionHasErrors('vendor');

    expect(VendorBankAccount::query()->count())->toBe(0);
});

/**
 * @return array{User, Vendor}
 */
function vendorOwnerWithDraft(): array
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $vendor = Vendor::factory()->create();

    VendorMembership::query()->create([
        'vendor_id' => $vendor->id,
        'user_id' => $user->id,
        'role' => 'vendor_owner',
        'status' => 'active',
    ]);

    return [$user, $vendor];
}

/**
 * @return array{account_holder_name: string, bank_name: string, account_number: string, routing_code: string}
 */
function bankAccountPayload(): array
{
    return [
        'account_holder_name' => '  Acme Sports Private Limited  ',
        'bank_name' => '  Example Bank  ',
        'account_number' => '1234567890123456',
        'routing_code' => 'exam0000123',
    ];
}
