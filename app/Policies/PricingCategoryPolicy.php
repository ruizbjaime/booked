<?php

namespace App\Policies;

use App\Models\PricingCategory;
use App\Models\User;

class PricingCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('pricing_category.viewAny');
    }

    public function view(User $user, PricingCategory $pricingCategory): bool
    {
        return $user->checkPermissionTo('pricing_category.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('pricing_category.create');
    }

    public function update(User $user, PricingCategory $pricingCategory): bool
    {
        return $user->checkPermissionTo('pricing_category.update');
    }

    public function delete(User $user, PricingCategory $pricingCategory): bool
    {
        return $user->checkPermissionTo('pricing_category.delete');
    }
}
