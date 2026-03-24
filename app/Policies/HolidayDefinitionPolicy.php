<?php

namespace App\Policies;

use App\Models\HolidayDefinition;
use App\Models\User;

class HolidayDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('holiday_definition.viewAny');
    }

    public function view(User $user, HolidayDefinition $holidayDefinition): bool
    {
        return $user->checkPermissionTo('holiday_definition.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('holiday_definition.create');
    }

    public function update(User $user, HolidayDefinition $holidayDefinition): bool
    {
        return $user->checkPermissionTo('holiday_definition.update');
    }

    public function delete(User $user, HolidayDefinition $holidayDefinition): bool
    {
        return $user->checkPermissionTo('holiday_definition.delete');
    }
}
