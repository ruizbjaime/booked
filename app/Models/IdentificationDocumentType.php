<?php

namespace App\Models;

use Database\Factories\IdentificationDocumentTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdentificationDocumentType extends Model
{
    /** @use HasFactory<IdentificationDocumentTypeFactory> */
    use HasFactory;

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
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

        return $query->where(fn (Builder $q) => $q
            ->where('code', 'like', "%{$escaped}%")
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
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'document_type_id');
    }
}
