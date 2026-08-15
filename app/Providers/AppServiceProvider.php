<?php

namespace App\Providers;

use App\Domain\Auth\Contracts\OtpDeliveryProvider;
use App\Domain\Auth\Providers\FakeOtpDeliveryProvider;
use App\Support\EnvironmentConfiguration;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue as BaseQueue;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OtpDeliveryProvider::class, FakeOtpDeliveryProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureTrustedProxies();
        $this->configureRateLimiting();
        $this->configureQueue();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        EnvironmentConfiguration::assertSafe(
            app()->environment(),
            (bool) config('app.debug'),
        );
        EnvironmentConfiguration::assertOtpHashPepper(
            app()->environment(),
            config('otp.hash_pepper'),
        );
        EnvironmentConfiguration::assertOtpLookupHmacKey(
            app()->environment(),
            config('otp.lookup_hmac_key'),
        );
        EnvironmentConfiguration::assertOtpDeliveryProvider(
            app()->environment(),
            config('otp.delivery_provider'),
        );

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure queue durability and correlation behavior shared by all jobs.
     */
    protected function configureQueue(): void
    {
        BaseQueue::createPayloadUsing(
            fn (): array => $this->queuePayloadContext(),
        );

        Queue::before(function (JobProcessing $event): void {
            Log::withContext($this->queueLogContext($event->job));
        });

        $clearContext = function (): void {
            $this->clearQueueLogContext();
        };

        Queue::after(function (JobProcessed $event) use ($clearContext): void {
            $clearContext();
        });

        Queue::exceptionOccurred(function (JobExceptionOccurred $event) use ($clearContext): void {
            $clearContext();
        });

        Queue::failing(function (JobFailed $event) use ($clearContext): void {
            $clearContext();
        });
    }

    /**
     * Configure the shared baseline limit for versioned API routes.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('rate_limiting.api.per_minute'))
                ->by($this->apiRateLimitKey($request));
        });
    }

    /**
     * Limit forwarding-header trust to the explicit trusted-proxy policy.
     */
    protected function configureTrustedProxies(): void
    {
        TrustProxies::withHeaders((int) config('trustedproxy.headers'));
    }

    /**
     * Build a privacy-safe, bounded rate-limit key from the current caller.
     */
    protected function apiRateLimitKey(Request $request): string
    {
        $user = $request->user();

        if ($user !== null) {
            return 'v1:api:user:'.$user->getAuthIdentifier();
        }

        return 'v1:api:ip:'.hash_hmac(
            'sha256',
            (string) $request->ip(),
            (string) config('app.key'),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function queuePayloadContext(): array
    {
        $requestId = $this->currentRequestId();

        return $requestId === null ? [] : ['request_id' => $requestId];
    }

    /**
     * @return array<string, string>
     */
    protected function queueLogContext(Job $job): array
    {
        $context = [
            'queue' => $job->getQueue(),
            'job_id' => $job->getJobId(),
        ];
        $requestId = $job->payload()['request_id'] ?? null;

        if (is_string($requestId)) {
            $context['request_id'] = $requestId;
        }

        return $context;
    }

    protected function clearQueueLogContext(): void
    {
        Log::withoutContext(['request_id', 'queue', 'job_id']);

        $requestId = $this->currentRequestId();

        if ($requestId !== null) {
            Log::withContext(['request_id' => $requestId]);
        }
    }

    protected function currentRequestId(): ?string
    {
        $request = app()->bound('request') ? app('request') : null;

        return $request instanceof Request && is_string($request->attributes->get('request_id'))
            ? $request->attributes->get('request_id')
            : null;
    }
}
