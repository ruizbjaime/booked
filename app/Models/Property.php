<?php

namespace App\Models;

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
    use HasFactory, InteractsWithMedia;

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
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

        return $query->where(fn (Builder $builder) => $builder
            ->where('slug', 'like', "%{$escaped}%")
            ->orWhere('name', 'like', "%{$escaped}%")
            ->orWhere('city', 'like', "%{$escaped}%")
            ->orWhere('address', 'like', "%{$escaped}%")
            ->orWhereHas('country', fn (Builder $countryQuery) => $countryQuery
                ->where('en_name', 'like', "%{$escaped}%")
                ->orWhere('es_name', 'like', "%{$escaped}%"))
        );
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
