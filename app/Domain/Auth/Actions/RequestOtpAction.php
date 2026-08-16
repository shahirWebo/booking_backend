<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Enums\OtpRequestPurpose;
use App\Domain\Auth\Services\OtpRequestRateLimiter;
use App\Jobs\SendOtpChallenge;
use App\Models\OtpRequest;

final class RequestOtpAction
{
    public function __construct(
        private readonly OtpRequestRateLimiter $rateLimiter,
        private readonly IssueOtpChallengeAction $issueChallenge,
    ) {}

    public function execute(
        string $mobileNumber,
        string $ipAddress,
        ?string $installationId,
    ): OtpRequest {
        $purpose = OtpRequestPurpose::Authentication;

        $this->rateLimiter->consume(
            $mobileNumber,
            $purpose->value,
            $ipAddress,
            $installationId,
        );

        $issued = $this->issueChallenge->execute($mobileNumber, $purpose);

        SendOtpChallenge::dispatch($issued['challenge']->id, $issued['code'])
            ->onQueue('auth')
            ->afterCommit();

        return $issued['challenge'];
    }
}
