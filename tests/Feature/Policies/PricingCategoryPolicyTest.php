<?php

use App\Models\PricingCategory;
use App\Policies\PricingCategoryPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('pricing category policy allows and denies each ability based on permission', function (string $ability, bool $needsModel) {
    $policy = new PricingCategoryPolicy;
    $user = makeGuest();
    $permission = 'pricing_category.'.$ability;
    $category = $needsModel ? PricingCategory::factory()->create() : null;

    expect($category === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $category))->toBeFalse();

    $user->givePermissionTo($permission);

    expect($category === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $category))->toBeTrue();
})->with([
    'viewAny' => ['viewAny', false],
    'view' => ['view', true],
    'create' => ['create', false],
    'update' => ['update', true],
    'delete' => ['delete', true],
]);
