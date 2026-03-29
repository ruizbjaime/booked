<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Concerns\HasSearchScope;
use App\Concerns\HasSlug;
use Database\Factories\PlatformFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    /** @use HasFactory<PlatformFactory> */
    use HasFactory, HasLocalizedName, HasSearchScope, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'en_name',
        'es_name',
        'color',
        'sort_order',
        'commission',
        'commission_tax',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'commission' => 'decimal:4',
            'commission_tax' => 'decimal:4',
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
}
