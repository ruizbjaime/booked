<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    /**
     * Determine whether the user can view the system settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('system_setting.viewAny');
    }

    /**
     * Determine whether the user can update the system settings.
     */
    public function update(User $user, SystemSetting $setting): bool
    {
        return $user->checkPermissionTo('system_setting.update');
    }
}
