<?php

namespace App\Domain\Vendors\Actions;

use App\Domain\Files\Enums\FilePurpose;
use App\Domain\Files\Enums\FileStatus;
use App\Domain\Vendors\Enums\VendorDocumentType;
use App\Domain\Vendors\Enums\VendorStatus;
use App\Jobs\ProcessVendorKycFile;
use App\Models\File;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class StoreVendorKycDocumentAction
{
    public function execute(
        Vendor $vendor,
        User $actor,
        VendorDocumentType $documentType,
        UploadedFile $uploadedFile,
    ): File {
        if ($vendor->status !== VendorStatus::Draft) {
            throw ValidationException::withMessages([
                'vendor' => 'KYC documents can only be uploaded while registration is a draft.',
            ]);
        }

        $disk = (string) config('files.vendor_kyc.disk');
        $objectKey = $this->objectKey();
        $contents = $uploadedFile->get();

        if ($contents === false) {
            throw ValidationException::withMessages([
                'document' => 'The uploaded document could not be read. Please try again.',
            ]);
        }

        Storage::disk($disk)->put($objectKey, $contents);

        try {
            $file = DB::transaction(function () use ($vendor, $actor, $documentType, $uploadedFile, $disk, $objectKey, $contents): File {
                $document = VendorDocument::query()
                    ->where('vendor_id', $vendor->id)
                    ->where('document_type', $documentType->value)
                    ->where('submission_version', $vendor->submission_version)
                    ->lockForUpdate()
                    ->first();

                if ($document instanceof VendorDocument && $document->status !== 'rejected') {
                    throw ValidationException::withMessages([
                        'document_type' => 'A document of this type is already attached to the current submission.',
                    ]);
                }

                $file = File::query()->create([
                    'purpose' => FilePurpose::VendorKycDocument,
                    'status' => FileStatus::Uploaded,
                    'created_by_user_id' => $actor->id,
                    'vendor_id' => $vendor->id,
                    'logical_disk' => $disk,
                    'object_key' => $objectKey,
                    'original_name' => $this->sanitizedOriginalName($uploadedFile),
                    'size_bytes' => strlen($contents),
                    'checksum_sha256' => hash('sha256', $contents),
                    'uploaded_at' => now(),
                ]);

                if ($document instanceof VendorDocument) {
                    $document->forceFill([
                        'file_id' => $file->id,
                        'status' => 'pending',
                    ])->save();
                } else {
                    VendorDocument::query()->create([
                        'vendor_id' => $vendor->id,
                        'file_id' => $file->id,
                        'document_type' => $documentType->value,
                        'submission_version' => $vendor->submission_version,
                        'status' => 'pending',
                    ]);
                }

                return $file;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($objectKey);

            throw $exception;
        }

        ProcessVendorKycFile::dispatch($file->id)->onQueue('media');

        return $file;
    }

    private function objectKey(): string
    {
        return sprintf(
            '%s/%s/%s/%s/source',
            FilePurpose::VendorKycDocument->value,
            now()->format('Y'),
            now()->format('m'),
            Str::ulid(),
        );
    }

    private function sanitizedOriginalName(UploadedFile $uploadedFile): string
    {
        $name = preg_replace('/[[:cntrl:]]/', '', $uploadedFile->getClientOriginalName()) ?? 'document';

        return Str::limit(basename($name), 255, '');
    }
}
