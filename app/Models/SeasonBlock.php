<?php

namespace App\Models;

use App\Domain\Calendar\Enums\SeasonStrategy;
use Database\Factories\SeasonBlockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property SeasonStrategy $calculation_strategy
 */
class SeasonBlock extends Model
{
    /** @use HasFactory<SeasonBlockFactory> */
    use HasFactory;

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

    public function localizedName(): string
    {
        return app()->getLocale() === 'es' ? $this->es_name : $this->en_name;
    }

    public static function localizedNameColumn(): string
    {
        return app()->getLocale() === 'es' ? 'es_name' : 'en_name';
    }

    /**
     * @return Attribute<string, never>
     */
    protected function localizedNameAttribute(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedName());
    }
}
