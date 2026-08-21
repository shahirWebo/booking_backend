<?php

namespace App\Domain\SystemSettings\Services;

use App\Domain\SystemSettings\Data\BookingConfigurationData;
use App\Domain\SystemSettings\Data\OtpConfigurationData;
use App\Domain\SystemSettings\Data\PlatformSupportData;
use App\Domain\SystemSettings\Data\SystemSettingsData;
use App\Domain\SystemSettings\Repositories\SystemSettingRepository;
use App\Domain\SystemSettings\SystemSettingKey;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;

final class SystemSettingsService
{
    private const CACHE_KEY = 'system_settings.snapshot';

    public function __construct(
        private readonly SystemSettingRepository $settings,
        private readonly CacheRepository $cache,
    ) {}

    public function snapshot(): SystemSettingsData
    {
        /** @var array{
         *     booking: array<string, mixed>,
         *     otp: array<string, mixed>,
         *     support: array<string, mixed>
         * } $payload
         */
        $payload = $this->cache->remember(
            self::CACHE_KEY,
            now()->addMinutes(5),
            fn (): array => $this->loadSnapshotPayload(),
        );

        return new SystemSettingsData(
            booking: BookingConfigurationData::fromArray($payload['booking']),
            otp: OtpConfigurationData::fromArray($payload['otp']),
            support: PlatformSupportData::fromArray($payload['support']),
        );
    }

    /**
     * @param  array{
     *     booking: array<string, mixed>,
     *     otp: array<string, mixed>,
     *     support: array<string, mixed>
     * }  $attributes
     */
    public function update(array $attributes): SystemSettingsData
    {
        $snapshot = new SystemSettingsData(
            booking: BookingConfigurationData::fromArray($attributes['booking']),
            otp: OtpConfigurationData::fromArray($attributes['otp']),
            support: PlatformSupportData::fromArray($attributes['support']),
        );

        $this->settings->upsertMany([
            SystemSettingKey::BookingConfiguration->value => $snapshot->booking->toArray(),
            SystemSettingKey::OtpConfiguration->value => $snapshot->otp->toArray(),
            SystemSettingKey::PlatformSupport->value => $snapshot->support->toArray(),
        ]);

        $this->cache->forget(self::CACHE_KEY);

        return $this->snapshot();
    }

    /**
     * @return array{
     *     booking: array<string, mixed>,
     *     otp: array<string, mixed>,
     *     support: array<string, mixed>
     * }
     */
    private function loadSnapshotPayload(): array
    {
        $indexed = $this->settings->all()->keyBy('key');

        return [
            'booking' => Arr::wrap($indexed->get(SystemSettingKey::BookingConfiguration->value)?->value),
            'otp' => Arr::wrap($indexed->get(SystemSettingKey::OtpConfiguration->value)?->value),
            'support' => Arr::wrap($indexed->get(SystemSettingKey::PlatformSupport->value)?->value),
        ];
    }
}
