<?php

namespace App\Http\Requests\Vendor;

use App\Domain\Vendors\Enums\VendorDocumentType;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File as FileRule;

final class UploadVendorKycDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vendor = $this->route('vendor');

        return $vendor instanceof Vendor
            && $this->user()?->can('update', $vendor) === true;
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::enum(VendorDocumentType::class)],
            'document' => [
                'required',
                FileRule::types(['pdf', 'jpg', 'jpeg', 'png'])
                    ->max((int) config('files.vendor_kyc.max_size_bytes') / 1024),
            ],
        ];
    }

    public function documentType(): VendorDocumentType
    {
        return VendorDocumentType::from((string) $this->input('document_type'));
    }

    public function document(): UploadedFile
    {
        /** @var UploadedFile $document */
        $document = $this->file('document');

        return $document;
    }
}
