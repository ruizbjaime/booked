<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string|null $en_label
 * @property string|null $es_label
 * @property string $color
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_default
 */
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'en_label',
        'es_label',
        'color',
        'sort_order',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
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
            ->orWhere('en_label', 'like', "%{$escaped}%")
            ->orWhere('es_label', 'like', "%{$escaped}%")
        );
    }

    public function localizedLabel(): string
    {
        $label = (string) (app()->getLocale() === 'es' ? $this->es_label : $this->en_label);

        if ($label !== '') {
            return $label;
        }

        $key = 'users.roles.'.$this->name;
        $translation = __($key);

        return is_string($translation) && $translation !== $key
            ? $translation
            : Str::headline($this->name);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function defaultBadgeLabel(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->is_default) {
                return null;
            }

            $label = __('roles.index.columns.default');

            return is_string($label) ? $label : null;
        });
    }

    public static function localizedLabelColumn(): string
    {
        return app()->getLocale() === 'es' ? 'es_label' : 'en_label';
    }
}
