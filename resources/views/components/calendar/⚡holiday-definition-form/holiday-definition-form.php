<?php

use App\Actions\Calendar\CreateHolidayDefinition;
use App\Actions\Calendar\EditHolidayDefinition;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Domain\Calendar\Enums\HolidayGroup;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\HolidayDefinition;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'holiday-definition-form';

    public string $mode = 'create';

    public ?int $holidayDefinitionId = null;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public string $group = 'fixed';

    public ?int $month = null;

    public ?int $day = null;

    public ?int $easter_offset = null;

    public bool $moves_to_monday = false;

    public string $base_impact_weights_json = '';

    public string $special_overrides_json = '';

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
        $this->holidayDefinitionId = is_numeric($context['holidayDefinitionId'] ?? null) ? (int) $context['holidayDefinitionId'] : null;

        if ($this->mode === 'edit') {
            $this->bootEditMode();
        } else {
            $this->bootCreateMode();
        }
    }

    public function updated(string $property): void
    {
        $errorField = match ($property) {
            'base_impact_weights_json' => 'base_impact_weights',
            'special_overrides_json' => 'special_overrides',
            default => $property,
        };

        if (in_array($property, [
            'name',
            'en_name',
            'es_name',
            'group',
            'month',
            'day',
            'easter_offset',
            'moves_to_monday',
            'base_impact_weights_json',
            'special_overrides_json',
            'sort_order',
            'is_active',
        ], true)) {
            $this->resetValidation($errorField);
        }
    }

    public function save(CreateHolidayDefinition $createAction, EditHolidayDefinition $editAction): void
    {
        if ($this->throttle('save', 5)) {
            return;
        }

        $holiday = match ($this->mode) {
            'edit' => $editAction->handle($this->actor(), $this->holidayDefinition(), $this->payload()),
            default => $createAction->handle($this->actor(), $this->payload()),
        };

        $messageKey = $this->mode === 'edit'
            ? 'calendar.settings.holiday_definition_form.updated'
            : 'calendar.settings.holiday_definition_form.created';

        ToastService::success(__($messageKey, [
            'holiday' => $holiday->localizedName(),
        ]));

        $this->dispatch('close-form-modal');
        $this->dispatch('holiday-definition-saved', holidayDefinitionId: $holiday->id);
    }

    public function isFixedOrEmiliani(): bool
    {
        return in_array($this->group, [HolidayGroup::Fixed->value, HolidayGroup::Emiliani->value], true);
    }

    public function isEasterBased(): bool
    {
        return $this->group === HolidayGroup::EasterBased->value;
    }

    private function bootCreateMode(): void
    {
        Gate::authorize('create', HolidayDefinition::class);

        /** @var object{max_sort_order: int|null}|null $maxValues */
        $maxValues = HolidayDefinition::query()
            ->toBase()
            ->selectRaw('max(sort_order) as max_sort_order')
            ->first();

        $this->sort_order = ($maxValues->max_sort_order ?? 0) + 1;
        $this->group = HolidayGroup::Fixed->value;
        $this->base_impact_weights_json = (string) json_encode([
            'monday' => 10,
            'friday' => 10,
            'tuesday' => 7,
            'thursday' => 7,
            'wednesday' => 4,
            'saturday' => 2,
            'sunday' => 2,
            'default' => 10,
        ], JSON_PRETTY_PRINT);
    }

    private function bootEditMode(): void
    {
        $holiday = $this->holidayDefinition();

        Gate::authorize('update', $holiday);

        $this->fillFromHolidayDefinition($holiday);
    }

    private function fillFromHolidayDefinition(HolidayDefinition $holiday): void
    {
        $this->name = $holiday->name;
        $this->en_name = $holiday->en_name;
        $this->es_name = $holiday->es_name;
        $this->group = $holiday->group->value;
        $this->month = $holiday->month;
        $this->day = $holiday->day;
        $this->easter_offset = $holiday->easter_offset;
        $this->moves_to_monday = $holiday->moves_to_monday;
        $this->base_impact_weights_json = (string) json_encode($holiday->base_impact_weights, JSON_PRETTY_PRINT);
        $this->special_overrides_json = $holiday->special_overrides !== null
            ? (string) json_encode($holiday->special_overrides, JSON_PRETTY_PRINT)
            : '';
        $this->sort_order = $holiday->sort_order;
        $this->is_active = $holiday->is_active;
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
            'group' => $this->group,
            'month' => $this->month,
            'day' => $this->day,
            'easter_offset' => $this->easter_offset,
            'moves_to_monday' => $this->moves_to_monday,
            'base_impact_weights' => $this->base_impact_weights_json,
            'special_overrides' => $this->special_overrides_json !== '' ? $this->special_overrides_json : null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }

    private function holidayDefinition(): HolidayDefinition
    {
        abort_if($this->holidayDefinitionId === null, 404);

        return HolidayDefinition::query()->findOrFail($this->holidayDefinitionId);
    }
};
