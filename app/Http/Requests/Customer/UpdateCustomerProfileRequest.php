<?php

namespace App\Http\Requests\Customer;

use App\Concerns\ProfileValidationRules;
use App\Models\Sport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getKey();

        return [
            ...$this->profileRules($userId),
            'profile_image' => ['nullable', 'image', 'max:3072'],
            'remove_profile_image' => ['nullable', 'boolean'],
            'preferred_sport_ids' => ['nullable', 'array', 'max:4'],
            'preferred_sport_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(Sport::class, 'id')->where('is_active', true),
            ],
            'default_location_label' => ['nullable', 'string', 'max:120'],
            'email_notifications_enabled' => ['required', 'boolean'],
            'sms_notifications_enabled' => ['required', 'boolean'],
            'marketing_notifications_enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     profile_image: UploadedFile|null,
     *     remove_profile_image: bool,
     *     preferred_sport_ids: array<int, int>,
     *     default_location_label: string|null,
     *     email_notifications_enabled: bool,
     *     sms_notifications_enabled: bool,
     *     marketing_notifications_enabled: bool
     * }
     */
    public function profileData(): array
    {
        /** @var array<int, int> $preferredSportIds */
        $preferredSportIds = array_values(array_map(
            static fn (mixed $sportId): int => (int) $sportId,
            $this->input('preferred_sport_ids', []),
        ));

        /** @var UploadedFile|null $profileImage */
        $profileImage = $this->file('profile_image');

        return [
            'name' => (string) $this->input('name'),
            'email' => (string) $this->input('email'),
            'profile_image' => $profileImage,
            'remove_profile_image' => $this->boolean('remove_profile_image'),
            'preferred_sport_ids' => $preferredSportIds,
            'default_location_label' => $this->filled('default_location_label')
                ? (string) $this->input('default_location_label')
                : null,
            'email_notifications_enabled' => $this->boolean('email_notifications_enabled'),
            'sms_notifications_enabled' => $this->boolean('sms_notifications_enabled'),
            'marketing_notifications_enabled' => $this->boolean('marketing_notifications_enabled'),
        ];
    }
}
