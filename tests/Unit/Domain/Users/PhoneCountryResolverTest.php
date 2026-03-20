<?php

use App\Domain\Users\PhoneCountryResolver;

beforeEach(function () {
    $this->resolver = new PhoneCountryResolver;
});

it('detects colombia from a colombian phone number', function () {
    expect($this->resolver->detectCountryFromPhone('+573001234567'))->toBe('CO');
});

it('detects united states from a us phone number', function () {
    expect($this->resolver->detectCountryFromPhone('+12025551234'))->toBe('US');
});

it('returns null for invalid phone numbers', function () {
    expect($this->resolver->detectCountryFromPhone('not-a-phone'))->toBeNull();
});

it('returns null for empty string', function () {
    expect($this->resolver->detectCountryFromPhone(''))->toBeNull();
});
