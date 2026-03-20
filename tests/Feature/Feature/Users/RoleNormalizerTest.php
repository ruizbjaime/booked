<?php

use App\Domain\Users\RoleConfig;
use App\Domain\Users\RoleNormalizer;
use Database\Seeders\RolesAndPermissionsSeeder;

test('normalize removes duplicate roles', function () {
    expect(RoleNormalizer::normalize(['role_a', 'role_a', 'role_b']))
        ->toBe(['role_a', 'role_b']);
});

test('normalize returns only admin when admin is present', function () {
    expect(RoleNormalizer::normalize(['role_a', RoleConfig::adminRole(), 'role_b']))
        ->toBe([RoleConfig::adminRole()]);
});

test('normalize filters by allowed roles when provided', function () {
    expect(RoleNormalizer::normalize(['role_a', 'role_b', 'role_c'], ['role_a', 'role_c']))
        ->toBe(['role_a', 'role_c']);
});

test('normalize returns only admin even when filtering by allowed roles', function () {
    expect(RoleNormalizer::normalize([RoleConfig::adminRole(), 'role_a'], [RoleConfig::adminRole(), 'role_a']))
        ->toBe([RoleConfig::adminRole()]);
});

test('normalize returns empty array when no roles match allowed list', function () {
    expect(RoleNormalizer::normalize(['role_a', 'role_b'], ['role_c']))
        ->toBe([]);
});

test('normalize preserves order of first occurrence', function () {
    expect(RoleNormalizer::normalize(['role_b', 'role_a']))
        ->toBe(['role_b', 'role_a']);
});

test('available returns role names from the database ordered by name', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $roles = RoleNormalizer::available();

    $sorted = $roles;
    sort($sorted);

    expect($roles)->toBeArray()
        ->and($roles)->toBe(array_values($sorted));
});
