<?php

use App\Domain\Users\PhoneCountryResolver;
use Propaganistas\LaravelPhone\PhoneNumber;

it('returns null when the phone number library throws an unexpected exception', function () {
    $resolver = new class extends PhoneCountryResolver
    {
        protected function parsePhoneNumber(string $phone): PhoneNumber
        {
            throw new RuntimeException('Unexpected library failure');
        }
    };

    expect($resolver->detectCountryFromPhone('+573001234567'))->toBeNull();
});
