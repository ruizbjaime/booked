<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Concerns\HasSearchScope;
use App\Concerns\HasSlug;
use Database\Factories\FeeTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeeType extends Model
{
    /** @use HasFactory<FeeTypeFactory> */
    use HasFactory, HasLocalizedName, HasSearchScope, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'en_name',
        'es_name',
        'order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    /**
     * @return BelongsToMany<ChargeBasis, $this, FeeTypeChargeBasis>
     */
    public function chargeBases(): BelongsToMany
    {
        return $this->belongsToMany(ChargeBasis::class, 'fee_type_charge_basis', 'fee_type_id', 'charge_basis_id')
            ->using(FeeTypeChargeBasis::class)
            ->withPivot(['id', 'is_active', 'is_default', 'sort_order', 'metadata'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('charge_bases.order');
    }
}
