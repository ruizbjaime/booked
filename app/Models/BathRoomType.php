<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Concerns\HasSearchScope;
use Database\Factories\BathRoomTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BathRoomType extends Model
{
    /** @use HasFactory<BathRoomTypeFactory> */
    use HasFactory, HasLocalizedName, HasSearchScope;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'en_name',
        'es_name',
        'description',
        'sort_order',
    ];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = static::escapeLikeTerm($term);

        return $query->where(function (Builder $q) use ($escaped): void {
            static::applyLikeSearch($q, 'name', $escaped, useOr: false);
            static::applyLikeSearch($q, 'en_name', $escaped);
            static::applyLikeSearch($q, 'es_name', $escaped);
            static::applyLikeSearch($q, 'description', $escaped);
        });
    }
}
