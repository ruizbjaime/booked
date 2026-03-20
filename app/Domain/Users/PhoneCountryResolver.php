<?php

namespace App\Domain\Users;

use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class PhoneCountryResolver
{
    /**
     * Detect the ISO alpha-2 country code from a phone number.
     */
    public function detectCountryFromPhone(string $phone): ?string
    {
        try {
            $parsed = new PhoneNumber($phone);

            $country = $parsed->getCountry();

            return $country !== null ? strtoupper($country) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
