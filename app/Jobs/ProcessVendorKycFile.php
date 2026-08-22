<?php

namespace App\Jobs;

use App\Domain\Files\Contracts\FileScanner;
use App\Domain\Files\Enums\FileStatus;
use App\Models\File;
use App\Models\VendorDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessVendorKycFile implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly int $fileId) {}

    public function handle(FileScanner $scanner): void
    {
        $file = DB::transaction(function (): ?File {
            $file = File::query()->lockForUpdate()->find($this->fileId);

            if (! $file instanceof File || $file->status !== FileStatus::Uploaded) {
                return null;
            }

            $file->forceFill(['status' => FileStatus::Scanning])->save();

            return $file;
        });

        if (! $file instanceof File) {
            return;
        }

        try {
            $contents = Storage::disk($file->logical_disk)->get($file->object_key);
            $metadata = $this->verifiedMetadata($contents, $scanner);

            Storage::disk('private_files')->put($file->object_key, $contents);

            DB::transaction(function () use ($file, $metadata): void {
                $lockedFile = File::query()->lockForUpdate()->find($file->id);

                if (! $lockedFile instanceof File || $lockedFile->status !== FileStatus::Scanning) {
                    return;
                }

                $lockedFile->forceFill([
                    'status' => FileStatus::Ready,
                    'logical_disk' => 'private_files',
                    'detected_mime_type' => $metadata['mime_type'],
                    'canonical_extension' => $metadata['extension'],
                    'scanned_at' => now(),
                    'ready_at' => now(),
                ])->save();

                VendorDocument::query()
                    ->where('file_id', $lockedFile->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'active']);
            });

            Storage::disk((string) config('files.vendor_kyc.disk'))->delete($file->object_key);
        } catch (Throwable) {
            $this->markFailed($file->id);
        }
    }

    /**
     * @return array{mime_type: string, extension: string}
     */
    private function verifiedMetadata(string $contents, FileScanner $scanner): array
    {
        if (! $scanner->isClean($contents)) {
            throw new \RuntimeException('File scanner rejected the upload.');
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $allowedMimeTypes = config('files.vendor_kyc.accepted_mime_types');

        if (! is_string($mimeType) || ! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new \RuntimeException('The uploaded content type is not permitted.');
        }

        return match ($mimeType) {
            'application/pdf' => $this->validatedPdfMetadata($contents),
            'image/jpeg', 'image/png' => $this->validatedImageMetadata($contents, $mimeType),
            default => throw new \RuntimeException('The uploaded content type is not permitted.'),
        };
    }

    /**
     * @return array{mime_type: string, extension: string}
     */
    private function validatedPdfMetadata(string $contents): array
    {
        if (! str_starts_with($contents, '%PDF-')) {
            throw new \RuntimeException('The uploaded PDF is invalid.');
        }

        return ['mime_type' => 'application/pdf', 'extension' => 'pdf'];
    }

    /**
     * @return array{mime_type: string, extension: string}
     */
    private function validatedImageMetadata(string $contents, string $mimeType): array
    {
        $image = @getimagesizefromstring($contents);

        if ($image === false || $image['mime'] !== $mimeType) {
            throw new \RuntimeException('The uploaded image is invalid.');
        }

        return [
            'mime_type' => $mimeType,
            'extension' => $mimeType === 'image/jpeg' ? 'jpg' : 'png',
        ];
    }

    private function markFailed(int $fileId): void
    {
        DB::transaction(function () use ($fileId): void {
            $file = File::query()->lockForUpdate()->find($fileId);

            if (! $file instanceof File || $file->status !== FileStatus::Scanning) {
                return;
            }

            $file->forceFill([
                'status' => FileStatus::Failed,
                'rejection_code' => 'processing_failed',
            ])->save();

            VendorDocument::query()
                ->where('file_id', $file->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);
        });
    }
}
