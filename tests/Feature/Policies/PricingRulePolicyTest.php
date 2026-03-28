<?php

use App\Models\PricingRule;
use App\Policies\PricingRulePolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('pricing rule policy allows and denies each ability based on permission', function (string $ability, bool $needsModel) {
    $policy = new PricingRulePolicy;
    $user = makeGuest();
    $permission = 'pricing_rule.'.$ability;
    $rule = $needsModel ? PricingRule::factory()->create() : null;

    expect($rule === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $rule))->toBeFalse();

    $user->givePermissionTo($permission);

    expect($rule === null ? $policy->{$ability}($user) : $policy->{$ability}($user, $rule))->toBeTrue();
})->with([
    'viewAny' => ['viewAny', false],
    'view' => ['view', true],
    'create' => ['create', false],
    'update' => ['update', true],
    'delete' => ['delete', true],
]);
