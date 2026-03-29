<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Domain\Calendar\Enums\SeasonStrategy;
use Database\Factories\SeasonBlockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property SeasonStrategy $calculation_strategy
 */
class SeasonBlock extends Model
{
    /** @use HasFactory<SeasonBlockFactory> */
    use HasFactory, HasLocalizedName;

    /**
     * @var list<string>
     */
    protected $fillable = [
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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'calculation_strategy' => SeasonStrategy::class,
            'fixed_start_month' => 'integer',
            'fixed_start_day' => 'integer',
            'fixed_end_month' => 'integer',
            'fixed_end_day' => 'integer',
            'priority' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CalendarDay, $this>
     */
    public function calendarDays(): HasMany
    {
        return $this->hasMany(CalendarDay::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isFixedRange(): bool
    {
        return $this->calculation_strategy === SeasonStrategy::FixedRange;
    }

    public function label(): string
    {
        return __('calendar.settings.season_block_label', [
            'name' => $this->name,
            'id' => $this->id,
        ]);
    }

    public function fixedRangeLabel(): string
    {
        if (! $this->isFixedRange()) {
            return '—';
        }

        if ($this->fixed_start_month === null || $this->fixed_start_day === null
            || $this->fixed_end_month === null || $this->fixed_end_day === null) {
            return '—';
        }

        return sprintf(
            '%02d-%02d → %02d-%02d',
            $this->fixed_start_month,
            $this->fixed_start_day,
            $this->fixed_end_month,
            $this->fixed_end_day,
        );
    }
}
