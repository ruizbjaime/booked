<?php

namespace App\Models;

use App\Domain\Calendar\Enums\PricingRuleType;
use Database\Factories\PricingRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PricingRuleType $rule_type
 * @property array<string, mixed> $conditions
 * @property int $pricing_category_id
 */
class PricingRule extends Model
{
    /** @use HasFactory<PricingRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'en_description',
        'es_description',
        'pricing_category_id',
        'rule_type',
        'conditions',
        'priority',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rule_type' => PricingRuleType::class,
            'conditions' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function localizedDescription(): string
    {
        return app()->getLocale() === 'es' ? $this->es_description : $this->en_description;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function localizedDescriptionAttribute(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedDescription());
    }
}
