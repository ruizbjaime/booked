<?php

namespace App\Policies;

use App\Models\CalendarDay;
use App\Models\User;

class CalendarDayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('calendar_day.viewAny');
    }

    public function view(User $user, CalendarDay $calendarDay): bool
    {
        return $user->checkPermissionTo('calendar_day.view');
    }

    public function regenerate(User $user): bool
    {
        return $user->checkPermissionTo('calendar_day.regenerate');
    }
}
