<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Sports\Actions\CreateSportAction;
use App\Domain\Sports\Actions\DeleteSportAction;
use App\Domain\Sports\Actions\UpdateSportAction;
use App\Domain\Sports\Repositories\SportRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreSportRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSportRequest;
use App\Http\Resources\Admin\SportResource;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class SportManagementController extends Controller
{
    public function __construct(
        private readonly SportRepository $sports,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/operations/SportsIndex', [
            'sports' => $this->serializeSports($request),
            'routes' => [
                'create' => route('admin.sports.create'),
                'publicIndex' => route('api.v1.sports.index'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/operations/SportForm', [
            'mode' => 'create',
            'sport' => null,
            'routes' => [
                'index' => route('admin.sports.index'),
                'submit' => route('admin.sports.store'),
            ],
        ]);
    }

    public function edit(Request $request, Sport $sport): Response
    {
        return Inertia::render('admin/operations/SportForm', [
            'mode' => 'edit',
            'sport' => [
                ...(new SportResource($sport))->resolve($request),
            ],
            'routes' => [
                'index' => route('admin.sports.index'),
                'submit' => route('admin.sports.update', $sport),
            ],
        ]);
    }

    public function store(
        StoreSportRequest $request,
        CreateSportAction $createSport,
    ): RedirectResponse {
        $createSport->execute($request->sportAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport created.')]);

        return to_route('admin.sports.index');
    }

    public function update(
        UpdateSportRequest $request,
        Sport $sport,
        UpdateSportAction $updateSport,
    ): RedirectResponse {
        $updateSport->execute($sport, $request->sportAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport updated.')]);

        return to_route('admin.sports.index');
    }

    public function destroy(
        Sport $sport,
        DeleteSportAction $deleteSport,
    ): RedirectResponse {
        $deleteSport->execute($sport);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sport deleted.')]);

        return to_route('admin.sports.index');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeSports(Request $request): array
    {
        /** @var Collection<int, Sport> $sports */
        $sports = $this->sports->allOrdered();

        return $sports
            ->map(fn (Sport $sport): array => [
                ...(new SportResource($sport))->resolve($request),
                'routes' => [
                    'edit' => route('admin.sports.edit', $sport),
                    'update' => route('admin.sports.update', $sport),
                    'destroy' => route('admin.sports.destroy', $sport),
                ],
            ])
            ->values()
            ->all();
    }
}
