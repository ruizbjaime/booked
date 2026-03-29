<?php

namespace App\Domain\Users;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class RoleConfig
{
    private static ?string $cachedDefaultRole = null;

    /** @var Collection<int, Role>|null */
    private static ?Collection $cachedRoles = null;

    public static function adminRole(): string
    {
        return Config::string('roles.admin_role');
    }

    public static function defaultRole(): string
    {
        if (self::$cachedDefaultRole !== null) {
            return self::$cachedDefaultRole;
        }

        $dbDefault = self::resolveRole(fn (Role $r): bool => (bool) $r->is_default);

        return self::$cachedDefaultRole = $dbDefault !== null ? $dbDefault->name : Config::string('roles.default_role');
    }

    public static function clearCache(): void
    {
        self::$cachedDefaultRole = null;
        self::$cachedRoles = null;
    }

    public static function defaultColor(): string
    {
        return Config::string('roles.default_color');
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        /** @var list<string> */
        return self::allRoles()
            ->filter(fn (Role $r): bool => (bool) $r->is_active)
            ->sortBy('sort_order')
            ->pluck('name')
            ->values()
            ->all();
    }

    public static function color(string $role): string
    {
        $dbRole = self::resolveRole(fn (Role $r): bool => $r->name === $role);

        return $dbRole !== null ? $dbRole->color : self::defaultColor();
    }

    public static function label(string $role): string
    {
        $dbRole = self::resolveRole(fn (Role $r): bool => $r->name === $role);

        if ($dbRole !== null) {
            return $dbRole->localizedLabel();
        }

        $key = 'users.roles.'.$role;
        $translation = __($key);

        return is_string($translation) && $translation !== $key
            ? $translation
            : Str::headline($role);
    }

    public static function isAdminRole(string $role): bool
    {
        return $role === self::adminRole();
    }

    public static function isSystemRole(string $role): bool
    {
        return self::isAdminRole($role) || $role === self::defaultRole();
    }

    /**
     * @param  callable(Role): bool  $predicate
     */
    private static function resolveRole(callable $predicate): ?Role
    {
        return self::allRoles()->first($predicate);
    }

    /**
     * @return Collection<int, Role>
     */
    private static function allRoles(): Collection
    {
        return self::$cachedRoles ??= Role::query()
            ->where('guard_name', 'web')
            ->get();
    }
}
