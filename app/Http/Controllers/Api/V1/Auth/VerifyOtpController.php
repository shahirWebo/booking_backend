<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\VerifyOtpAction;
use App\Domain\Auth\Exceptions\OtpAttemptsExceededException;
use App\Domain\Auth\Exceptions\OtpInvalidOrExpiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class VerifyOtpController extends Controller
{
    public function __invoke(
        VerifyOtpRequest $request,
        VerifyOtpAction $verifyOtp,
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $authenticated = $verifyOtp->execute($validated['otp_request_id'], $validated['code']);
        } catch (OtpInvalidOrExpiredException|OtpAttemptsExceededException) {
            // Keep challenge lifecycle outcomes indistinguishable to callers.
            throw new UnprocessableEntityHttpException('The OTP is invalid or expired.');
        }

        return ApiResponse::success(
            [
                'access_token' => $authenticated['accessToken']->plainTextToken,
                'token_type' => 'Bearer',
            ],
            headers: ['Cache-Control' => 'no-store'],
        );
    }
}
