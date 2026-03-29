<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Concerns\HasSearchScope;
use App\Concerns\HasSlug;
use Database\Factories\ChargeBasisFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChargeBasis extends Model
{
    /** @use HasFactory<ChargeBasisFactory> */
    use HasFactory, HasLocalizedName, HasSearchScope, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'en_name',
        'es_name',
        'en_description',
        'es_description',
        'order',
        'is_active',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public static function slugSourceField(): string
    {
        return 'en_name';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = static::escapeLikeTerm($term);

        return $query->where(function (Builder $q) use ($escaped): void {
            static::applyLikeSearch($q, 'slug', $escaped, useOr: false);
            static::applyLikeSearch($q, 'en_name', $escaped);
            static::applyLikeSearch($q, 'es_name', $escaped);
        });
    }

    public function localizedDescription(): ?string
    {
        return app()->getLocale() === 'es' ? $this->es_description : $this->en_description;
    }

    public static function localizedDescriptionColumn(): string
    {
        return app()->getLocale() === 'es' ? 'es_description' : 'en_description';
    }

    public function statusLabel(): string
    {
        return $this->is_active
            ? __('charge_bases.show.status.active')
            : __('charge_bases.show.status.inactive');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function localizedDescriptionAttribute(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->localizedDescription());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function statusLabelAttribute(): Attribute
    {
        return Attribute::get(fn (): string => $this->statusLabel());
    }

    /**
     * @return BelongsToMany<FeeType, $this, FeeTypeChargeBasis>
     */
    public function feeTypes(): BelongsToMany
    {
        return $this->belongsToMany(FeeType::class, 'fee_type_charge_basis', 'charge_basis_id', 'fee_type_id')
            ->using(FeeTypeChargeBasis::class)
            ->withPivot(['id', 'is_active', 'is_default', 'sort_order', 'metadata'])
            ->withTimestamps();
    }
}
