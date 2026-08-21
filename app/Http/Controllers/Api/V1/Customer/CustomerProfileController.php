<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RequestCustomerAccountDeletionRequest;
use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use App\Http\Resources\Customer\CustomerProfileResource;
use App\Models\CustomerProfile;
use App\Models\Sport;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse|InertiaResponse
    {
        $profile = CustomerProfile::query()->firstOrCreate([
            'user_id' => $request->user()->id,
        ])->load('user');

        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::success(new CustomerProfileResource($profile));
        }

        return Inertia::render('customer/Profile', [
            'profile' => $this->profilePageData($profile),
            'availableSports' => Sport::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Sport $sport): array => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'code' => $sport->code,
                ])
                ->all(),
        ]);
    }

    public function update(UpdateCustomerProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $profile = CustomerProfile::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        $data = $request->profileData();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $existingImagePath = $profile->profile_image_path;

        if ($data['remove_profile_image'] && $existingImagePath !== null) {
            Storage::disk('public')->delete($existingImagePath);
            $profile->profile_image_path = null;
        }

        if ($data['profile_image'] !== null) {
            if ($existingImagePath !== null) {
                Storage::disk('public')->delete($existingImagePath);
            }

            $profile->profile_image_path = $data['profile_image']->store('customer-profiles', 'public');
        }

        $profile->fill([
            'preferred_sport_ids' => $data['preferred_sport_ids'],
            'default_location_label' => $data['default_location_label'],
            'email_notifications_enabled' => $data['email_notifications_enabled'],
            'sms_notifications_enabled' => $data['sms_notifications_enabled'],
            'marketing_notifications_enabled' => $data['marketing_notifications_enabled'],
        ]);
        $profile->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Customer profile updated.')]);

        return to_route('customer.profile.show');
    }

    public function requestDeletion(RequestCustomerAccountDeletionRequest $request): RedirectResponse
    {
        $profile = CustomerProfile::query()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        if ($profile->account_deletion_requested_at === null) {
            $profile->forceFill([
                'account_deletion_requested_at' => CarbonImmutable::now(),
                'account_deletion_reason' => $request->reason(),
            ])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delete-account request submitted.')]);

        return to_route('customer.profile.show');
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePageData(CustomerProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'name' => $profile->user?->name,
            'mobile_number' => $profile->user?->mobile_number,
            'email' => $profile->user?->email,
            'profile_image_url' => $profile->profileImageUrl(),
            'preferred_sport_ids' => $profile->preferred_sport_ids ?? [],
            'default_location_label' => $profile->default_location_label,
            'email_notifications_enabled' => $profile->email_notifications_enabled,
            'sms_notifications_enabled' => $profile->sms_notifications_enabled,
            'marketing_notifications_enabled' => $profile->marketing_notifications_enabled,
            'account_deletion_requested_at' => $profile->account_deletion_requested_at?->toIso8601String(),
            'account_deletion_reason' => $profile->account_deletion_reason,
        ];
    }
}
