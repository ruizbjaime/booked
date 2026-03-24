<?php

use App\Models\CalendarDay;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app.sidebar')]
class extends Component
{
    public CalendarDay $calendarDay;

    public function mount(string $date): void
    {
        $this->calendarDay = CalendarDay::query()
            ->where('date', $date)
            ->with(['holidayDefinition', 'seasonBlock', 'pricingCategory'])
            ->firstOrFail();

        Gate::authorize('view', $this->calendarDay);
    }

    #[Computed]
    public function categoryColor(): string
    {
        return $this->calendarDay->pricingCategory->color ?? '#6B7280';
    }

    #[Computed]
    public function previousDate(): ?string
    {
        $prev = CalendarDay::query()
            ->where('date', '<', $this->calendarDay->date)
            ->orderByDesc('date')
            ->first();

        return $prev?->date->toDateString();
    }

    #[Computed]
    public function nextDate(): ?string
    {
        $next = CalendarDay::query()
            ->where('date', '>', $this->calendarDay->date)
            ->orderBy('date')
            ->first();

        return $next?->date->toDateString();
    }
};
