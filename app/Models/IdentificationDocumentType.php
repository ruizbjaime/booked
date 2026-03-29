<?php

namespace App\Models;

use App\Concerns\HasLocalizedName;
use App\Concerns\HasSearchScope;
use Database\Factories\IdentificationDocumentTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdentificationDocumentType extends Model
{
    /** @use HasFactory<IdentificationDocumentTypeFactory> */
    use HasFactory, HasLocalizedName, HasSearchScope;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'en_name',
        'es_name',
        'sort_order',
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
        $escaped = static::escapeLikeTerm($term);

        return $query->where(function (Builder $q) use ($escaped): void {
            static::applyLikeSearch($q, 'code', $escaped, useOr: false);
            static::applyLikeSearch($q, 'en_name', $escaped);
            static::applyLikeSearch($q, 'es_name', $escaped);
        });
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'document_type_id');
    }
}
