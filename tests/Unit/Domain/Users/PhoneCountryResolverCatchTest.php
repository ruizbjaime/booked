<?php

use App\Domain\Users\PhoneCountryResolver;
use Propaganistas\LaravelPhone\PhoneNumber;

it('returns null when the phone number library throws an unexpected exception', function () {
    // The PhoneNumber constructor stores values without parsing, and getCountry()
    // catches exceptions internally. The outer catch in PhoneCountryResolver is a
    // safety net for unexpected library failures. We verify it by overriding the
    // PhoneNumber class to throw from getCountry().
    $mock = Mockery::mock('overload:'.PhoneNumber::class);
    $mock->shouldReceive('getCountry')->andThrow(new RuntimeException('Unexpected library failure'));

    $resolver = new PhoneCountryResolver;

    expect($resolver->detectCountryFromPhone('+573001234567'))->toBeNull();
});
