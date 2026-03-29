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
    private const array ADMIN_EXCLUDED_MODELS = ['property'];

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

        if ($policyFiles === false) { // @codeCoverageIgnore
            self::$discoveredAbilities = []; // @codeCoverageIgnore

            return []; // @codeCoverageIgnore
        }

        foreach ($policyFiles as $file) {
            $className = 'App\\Policies\\'.basename($file, '.php');

            if (! class_exists($className)) { // @codeCoverageIgnore
                continue; // @codeCoverageIgnore
            }

            $reflection = new ReflectionClass($className);
            $modelKey = self::policyToModelKey($reflection);

            if ($modelKey === null) { // @codeCoverageIgnore
                continue; // @codeCoverageIgnore
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

    /**
     * @return list<string>
     */
    public static function adminExcludedModels(): array
    {
        return self::ADMIN_EXCLUDED_MODELS;
    }

    public static function isAdminProtectedPermission(string $permissionName): bool
    {
        $modelKey = Str::before($permissionName, '.');

        return in_array($modelKey, self::ADMIN_PROTECTED_MODELS, true);
    }

    public static function isAdminExcludedPermission(string $permissionName): bool
    {
        $modelKey = Str::before($permissionName, '.');

        return in_array($modelKey, self::ADMIN_EXCLUDED_MODELS, true);
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

    /**
     * @return list<string>
     */
    public static function adminExcludedPermissions(): array
    {
        $permissions = [];
        $abilities = self::discoverModelAbilities();

        foreach (self::ADMIN_EXCLUDED_MODELS as $model) {
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

        if (! str_ends_with($className, 'Policy')) { // @codeCoverageIgnore
            return null; // @codeCoverageIgnore
        }

        $modelName = substr($className, 0, -6);

        if ($modelName === '') { // @codeCoverageIgnore
            return null; // @codeCoverageIgnore
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
            if ($method->getDeclaringClass()->getName() !== $className) { // @codeCoverageIgnore
                continue; // @codeCoverageIgnore
            }

            if (in_array($method->getName(), self::EXCLUDED_METHODS, true)) { // @codeCoverageIgnore
                continue; // @codeCoverageIgnore
            }

            $methods[] = $method->getName();
        }

        return $methods;
    }
}
