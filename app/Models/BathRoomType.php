<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Concerns\HasSearchScope;
use App\Concerns\HasSlug;
use Database\Factories\BathRoomTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BathRoomType extends Model
{
    /** @use HasFactory<BathRoomTypeFactory> */
    use HasFactory, HasLocalizedName, HasSearchScope, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'en_name',
        'es_name',
        'description',
        'sort_order',
    ];

    public static function slugSourceField(): string
    {
        return 'en_name';
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
            static::applyLikeSearch($q, 'description', $escaped);
        });
    }

    /**
     * @return BelongsToMany<Bedroom, $this, BathRoomTypeBedroom>
     */
    public function bedrooms(): BelongsToMany
    {
        return $this->belongsToMany(Bedroom::class)
            ->using(BathRoomTypeBedroom::class)
            ->withPivot(['id', 'quantity'])
            ->withTimestamps()
            ->orderBy('bedrooms.en_name');
    }

    /**
     * @return BelongsToMany<Property, $this, BathRoomTypeProperty>
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class)
            ->using(BathRoomTypeProperty::class)
            ->withPivot(['id', 'quantity'])
            ->withTimestamps()
            ->orderBy('properties.name');
    }
}
