<?php

namespace App\Models;

use Database\Factories\BedTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BedType extends Model
{
    /** @use HasFactory<BedTypeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_en',
        'name_es',
        'bed_capacity',
        'sort_order',
    ];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$escaped}%")
            ->orWhere('name_en', 'like', "%{$escaped}%")
            ->orWhere('name_es', 'like', "%{$escaped}%")
        );
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'es' ? $this->name_es : $this->name_en;
    }

    public static function localizedNameColumn(): string
    {
        return app()->getLocale() === 'es' ? 'name_es' : 'name_en';
    }

    /**
     * @return Attribute<string, never>
     */
    protected function localizedNameAttribute(): Attribute
    {
        return Attribute::get(fn (): string => $this->localizedName());
    }
}
