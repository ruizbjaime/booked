<?php

namespace App\Livewire\Calendar;

use App\Actions\Calendar\CreatePricingCategory;
use App\Actions\Calendar\EditPricingCategory;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\PricingCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PricingCategoryForm extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'pricing-category-form';

    /**
     * @var list<string>
     */
    private const array VALID_MODES = ['create', 'edit'];

    /**
     * @var list<string>
     */
    private const array RESETTABLE_FIELDS = [
        'name',
        'en_name',
        'es_name',
        'level',
        'color',
        'multiplier',
        'sort_order',
        'is_active',
    ];

    public string $mode = 'create';

    public ?int $pricingCategoryId = null;

    public string $name = '';

    public string $en_name = '';

    public string $es_name = '';

    public int $level = 1;

    public string $color = '#3B82F6';

    public string $multiplier = '1.00';

    public int $sort_order = 0;

    public bool $is_active = true;

    /**
     * @var array<string, mixed>
     */
    public array $context = [];

    private ?PricingCategory $resolvedPricingCategory = null;

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        $this->context = $context;
        $rawMode = $context['mode'] ?? 'create';
        $this->mode = is_string($rawMode) && in_array($rawMode, self::VALID_MODES, true) ? $rawMode : 'create';
        $this->pricingCategoryId = is_numeric($context['pricingCategoryId'] ?? null) ? (int) $context['pricingCategoryId'] : null;

        match ($this->mode) {
            'edit' => $this->bootEditMode(),
            default => $this->bootCreateMode(),
        };
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::RESETTABLE_FIELDS, true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreatePricingCategory $createPricingCategory, EditPricingCategory $editPricingCategory): void
    {
        if ($this->throttle('save', 5)) {
            return; // @codeCoverageIgnore — throttle mechanism tested via ThrottlesFormActionsTest
        }

        $payload = $this->payload();

        $pricingCategory = match ($this->mode) {
            'edit' => $editPricingCategory->handle($this->actor(), $this->pricingCategory(), $payload),
            default => $createPricingCategory->handle($this->actor(), $payload),
        };

        $messageKey = $this->mode === 'edit'
            ? 'calendar.settings.pricing_category_form.updated'
            : 'calendar.settings.pricing_category_form.created';

        ToastService::success(__($messageKey, [
            'category' => $this->pricingCategoryLabel($pricingCategory),
        ]));

        $this->dispatch('close-form-modal');
        $this->dispatch('pricing-category-saved', pricingCategoryId: $pricingCategory->id);
    }

    public function render(): View
    {
        return view('livewire.calendar.pricing-category-form');
    }

    private function bootCreateMode(): void
    {
        Gate::authorize('create', PricingCategory::class);

        $this->level = $this->firstAvailableLevel($this->usedLevels());
        $this->sort_order = $this->nextSortOrder();
    }

    private function bootEditMode(): void
    {
        $pricingCategory = $this->pricingCategory();

        Gate::authorize('update', $pricingCategory);

        $this->fillFromPricingCategory($pricingCategory);
    }

    private function fillFromPricingCategory(PricingCategory $pricingCategory): void
    {
        $this->name = $pricingCategory->name;
        $this->en_name = $pricingCategory->en_name;
        $this->es_name = $pricingCategory->es_name;
        $this->level = $pricingCategory->level;
        $this->color = $pricingCategory->color;
        $this->multiplier = (string) $pricingCategory->multiplier;
        $this->sort_order = $pricingCategory->sort_order;
        $this->is_active = $pricingCategory->is_active;
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
            'level' => $this->level,
            'color' => $this->color,
            'multiplier' => $this->multiplier,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }

    private function pricingCategory(): PricingCategory
    {
        abort_if($this->pricingCategoryId === null, 404);

        return $this->resolvedPricingCategory ??= PricingCategory::query()->findOrFail($this->pricingCategoryId);
    }

    /**
     * @param  array<int, int>  $usedLevels
     */
    private function firstAvailableLevel(array $usedLevels): int
    {
        foreach (range(1, 10) as $level) {
            if (! in_array($level, $usedLevels, true)) {
                return $level;
            }
        }

        return 10;
    }

    /**
     * @return array<int, int>
     */
    private function usedLevels(): array
    {
        return PricingCategory::query()
            ->orderBy('level')
            ->pluck('level')
            ->filter(fn (mixed $level): bool => is_numeric($level))
            ->map(fn (mixed $level): int => (int) $level)
            ->values()
            ->all();
    }

    private function nextSortOrder(): int
    {
        $rawSortOrder = PricingCategory::query()->max('sort_order');

        return is_numeric($rawSortOrder) ? ((int) $rawSortOrder) + 1 : 1;
    }

    private function pricingCategoryLabel(PricingCategory $pricingCategory): string
    {
        return __('calendar.settings.pricing_category_label', [
            'name' => $pricingCategory->name,
            'id' => $pricingCategory->id,
        ]);
    }
}
