<?php

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('vendor onboarding tables store versioned private evidence and lifecycle history', function () {
    expect(Schema::hasColumns('vendors', [
        'legal_name', 'display_name', 'legal_entity_type', 'primary_contact_name',
        'primary_contact_email', 'primary_contact_mobile_number', 'is_gst_registered', 'gstin',
        'submission_version',
    ]))->toBeTrue();
    expect(Schema::hasColumns('vendor_documents', [
        'vendor_id', 'file_id', 'document_type', 'submission_version', 'status',
    ]))->toBeTrue();
    expect(Schema::hasColumns('vendor_bank_accounts', [
        'vendor_id', 'account_number_encrypted', 'account_number_last_four', 'routing_code_encrypted',
        'submission_version', 'status',
    ]))->toBeTrue();
    expect(Schema::hasColumns('vendor_status_histories', [
        'vendor_id', 'actor_user_id', 'sequence', 'from_status', 'to_status', 'correlation_id', 'transitioned_at',
    ]))->toBeTrue();

    $vendor = Vendor::factory()->create();
    $actor = User::factory()->create();

    DB::table('vendor_documents')->insert([
        'vendor_id' => $vendor->id,
        'document_type' => 'gst_registration',
        'submission_version' => 1,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('vendor_bank_accounts')->insert([
        'vendor_id' => $vendor->id,
        'account_holder_name' => 'Court Club Pvt Ltd',
        'bank_name' => 'Example Bank',
        'account_number_encrypted' => 'encrypted-account-number',
        'account_number_last_four' => '1234',
        'country_code' => 'IN',
        'currency' => 'INR',
        'submission_version' => 1,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('vendor_status_histories')->insert([
        'vendor_id' => $vendor->id,
        'actor_user_id' => $actor->id,
        'sequence' => 1,
        'to_status' => 'draft',
        'transitioned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('vendor_documents')->where('vendor_id', $vendor->id)->exists())->toBeTrue();
    expect(DB::table('vendor_bank_accounts')->where('vendor_id', $vendor->id)->exists())->toBeTrue();
    expect(DB::table('vendor_status_histories')->where('vendor_id', $vendor->id)->exists())->toBeTrue();
});

test('vendor onboarding evidence and lifecycle sequences are unique within a submission or vendor', function () {
    $vendor = Vendor::factory()->create();

    $document = [
        'vendor_id' => $vendor->id,
        'document_type' => 'gst_registration',
        'submission_version' => 1,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('vendor_documents')->insert($document);

    expect(fn () => DB::table('vendor_documents')->insert($document))->toThrow(QueryException::class);

    $history = [
        'vendor_id' => $vendor->id,
        'sequence' => 1,
        'to_status' => 'draft',
        'transitioned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('vendor_status_histories')->insert($history);

    expect(fn () => DB::table('vendor_status_histories')->insert($history))->toThrow(QueryException::class);
});

test('vendor onboarding tables provide their lifecycle lookup indexes', function () {
    $indexesFor = static fn (string $table): array => collect(DB::select("PRAGMA index_list('{$table}')"))
        ->pluck('name')
        ->all();

    expect($indexesFor('vendors'))->toContain('vendors_status_updated_at_index');
    expect($indexesFor('vendor_documents'))
        ->toContain('vendor_documents_vendor_status_index')
        ->toContain('vendor_documents_file_id_index');
    expect($indexesFor('vendor_bank_accounts'))->toContain('vendor_bank_accounts_vendor_status_index');
    expect($indexesFor('vendor_status_histories'))
        ->toContain('vendor_status_histories_vendor_transitioned_index')
        ->toContain('vendor_status_histories_correlation_id_index');
});

test('vendor lifecycle history is retained when an actor account is deleted', function () {
    $vendor = Vendor::factory()->create();
    $actor = User::factory()->create();

    DB::table('vendor_status_histories')->insert([
        'vendor_id' => $vendor->id,
        'actor_user_id' => $actor->id,
        'sequence' => 1,
        'to_status' => 'draft',
        'transitioned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $actor->delete();

    expect(DB::table('vendor_status_histories')->where('vendor_id', $vendor->id)->value('actor_user_id'))->toBeNull();
});

test('vendor bank account credentials are encrypted by the model', function () {
    $vendor = Vendor::factory()->create();

    $account = VendorBankAccount::query()->create([
        'vendor_id' => $vendor->id,
        'account_holder_name' => 'Court Club Pvt Ltd',
        'bank_name' => 'Example Bank',
        'account_number_encrypted' => '1234567890123456',
        'account_number_last_four' => '3456',
        'routing_code_encrypted' => 'EXAMPLE0001',
        'country_code' => 'IN',
        'currency' => 'INR',
    ]);

    expect(DB::table('vendor_bank_accounts')->find($account->id)->account_number_encrypted)
        ->not->toBe('1234567890123456')
        ->and($account->refresh()->account_number_encrypted)->toBe('1234567890123456')
        ->and($account->routing_code_encrypted)->toBe('EXAMPLE0001');
});
