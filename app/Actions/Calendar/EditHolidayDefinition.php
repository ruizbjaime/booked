<?php

namespace App\Actions\Calendar;

use App\Models\HolidayDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EditHolidayDefinition
{
    public function __construct(
        private readonly BuildHolidayDefinitionPayload $buildPayload = new BuildHolidayDefinitionPayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, HolidayDefinition $holiday, array $input): HolidayDefinition
    {
        Gate::forUser($actor)->authorize('update', $holiday);

        $payload = $this->buildPayload->handle($input, $holiday);

        $holiday->update($payload);

        $this->freshnessTimestamp->stampModel($holiday);

        return $holiday->refresh();
    }
}
