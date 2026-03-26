<?php

use App\Actions\Calendar\CreateSeasonBlock;
use App\Actions\Calendar\EditSeasonBlock;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\SeasonBlock;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'season-block-form';

    public string $mode = 'create';

    public ?int $seasonBlockId = null;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public string $calculation_strategy = 'fixed_range';

    public ?int $fixed_start_month = null;

    public ?int $fixed_start_day = null;

    public ?int $fixed_end_month = null;

    public ?int $fixed_end_day = null;

    public int $priority = 0;

    public int $sort_order = 0;

    public bool $is_active = true;

    /**
     * @var array<string, mixed>
     */
    public array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        $this->context = $context;
        $rawMode = $context['mode'] ?? 'create';
        $this->mode = is_string($rawMode) && in_array($rawMode, ['create', 'edit'], true) ? $rawMode : 'create';
        $this->seasonBlockId = is_numeric($context['seasonBlockId'] ?? null) ? (int) $context['seasonBlockId'] : null;

        match ($this->mode) {
            'edit' => $this->bootEditMode(),
            default => $this->bootCreateMode(),
        };
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'name',
            'en_name',
            'es_name',
            'calculation_strategy',
            'fixed_start_month',
            'fixed_start_day',
            'fixed_end_month',
            'fixed_end_day',
            'priority',
            'sort_order',
            'is_active',
        ], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateSeasonBlock $createSeasonBlock, EditSeasonBlock $editSeasonBlock): void
    {
        if ($this->throttle('save', 5)) {
            return;
        }

        $seasonBlock = match ($this->mode) {
            'edit' => $editSeasonBlock->handle($this->actor(), $this->seasonBlock(), $this->payload()),
            default => $createSeasonBlock->handle($this->actor(), $this->payload()),
        };

        $messageKey = $this->mode === 'edit'
            ? 'calendar.settings.season_block_form.updated'
            : 'calendar.settings.season_block_form.created';

        ToastService::success(__($messageKey, [
            'season_block' => $seasonBlock->label(),
        ]));

        $this->dispatch('close-form-modal');
        $this->dispatch('season-block-saved', seasonBlockId: $seasonBlock->id);
    }

    public function usesFixedRange(): bool
    {
        return $this->calculation_strategy === SeasonStrategy::FixedRange->value;
    }

    public function canEditStrategy(): bool
    {
        return $this->mode === 'create' || $this->usesFixedRange();
    }

    public function strategyLabel(): string
    {
        return __('calendar.season_strategies.'.$this->calculation_strategy);
    }

    private function bootCreateMode(): void
    {
        Gate::authorize('create', SeasonBlock::class);

        /** @var object{max_priority: int|null, max_sort_order: int|null}|null $maxValues */
        $maxValues = SeasonBlock::query()
            ->toBase()
            ->selectRaw('max(priority) as max_priority, max(sort_order) as max_sort_order')
            ->first();

        $this->priority = ($maxValues->max_priority ?? 0) + 1;
        $this->sort_order = ($maxValues->max_sort_order ?? 0) + 1;
        $this->calculation_strategy = SeasonStrategy::FixedRange->value;
    }

    private function bootEditMode(): void
    {
        $seasonBlock = $this->seasonBlock();

        Gate::authorize('update', $seasonBlock);

        $this->fillFromSeasonBlock($seasonBlock);
    }

    private function fillFromSeasonBlock(SeasonBlock $seasonBlock): void
    {
        $this->name = $seasonBlock->name;
        $this->en_name = $seasonBlock->en_name;
        $this->es_name = $seasonBlock->es_name;
        $this->calculation_strategy = $seasonBlock->calculation_strategy->value;
        $this->fixed_start_month = $seasonBlock->fixed_start_month;
        $this->fixed_start_day = $seasonBlock->fixed_start_day;
        $this->fixed_end_month = $seasonBlock->fixed_end_month;
        $this->fixed_end_day = $seasonBlock->fixed_end_day;
        $this->priority = $seasonBlock->priority;
        $this->sort_order = $seasonBlock->sort_order;
        $this->is_active = $seasonBlock->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'name' => $this->name,
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'calculation_strategy' => $this->calculation_strategy,
            'fixed_start_month' => $this->fixed_start_month,
            'fixed_start_day' => $this->fixed_start_day,
            'fixed_end_month' => $this->fixed_end_month,
            'fixed_end_day' => $this->fixed_end_day,
            'priority' => $this->priority,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }

    private function seasonBlock(): SeasonBlock
    {
        abort_if($this->seasonBlockId === null, 404);

        return SeasonBlock::query()->findOrFail($this->seasonBlockId);
    }
};
