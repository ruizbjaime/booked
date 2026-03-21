<?php

namespace App\Models;

use Database\Factories\FeeTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeeType extends Model
{
    /** @use HasFactory<FeeTypeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
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
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$escaped}%")
            ->orWhere('en_name', 'like', "%{$escaped}%")
            ->orWhere('es_name', 'like', "%{$escaped}%")
        );
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
