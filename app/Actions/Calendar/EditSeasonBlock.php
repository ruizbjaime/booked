<?php

namespace App\Actions\Calendar;

use App\Models\SeasonBlock;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditSeasonBlock
{
    public function __construct(
        private readonly BuildSeasonBlockPayload $buildSeasonBlockPayload = new BuildSeasonBlockPayload,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, SeasonBlock $seasonBlock, array $input): SeasonBlock
    {
        Gate::forUser($actor)->authorize('update', $seasonBlock);

        $payload = $this->buildSeasonBlockPayload->handle($input, $seasonBlock);

        if (! $seasonBlock->isFixedRange()) {
            $this->ensureManagedStrategyIsLocked($seasonBlock, $payload['calculation_strategy']);
            $payload = $this->preserveManagedSeasonConfiguration($seasonBlock, $payload);
        }

        $seasonBlock->update($payload);

        $this->freshnessTimestamp->stampModel($seasonBlock);

        return $seasonBlock->refresh();
    }

    private function ensureManagedStrategyIsLocked(SeasonBlock $seasonBlock, string $calculationStrategy): void
    {
        if ($calculationStrategy === $seasonBlock->calculation_strategy->value) {
            return;
        }

        throw ValidationException::withMessages([
            'calculation_strategy' => __('calendar.settings.validation.system_strategy_is_locked'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function preserveManagedSeasonConfiguration(SeasonBlock $seasonBlock, array $payload): array
    {
        $payload['fixed_start_month'] = $seasonBlock->fixed_start_month;
        $payload['fixed_start_day'] = $seasonBlock->fixed_start_day;
        $payload['fixed_end_month'] = $seasonBlock->fixed_end_month;
        $payload['fixed_end_day'] = $seasonBlock->fixed_end_day;
        $payload['calculation_strategy'] = $seasonBlock->calculation_strategy->value;

        return $payload;
    }
}
