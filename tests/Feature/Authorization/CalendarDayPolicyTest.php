<?php

use App\Domain\Users\RoleConfig;
use App\Models\CalendarDay;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('admin can perform every calendar day policy ability', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $day = CalendarDay::factory()->create();

    $admin->assignRole(RoleConfig::adminRole());

    expect($admin->can('viewAny', CalendarDay::class))->toBeTrue()
        ->and($admin->can('view', $day))->toBeTrue()
        ->and($admin->can('regenerate', CalendarDay::class))->toBeTrue();
});

test('non-admin roles cannot perform any calendar day policy ability', function (string $role) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $day = CalendarDay::factory()->create();

    $user->assignRole($role);

    expect($user->can('viewAny', CalendarDay::class))->toBeFalse()
        ->and($user->can('view', $day))->toBeFalse()
        ->and($user->can('regenerate', CalendarDay::class))->toBeFalse();
})->with(function () {
    return nonAdminRoleNames();
});

test('role with specific calendar day permissions can perform only those abilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['name' => 'calendar-viewer']);
    $role->givePermissionTo('calendar_day.viewAny', 'calendar_day.view');

    $user = User::factory()->create();
    $user->assignRole($role);
    $day = CalendarDay::factory()->create();

    expect($user->can('viewAny', CalendarDay::class))->toBeTrue()
        ->and($user->can('view', $day))->toBeTrue()
        ->and($user->can('regenerate', CalendarDay::class))->toBeFalse();
});
