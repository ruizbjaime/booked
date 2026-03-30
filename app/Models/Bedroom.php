<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Concerns\HasSearchScope;
use App\Concerns\HasSlug;
use Database\Factories\BedroomFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bedroom extends Model
{
    /** @use HasFactory<BedroomFactory> */
    use HasFactory, HasLocalizedName, HasSearchScope, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'slug',
        'en_name',
        'es_name',
        'en_description',
        'es_description',
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

        return $query->where(function (Builder $query) use ($escaped): void {
            static::applyLikeSearch($query, 'slug', $escaped, useOr: false);
            static::applyLikeSearch($query, 'en_name', $escaped);
            static::applyLikeSearch($query, 'es_name', $escaped);
            static::applyLikeSearch($query, 'en_description', $escaped);
            static::applyLikeSearch($query, 'es_description', $escaped);
        });
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsToMany<BedType, $this, BedTypeBedroom>
     */
    public function bedTypes(): BelongsToMany
    {
        return $this->belongsToMany(BedType::class)
            ->using(BedTypeBedroom::class)
            ->withPivot(['id', 'quantity'])
            ->withTimestamps()
            ->orderBy('bed_types.sort_order');
    }

    /**
     * @return BelongsToMany<BathRoomType, $this, BathRoomTypeBedroom>
     */
    public function bathRoomTypes(): BelongsToMany
    {
        return $this->belongsToMany(BathRoomType::class)
            ->using(BathRoomTypeBedroom::class)
            ->withPivot(['id', 'quantity'])
            ->withTimestamps()
            ->orderBy('bath_room_types.sort_order');
    }
}
