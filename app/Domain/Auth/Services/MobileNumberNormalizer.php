<?php

namespace App\Domain\Auth\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use LogicException;

final class MobileNumberNormalizer
{
    /**
     * Normalize a supported mobile number to E.164.
     *
     * @throws NumberParseException
     */
    public function normalize(string $mobileNumber): string
    {
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneNumberUtil->parse(
            $mobileNumber,
            config('otp.default_region'),
        );

        if (! $phoneNumberUtil->isValidNumber($phoneNumber)) {
            throw new LogicException('The mobile number is not valid.');
        }

        $region = $phoneNumberUtil->getRegionCodeForNumber($phoneNumber);

        if (! in_array($region, config('otp.permitted_regions'), true)) {
            throw new LogicException('The mobile number region is not supported.');
        }

        return $phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::E164);
    }
}
