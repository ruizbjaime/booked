<?php

namespace App\Concerns;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use WeakMap;

/**
 * @mixin Model
 *
 * @property string $slug
 */
trait HasSlug
{
    private const int MAX_GENERATION_ATTEMPTS = 10;

    private const int LOCK_SECONDS = 5;

    /**
     * @var WeakMap<Model, Lock>|null
     */
    private static ?WeakMap $slugLocks = null;

    public static function bootHasSlug(): void
    {
        $sourceField = static::slugSourceField();

        static::creating(function (Model $model) use ($sourceField): void {
            if (empty($model->getAttribute('slug'))) {
                static::assignGeneratedSlug($model, $sourceField, acquireLock: true);
            }
        });

        static::updating(function (Model $model) use ($sourceField): void {
            if ($model->isDirty($sourceField)) {
                static::assignGeneratedSlug($model, $sourceField, acquireLock: true);
            }
        });

        static::created(function (Model $model) use ($sourceField): void {
            try {
                static::resolvePersistedSlugCollision($model, $sourceField);
            } finally {
                static::releaseSlugLock($model);
            }
        });

        static::updated(function (Model $model) use ($sourceField): void {
            if ($model->wasChanged($sourceField)) {
                try {
                    static::resolvePersistedSlugCollision($model, $sourceField);
                } finally {
                    static::releaseSlugLock($model);
                }
            }
        });
    }

    public static function slugSourceField(): string
    {
        return 'name';
    }

    public static function generateUniqueSlug(string $source, ?Model $ignore = null): string
    {
        return static::generateUniqueSlugFromBase(static::slugBase($source), $ignore);
    }

    protected static function assignGeneratedSlug(Model $model, string $sourceField, bool $acquireLock = false): void
    {
        $source = $model->getAttribute($sourceField);
        $base = static::slugBase(is_string($source) ? $source : '');

        if ($acquireLock) {
            static::acquireSlugLock($model, $base);
        }

        $model->setAttribute('slug', static::generateUniqueSlugFromBase($base, $model));
    }

    protected static function resolvePersistedSlugCollision(Model $model, string $sourceField): void
    {
        if (! $model->exists) {
            return;
        }

        $currentSlug = $model->getAttribute('slug');

        if (! is_string($currentSlug) || ! static::slugExists($currentSlug, $model)) {
            return;
        }

        $source = $model->getAttribute($sourceField);
        $base = static::slugBase(is_string($source) ? $source : '');

        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $candidate = static::generateUniqueSlugFromBase($base, $model);

            try {
                $model->forceFill(['slug' => $candidate])->saveQuietly();

                return;
            } catch (QueryException $exception) {
                if (! static::isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Failed to persist a unique slug after 10 attempts.');
    }

    protected static function acquireSlugLock(Model $model, string $base): void
    {
        static::releaseSlugLock($model);

        $lock = Cache::lock(static::slugLockKey($base), self::LOCK_SECONDS);
        $lock->block(self::LOCK_SECONDS);

        static::slugLocks()->offsetSet($model, $lock);
    }

    protected static function releaseSlugLock(Model $model): void
    {
        $locks = static::slugLocks();

        if (! $locks->offsetExists($model)) {
            return;
        }

        /** @var Lock $lock */
        $lock = $locks[$model];
        $lock->release();
        $locks->offsetUnset($model);
    }

    protected static function generateUniqueSlugFromBase(string $base, ?Model $ignore = null): string
    {
        if (! static::slugExists($base, $ignore)) {
            return $base;
        }

        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $candidate = $base.'-'.static::randomAlphaSuffix(4);

            if (! static::slugExists($candidate, $ignore)) {
                return $candidate;
            }
        }

        return static::generateSequentialSlug($base, $ignore);
    }

    protected static function slugBase(string $source): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = Str::slug(class_basename(static::class));
        }

        return $base;
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

    protected static function generateSequentialSlug(string $base, ?Model $ignore = null): string
    {
        for ($sequence = 2; $sequence <= 1000; $sequence++) {
            $candidate = $base.'-'.$sequence;

            if (! static::slugExists($candidate, $ignore)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Failed to generate a unique slug after exhausting sequential fallbacks.');
    }

    protected static function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = Str::lower($exception->getMessage());

        return $sqlState === '23000'
            || $sqlState === '23505'
            || $driverCode === 19
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }

    /**
     * @return WeakMap<Model, Lock>
     */
    protected static function slugLocks(): WeakMap
    {
        return self::$slugLocks ??= new WeakMap;
    }

    protected static function slugLockKey(string $base): string
    {
        return 'slug-lock:'.static::class.':'.$base;
    }
}
