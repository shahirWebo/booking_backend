<?php

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Enums\OtpRequestStatus;
use App\Models\OtpRequest;
use Carbon\CarbonImmutable;

final class OtpRequestRepository
{
    /**
     * @param  list<OtpRequestStatus>  $statuses
     */
    public function expireActive(
        string $mobileLookup,
        OtpRequestPurpose $purpose,
        array $statuses,
        CarbonImmutable $now,
    ): void {
        OtpRequest::query()
            ->where('mobile_number_lookup_hmac', $mobileLookup)
            ->where('purpose', $purpose->value)
            ->whereIn('status', $this->statusValues($statuses))
            ->where('expires_at', '<=', $now)
            ->lockForUpdate()
            ->update([
                'status' => OtpRequestStatus::Expired->value,
                'terminal_reason' => 'expired',
                'updated_at' => $now,
            ]);
    }

    public function latestForUpdate(string $mobileLookup, OtpRequestPurpose $purpose): ?OtpRequest
    {
        return OtpRequest::query()
            ->where('mobile_number_lookup_hmac', $mobileLookup)
            ->where('purpose', $purpose->value)
            ->latest('issued_at')
            ->lockForUpdate()
            ->first();
    }

    /**
     * @param  list<OtpRequestStatus>  $statuses
     */
    public function supersedeActive(
        string $mobileLookup,
        OtpRequestPurpose $purpose,
        array $statuses,
        CarbonImmutable $now,
    ): void {
        OtpRequest::query()
            ->where('mobile_number_lookup_hmac', $mobileLookup)
            ->where('purpose', $purpose->value)
            ->whereIn('status', $this->statusValues($statuses))
            ->lockForUpdate()
            ->update([
                'status' => OtpRequestStatus::Superseded->value,
                'terminal_reason' => 'resend',
                'updated_at' => $now,
            ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $id, array $attributes): OtpRequest
    {
        $challenge = new OtpRequest($attributes);
        $challenge->id = $id;
        $challenge->save();

        return $challenge;
    }

    public function find(string $challengeId): ?OtpRequest
    {
        return OtpRequest::query()->find($challengeId);
    }

    public function findForUpdate(string $challengeId): ?OtpRequest
    {
        return OtpRequest::query()->lockForUpdate()->find($challengeId);
    }

    public function save(OtpRequest $challenge): void
    {
        $challenge->save();
    }

    /**
     * @param  list<OtpRequestStatus>  $statuses
     * @return list<string>
     */
    private function statusValues(array $statuses): array
    {
        return array_map(
            static fn (OtpRequestStatus $status): string => $status->value,
            $statuses,
        );
    }
}
