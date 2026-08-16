<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RequestOtpAction;
use App\Domain\Auth\Exceptions\OtpRateLimitExceededException;
use App\Domain\Auth\Exceptions\OtpResendCooldownException;
use App\Domain\Auth\Exceptions\OtpSecurityControlUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RequestOtpRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class RequestOtpController extends Controller
{
    /**
     * Expose the OTP-request API boundary.
     *
     * It accepts a durably stored challenge only; provider delivery is performed
     * asynchronously after the challenge transaction commits.
     */
    public function __invoke(
        RequestOtpRequest $request,
        RequestOtpAction $requestOtp,
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $challenge = $requestOtp->execute(
                $validated['mobile'],
                (string) $request->ip(),
                $request->header('X-Installation-ID'),
            );
        } catch (OtpResendCooldownException|OtpRateLimitExceededException $exception) {
            throw new TooManyRequestsHttpException(
                $exception->retryAfterSeconds,
                'OTP requests are temporarily limited.',
            );
        } catch (OtpSecurityControlUnavailableException $exception) {
            throw new ServiceUnavailableHttpException(null, 'OTP requests are temporarily unavailable.', $exception);
        }

        return ApiResponse::success(
            [
                'otp_request_id' => $challenge->id,
                'expires_at' => $challenge->expires_at->toIso8601String(),
                'resend_available_at' => $challenge->resend_available_at->toIso8601String(),
            ],
            202,
            headers: ['Cache-Control' => 'no-store, private'],
        );
    }
}
