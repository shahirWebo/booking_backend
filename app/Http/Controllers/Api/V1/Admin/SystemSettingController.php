<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\SystemSettings\Actions\UpdateSystemSettingsAction;
use App\Domain\SystemSettings\Services\SystemSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateSystemSettingsRequest;
use App\Http\Resources\Admin\SystemSettingsResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success(
            new SystemSettingsResource($this->settings->snapshot()),
            headers: ['Cache-Control' => 'private, max-age=300'],
        );
    }

    public function update(
        UpdateSystemSettingsRequest $request,
        UpdateSystemSettingsAction $updateSystemSettings,
    ): JsonResponse {
        $settings = $updateSystemSettings->execute($request->settingsAttributes());

        return ApiResponse::success(
            new SystemSettingsResource($settings),
            message: 'System settings updated.',
        );
    }
}
