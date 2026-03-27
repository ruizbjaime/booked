<?php

namespace App\Actions\Calendar;

use App\Models\HolidayDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteHolidayDefinition
{
    public function __construct(
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    public function handle(User $actor, HolidayDefinition $holiday): void
    {
        Gate::forUser($actor)->authorize('delete', $holiday);

        $holiday->delete();

        $this->stampLatestHoliday();
    }

    private function stampLatestHoliday(): void
    {
        $remaining = HolidayDefinition::query()->latest('updated_at')->first();

        if ($remaining === null) {
            $this->freshnessTimestamp->markConfigurationChanged();

            return;
        }

        $this->freshnessTimestamp->stampModel($remaining);
    }
}
