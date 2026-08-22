<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Search\Services\TurfDiscoveryService;
use App\Domain\Sports\Repositories\SportRepository;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CustomerHomeController extends Controller
{
    public function __construct(
        private readonly TurfDiscoveryService $discovery,
        private readonly SportRepository $sports,
    ) {}

    public function __invoke(): InertiaResponse
    {
        return Inertia::render('customer/Home', [
            'nearbyTurfs' => $this->discovery->search([
                'per_page' => 4,
            ], route('customer.home'))->items(),
            'sports' => $this->sports->allActiveOrdered()
                ->map(fn ($sport): array => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'code' => $sport->code,
                ])
                ->all(),
        ]);
    }
}
