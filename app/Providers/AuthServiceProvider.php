<?php

namespace App\Providers;

use App\Http\Policies\LocationPolicy;
use App\Http\Policies\VendorPolicy;
use App\Models\Location;
use App\Models\Vendor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }

    /**
     * Register authorization policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
    }
}
