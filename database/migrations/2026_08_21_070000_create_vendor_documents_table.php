<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->restrictOnDelete()
                ->comment('The vendor that supplied this private onboarding document.');
            $table->unsignedBigInteger('file_id')->nullable()
                ->comment('Files-owned record; constrained when the Files foundation is introduced.');
            $table->string('document_type', 50)
                ->comment('Controlled KYC or business-document type.');
            $table->unsignedInteger('submission_version')->default(1)
                ->comment('The vendor onboarding submission version that includes this document.');
            $table->string('status', 50)->default('active')
                ->comment('Attachment lifecycle: active, superseded, rejected.');
            $table->timestampsTz(6);

            $table->unique(
                ['vendor_id', 'document_type', 'submission_version'],
                'vendor_documents_vendor_type_version_unique',
            );
            $table->index(['vendor_id', 'status'], 'vendor_documents_vendor_status_index');
            $table->index('file_id', 'vendor_documents_file_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
