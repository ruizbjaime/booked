<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Domain\Calendar\Enums\HolidayGroup;
use Database\Factories\HolidayDefinitionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property HolidayGroup $group
 * @property array<string, int> $base_impact_weights
 * @property list<array{location: string, dates: list<string>, impact: int}>|null $special_overrides
 */
class HolidayDefinition extends Model
{
    /** @use HasFactory<HolidayDefinitionFactory> */
    use HasFactory, HasLocalizedName;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'en_name',
        'es_name',
        'group',
        'month',
        'day',
        'easter_offset',
        'moves_to_monday',
        'base_impact_weights',
        'special_overrides',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => HolidayGroup::class,
            'month' => 'integer',
            'day' => 'integer',
            'easter_offset' => 'integer',
            'moves_to_monday' => 'boolean',
            'base_impact_weights' => 'array',
            'special_overrides' => 'array',
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
}
