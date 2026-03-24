<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\CalendarDayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $date
 * @property int|null $pricing_category_level
 * @property bool $is_holiday
 * @property bool $is_bridge_day
 * @property-read PricingCategory|null $pricingCategory
 */
class CalendarDay extends Model
{
    /** @use HasFactory<CalendarDayFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'year',
        'month',
        'day_of_week',
        'day_of_week_name',
        'is_holiday',
        'holiday_definition_id',
        'holiday_original_date',
        'holiday_observed_date',
        'holiday_group',
        'holiday_impact',
        'is_bridge_day',
        'season_block_id',
        'season_block_name',
        'pricing_category_id',
        'pricing_category_level',
        'is_quincena_adjacent',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'year' => 'integer',
            'month' => 'integer',
            'day_of_week' => 'integer',
            'is_holiday' => 'boolean',
            'holiday_original_date' => 'date',
            'holiday_observed_date' => 'date',
            'holiday_impact' => 'decimal:1',
            'is_bridge_day' => 'boolean',
            'pricing_category_level' => 'integer',
            'is_quincena_adjacent' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<HolidayDefinition, $this>
     */
    public function holidayDefinition(): BelongsTo
    {
        return $this->belongsTo(HolidayDefinition::class);
    }

    /**
     * @return BelongsTo<SeasonBlock, $this>
     */
    public function seasonBlock(): BelongsTo
    {
        return $this->belongsTo(SeasonBlock::class);
    }

    /**
     * @return BelongsTo<PricingCategory, $this>
     */
    public function pricingCategory(): BelongsTo
    {
        return $this->belongsTo(PricingCategory::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeHolidays(Builder $query): Builder
    {
        return $query->where('is_holiday', true);
    }
}
