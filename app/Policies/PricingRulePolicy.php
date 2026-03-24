<?php

namespace App\Policies;

use App\Models\PricingRule;
use App\Models\User;

class PricingRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('pricing_rule.viewAny');
    }

    public function view(User $user, PricingRule $pricingRule): bool
    {
        return $user->checkPermissionTo('pricing_rule.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('pricing_rule.create');
    }

    public function update(User $user, PricingRule $pricingRule): bool
    {
        return $user->checkPermissionTo('pricing_rule.update');
    }

    public function delete(User $user, PricingRule $pricingRule): bool
    {
        return $user->checkPermissionTo('pricing_rule.delete');
    }
}
