<?php

namespace App\Domain\Auth;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class PermissionRegistry
{
    /**
     * @var list<string>
     */
    private const array ADMIN_PROTECTED_MODELS = ['user', 'role'];

    /**
     * @var list<string>
     */
    private const array EXCLUDED_METHODS = ['before', 'after', '__construct'];

    /**
     * @var array<string, list<string>>|null
     */
    private static ?array $discoveredAbilities = null;

    /**
     * @return array<string, list<string>>
     */
    public static function discoverModelAbilities(): array
    {
        if (self::$discoveredAbilities !== null) {
            return self::$discoveredAbilities;
        }

        $abilities = [];
        $policyPath = app_path('Policies');
        $policyFiles = glob("{$policyPath}/*Policy.php");

        if ($policyFiles === false) {
            self::$discoveredAbilities = [];

            return [];
        }

        foreach ($policyFiles as $file) {
            $className = 'App\\Policies\\'.basename($file, '.php');

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            $modelKey = self::policyToModelKey($reflection);

            if ($modelKey === null) {
                continue;
            }

            $methods = self::extractPublicAbilities($reflection, $className);

            if ($methods !== []) {
                $abilities[$modelKey] = $methods;
            }
        }

        self::$discoveredAbilities = $abilities;

        return $abilities;
    }

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        $permissions = [];

        foreach (self::discoverModelAbilities() as $model => $abilities) {
            foreach ($abilities as $ability) {
                $permissions[] = self::permissionName($model, $ability);
            }
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function modelKeys(): array
    {
        return array_keys(self::discoverModelAbilities());
    }

    /**
     * @return list<string>
     */
    public static function abilitiesForModel(string $modelKey): array
    {
        return self::discoverModelAbilities()[$modelKey] ?? [];
    }

    public static function permissionName(string $modelKey, string $ability): string
    {
        return "{$modelKey}.{$ability}";
    }

    /**
     * @return list<string>
     */
    public static function adminProtectedModels(): array
    {
        return self::ADMIN_PROTECTED_MODELS;
    }

    public static function isAdminProtectedPermission(string $permissionName): bool
    {
        $modelKey = Str::before($permissionName, '.');

        return in_array($modelKey, self::ADMIN_PROTECTED_MODELS, true);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function permissionsGroupedByModel(): array
    {
        $grouped = [];

        foreach (self::discoverModelAbilities() as $model => $abilities) {
            $grouped[$model] = array_map(
                fn (string $ability) => self::permissionName($model, $ability),
                $abilities,
            );
        }

        return $grouped;
    }

    /**
     * @return list<string>
     */
    public static function adminProtectedPermissions(): array
    {
        $permissions = [];
        $abilities = self::discoverModelAbilities();

        foreach (self::ADMIN_PROTECTED_MODELS as $model) {
            foreach ($abilities[$model] ?? [] as $ability) {
                $permissions[] = self::permissionName($model, $ability);
            }
        }

        return $permissions;
    }

    public static function modelLabel(string $modelKey): string
    {
        $key = "roles.show.permissions.models.{$modelKey}";
        $translation = __($key);

        return is_string($translation) && $translation !== $key
            ? $translation
            : Str::headline(str_replace('_', ' ', $modelKey));
    }

    public static function abilityLabel(string $ability): string
    {
        $key = "roles.show.permissions.abilities.{$ability}";
        $translation = __($key);

        return is_string($translation) && $translation !== $key
            ? $translation
            : Str::headline($ability);
    }

    public static function computeHash(): string
    {
        $names = self::allPermissionNames();
        sort($names);

        return md5(implode(',', $names));
    }

    public static function resetCache(): void
    {
        self::$discoveredAbilities = null;
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     */
    private static function policyToModelKey(ReflectionClass $reflection): ?string
    {
        $className = $reflection->getShortName();

        if (! str_ends_with($className, 'Policy')) {
            return null;
        }

        $modelName = substr($className, 0, -6);

        if ($modelName === '') {
            return null;
        }

        return Str::snake($modelName);
    }

    /**
     * @param  ReflectionClass<object>  $reflection
     * @param  class-string  $className
     * @return list<string>
     */
    private static function extractPublicAbilities(ReflectionClass $reflection, string $className): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if (in_array($method->getName(), self::EXCLUDED_METHODS, true)) {
                continue;
            }

            $methods[] = $method->getName();
        }

        return $methods;
    }
}
