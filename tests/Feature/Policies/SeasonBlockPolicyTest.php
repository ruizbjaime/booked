<?php

use App\Models\SeasonBlock;
use App\Policies\SeasonBlockPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('season block policy allows and denies each ability based on permission', function (string $ability, bool $needsModel) {
    $policy = new SeasonBlockPolicy;
    $user = makeGuest();
    $permission = 'season_block.'.$ability;
    $block = $needsModel ? SeasonBlock::factory()->fixedRange(6, 1, 6, 30)->create() : null;

    expect($block === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $block))->toBeFalse();

    $user->givePermissionTo($permission);

    expect($block === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $block))->toBeTrue();
})->with([
    'viewAny' => ['viewAny', false],
    'view' => ['view', true],
    'create' => ['create', false],
    'update' => ['update', true],
    'delete' => ['delete', true],
]);
