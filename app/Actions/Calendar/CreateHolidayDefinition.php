<?php

namespace App\Actions\Calendar;

use App\Models\HolidayDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CreateHolidayDefinition
{
    public function __construct(
        private readonly BuildHolidayDefinitionPayload $buildPayload = new BuildHolidayDefinitionPayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): HolidayDefinition
    {
        Gate::forUser($actor)->authorize('create', HolidayDefinition::class);

        $payload = $this->buildPayload->handle($input);

        $holiday = HolidayDefinition::create($payload);

        $this->freshnessTimestamp->stampModel($holiday, ['created_at', 'updated_at']);

        return $holiday->refresh();
    }
}
