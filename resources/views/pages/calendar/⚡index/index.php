<?php

use App\Models\CalendarDay;
use App\Models\PricingCategory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app.sidebar')]
class extends Component
{
    #[Url(as: 'year')]
    public int $selectedYear;

    public function mount(): void
    {
        Gate::authorize('viewAny', CalendarDay::class);

        if (! isset($this->selectedYear) || $this->selectedYear === 0) {
            $this->selectedYear = (int) now()->year;
        }
    }

    public function previousYear(): void
    {
        $this->selectedYear--;
    }

    public function nextYear(): void
    {
        $this->selectedYear++;
    }

    /**
     * @return Collection<string, CalendarDay>
     */
    #[Computed]
    public function days(): Collection
    {
        return CalendarDay::query()
            ->forYear($this->selectedYear)
            ->orderBy('date')
            ->get()
            ->keyBy(fn (CalendarDay $d): string => $d->date->toDateString());
    }

    /**
     * @return Collection<int, PricingCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return PricingCategory::query()
            ->active()
            ->orderBy('level')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        $days = $this->days();

        return [
            'holidays' => $days->where('is_holiday', true)->count(),
            'bridges' => $days->where('is_bridge_day', true)->count(),
            'cat_1' => $days->where('pricing_category_level', 1)->count(),
            'cat_2' => $days->where('pricing_category_level', 2)->count(),
            'cat_3' => $days->where('pricing_category_level', 3)->count(),
            'cat_4' => $days->where('pricing_category_level', 4)->count(),
        ];
    }

    /**
     * @return array<int, array<int, array<int, array{date: string, day: int, level: int|null, isHoliday: bool, isBridge: bool}|null>>>
     */
    #[Computed]
    public function monthGrids(): array
    {
        $grids = [];

        for ($month = 1; $month <= 12; $month++) {
            $firstDay = CarbonImmutable::createStrict($this->selectedYear, $month, 1);
            $daysInMonth = $firstDay->daysInMonth;
            // Monday=0 .. Sunday=6
            $startOffset = ($firstDay->dayOfWeekIso - 1);

            $weeks = [];
            $week = array_fill(0, 7, null);

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $pos = ($startOffset + $d - 1) % 7;
                $weekIndex = intdiv($startOffset + $d - 1, 7);

                if (! isset($weeks[$weekIndex])) {
                    $weeks[$weekIndex] = array_fill(0, 7, null);
                }

                $dateStr = sprintf('%d-%02d-%02d', $this->selectedYear, $month, $d);
                $calDay = $this->days()->get($dateStr);

                $weeks[$weekIndex][$pos] = [
                    'date' => $dateStr,
                    'day' => $d,
                    'level' => $calDay?->pricing_category_level,
                    'isHoliday' => (bool) ($calDay?->is_holiday),
                    'isBridge' => (bool) ($calDay?->is_bridge_day),
                ];
            }

            $grids[$month] = $weeks;
        }

        return $grids;
    }

    public function categoryColor(int $level): string
    {
        $category = $this->categories()->firstWhere('level', $level);

        return $category->color ?? '#6B7280';
    }
};
