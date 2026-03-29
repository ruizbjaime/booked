<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use Database\Factories\PricingCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $level
 * @property string $color
 */
class PricingCategory extends Model
{
    /** @use HasFactory<PricingCategoryFactory> */
    use HasFactory, HasLocalizedName;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'en_name',
        'es_name',
        'level',
        'color',
        'multiplier',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'multiplier' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PricingRule, $this>
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
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
