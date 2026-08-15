<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RequestOtpRequest;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class RequestOtpController extends Controller
{
    /**
     * Expose the OTP-request API boundary.
     *
     * The endpoint fails closed until the subsequent Auth tasks add validation,
     * challenge issuance, anti-abuse controls, and encrypted provider delivery.
     */
    public function __invoke(RequestOtpRequest $request): never
    {
        $request->validated();

        throw new ServiceUnavailableHttpException(null, 'OTP requests are not available.');
    }
}
