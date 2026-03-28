<?php

use App\Models\Country;
use App\Models\IdentificationDocumentType;
use App\Models\User;
use Carbon\CarbonImmutable;

it('computes initials from a full name', function () {
    $user = User::factory()->make(['name' => 'Jaime Ruiz']);

    expect($user->initials())->toBe('JR');
});

it('computes initials from a single name', function () {
    $user = User::factory()->make(['name' => 'Admin']);

    expect($user->initials())->toBe('A');
});

it('computes initials from three or more names taking only first two', function () {
    $user = User::factory()->make(['name' => 'Ana María López']);

    expect($user->initials())->toBe('AM');
});

it('returns null avatar url when no avatar is set', function () {
    $user = User::factory()->create();

    expect($user->avatarUrl())->toBeNull();
});

it('belongs to a country', function () {
    $country = Country::factory()->create();
    $user = User::factory()->create(['country_id' => $country->id]);

    expect($user->country->id)->toBe($country->id);
});

it('belongs to a document type', function () {
    $type = IdentificationDocumentType::factory()->create();
    $user = User::factory()->create(['document_type_id' => $type->id]);

    expect($user->documentType->id)->toBe($type->id);
});

it('casts is_active to boolean', function () {
    $user = User::factory()->create(['is_active' => true]);

    expect($user->is_active)->toBeTrue()->toBeBool();
});

it('casts last_login_at to datetime', function () {
    $user = User::factory()->create(['last_login_at' => now()]);

    expect($user->last_login_at)->toBeInstanceOf(CarbonImmutable::class);
});
