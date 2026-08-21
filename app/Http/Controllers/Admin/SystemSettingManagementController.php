<?php

namespace App\Http\Controllers\Admin;

use App\Domain\SystemSettings\Actions\UpdateSystemSettingsAction;
use App\Domain\SystemSettings\Services\SystemSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateSystemSettingsRequest;
use App\Http\Resources\Admin\SystemSettingsResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SystemSettingManagementController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    public function show(Request $request): Response
    {
        return Inertia::render('admin/governance/SystemSettings', [
            'settings' => (new SystemSettingsResource($this->settings->snapshot()))->resolve($request),
            'routes' => [
                'update' => route('admin.system_settings.update'),
            ],
        ]);
    }

    public function update(
        UpdateSystemSettingsRequest $request,
        UpdateSystemSettingsAction $updateSystemSettings,
    ): RedirectResponse {
        $updateSystemSettings->execute($request->settingsAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('System settings updated.')]);

        return to_route('admin.system_settings.show');
    }
}
