<?php

use App\Models\SystemSetting;
use App\Policies\SystemSettingPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('system setting policy allows viewing settings with the proper permission', function () {
    $policy = new SystemSettingPolicy;
    $user = makeGuest();
    $user->givePermissionTo('system_setting.viewAny');

    expect($policy->viewAny($user))->toBeTrue();
});

test('system setting policy denies viewing settings without permission', function () {
    $policy = new SystemSettingPolicy;
    $user = makeGuest();

    expect($policy->viewAny($user))->toBeFalse();
});

test('system setting policy allows updates with the proper permission', function () {
    $policy = new SystemSettingPolicy;
    $user = makeGuest();
    $user->givePermissionTo('system_setting.update');

    expect($policy->update($user, SystemSetting::instance()))->toBeTrue();
});

test('system setting policy denies updates without permission', function () {
    $policy = new SystemSettingPolicy;
    $user = makeGuest();

    expect($policy->update($user, SystemSetting::instance()))->toBeFalse();
});
