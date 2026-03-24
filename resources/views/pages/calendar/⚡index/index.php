<?php

use App\Models\CalendarDay;
use App\Models\PricingCategory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'year')]
    public int $selectedYear = 0;

    /** @var array<string, array{level: int|null, isHoliday: bool, isBridge: bool}>|null */
    private ?array $cachedCalendarData = null;

    /** @var array<int, string>|null */
    private ?array $cachedColorMap = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', CalendarDay::class);

        if ($this->selectedYear === 0) {
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
        $data = collect($this->calendarData());

        return [
            'holidays' => $data->where('isHoliday', true)->count(),
            'bridges' => $data->where('isBridge', true)->count(),
            'cat_1' => $data->where('level', 1)->count(),
            'cat_2' => $data->where('level', 2)->count(),
            'cat_3' => $data->where('level', 3)->count(),
            'cat_4' => $data->where('level', 4)->count(),
        ];
    }

    /**
     * @return array<int, array<int, array<int, array{date: string, day: int, level: int|null, isHoliday: bool, isBridge: bool, color: string}|null>>>
     */
    #[Computed]
    public function monthGrids(): array
    {
        $calendarData = $this->calendarData();
        $colorMap = $this->colorMap();
        $defaultColor = '#374151';
        $grids = [];

        for ($month = 1; $month <= 12; $month++) {
            $firstDay = CarbonImmutable::createStrict($this->selectedYear, $month, 1);
            $daysInMonth = $firstDay->daysInMonth;
            $startOffset = $firstDay->dayOfWeekIso - 1;

            $weeks = [];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $pos = ($startOffset + $d - 1) % 7;
                $weekIndex = intdiv($startOffset + $d - 1, 7);

                if (! isset($weeks[$weekIndex])) {
                    $weeks[$weekIndex] = array_fill(0, 7, null);
                }

                $dateStr = sprintf('%d-%02d-%02d', $this->selectedYear, $month, $d);
                $dayData = $calendarData[$dateStr] ?? null;
                $level = $dayData['level'] ?? null;

                $weeks[$weekIndex][$pos] = [
                    'date' => $dateStr,
                    'day' => $d,
                    'level' => $level,
                    'isHoliday' => $dayData['isHoliday'] ?? false,
                    'isBridge' => $dayData['isBridge'] ?? false,
                    'color' => $level ? ($colorMap[$level] ?? $defaultColor) : $defaultColor,
                ];
            }

            $grids[$month] = $weeks;
        }

        return $grids;
    }

    #[Computed]
    public function hasCalendarData(): bool
    {
        return $this->calendarData() !== [];
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function colorMap(): array
    {
        if ($this->cachedColorMap !== null) {
            return $this->cachedColorMap;
        }

        /** @var array<int, string> $map */
        $map = PricingCategory::query()
            ->active()
            ->pluck('color', 'level')
            ->all();

        return $this->cachedColorMap = $map;
    }

    /**
     * @return array<string, array{level: int|null, isHoliday: bool, isBridge: bool}>
     */
    private function calendarData(): array
    {
        if ($this->cachedCalendarData !== null) {
            return $this->cachedCalendarData;
        }

        $data = [];

        CalendarDay::query()
            ->forYear($this->selectedYear)
            ->orderBy('date')
            ->each(function (CalendarDay $day) use (&$data): void {
                $data[$day->date->toDateString()] = [
                    'level' => $day->pricing_category_level,
                    'isHoliday' => $day->is_holiday,
                    'isBridge' => $day->is_bridge_day,
                ];
            });

        return $this->cachedCalendarData = $data;
    }
};
