<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Automatically generates and maintains a URL-friendly slug from a source field.
 *
 * By default, the slug is derived from the `name` field. Override `slugSourceField()`
 * to use a different source (e.g., `en_name` for models with localized display names).
 *
 * The slug is generated on creation (if not already set) and regenerated whenever
 * the source field changes on update. Separator is always `-` (hyphen).
 *
 * @mixin Model
 *
 * @property string $slug
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        $sourceField = static::slugSourceField();

        static::creating(function (Model $model) use ($sourceField): void {
            if (empty($model->getAttribute('slug'))) {
                $source = $model->getAttribute($sourceField);

                $model->setAttribute('slug', static::generateUniqueSlug(is_string($source) ? $source : ''));
            }
        });

        static::updating(function (Model $model) use ($sourceField): void {
            if ($model->isDirty($sourceField)) {
                $source = $model->getAttribute($sourceField);

                $model->setAttribute('slug', static::generateUniqueSlug(is_string($source) ? $source : '', $model));
            }
        });
    }

    /**
     * The model attribute used as the slug source.
     * Override in the model to use a different field (e.g., 'en_name').
     */
    public static function slugSourceField(): string
    {
        return 'name';
    }

    public static function generateUniqueSlug(string $source, ?Model $ignore = null): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = Str::slug(class_basename(static::class));
        }

        if (! static::slugExists($base, $ignore)) {
            return $base;
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = $base.'-'.static::randomAlphaSuffix(4);

            if (! static::slugExists($candidate, $ignore)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Failed to generate a unique slug after 10 attempts.');
    }

    protected static function slugExists(string $slug, ?Model $ignore = null): bool
    {
        $query = static::query()->where('slug', $slug);

        if ($ignore !== null && $ignore->exists) {
            $query->whereKeyNot($ignore->getKey());
        }

        return $query->exists();
    }

    protected static function randomAlphaSuffix(int $length): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $suffix = '';

        for ($i = 0; $i < $length; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $suffix;
    }
}
