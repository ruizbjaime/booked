<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Models\SeasonBlock;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateSeasonBlock
{
    public function __construct(
        private readonly BuildSeasonBlockPayload $buildSeasonBlockPayload = new BuildSeasonBlockPayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): SeasonBlock
    {
        Gate::forUser($actor)->authorize('create', SeasonBlock::class);

        $payload = $this->buildSeasonBlockPayload->handle($input);

        if ($payload['calculation_strategy'] !== SeasonStrategy::FixedRange->value) {
            throw ValidationException::withMessages([
                'calculation_strategy' => __('calendar.settings.validation.custom_blocks_must_use_fixed_range'),
            ]);
        }

        $seasonBlock = SeasonBlock::create($payload);

        $this->freshnessTimestamp->stampModel($seasonBlock, ['created_at', 'updated_at']);

        return $seasonBlock->refresh();
    }
}
