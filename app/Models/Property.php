<?php

namespace App\Models;

use App\Concerns\HasSearchScope;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends Model implements HasMedia
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory, HasSearchScope, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'city',
        'address',
        'country_id',
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

        return $query->where(function (Builder $builder) use ($escaped): void {
            static::applyLikeSearch($builder, 'slug', $escaped, useOr: false);
            static::applyLikeSearch($builder, 'name', $escaped);
            static::applyLikeSearch($builder, 'city', $escaped);
            static::applyLikeSearch($builder, 'address', $escaped);
            $builder->orWhereHas('country', function (Builder $countryQuery) use ($escaped): void {
                static::applyLikeSearch($countryQuery, 'en_name', $escaped, useOr: false);
                static::applyLikeSearch($countryQuery, 'es_name', $escaped);
            });
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function avatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: null;
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }

    public function label(): string
    {
        return __('properties.property_label', [
            'name' => $this->name,
            'id' => $this->id,
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
