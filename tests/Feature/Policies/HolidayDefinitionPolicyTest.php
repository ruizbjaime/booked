<?php

use App\Models\HolidayDefinition;
use App\Policies\HolidayDefinitionPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('holiday definition policy allows and denies each ability based on permission', function (string $ability, bool $needsModel) {
    $policy = new HolidayDefinitionPolicy;
    $user = makeGuest();
    $permission = 'holiday_definition.'.$ability;
    $holiday = $needsModel ? HolidayDefinition::factory()->fixed()->create() : null;

    expect($holiday === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $holiday))->toBeFalse();

    $user->givePermissionTo($permission);

    expect($holiday === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $holiday))->toBeTrue();
})->with([
    'viewAny' => ['viewAny', false],
    'view' => ['view', true],
    'create' => ['create', false],
    'update' => ['update', true],
    'delete' => ['delete', true],
]);
