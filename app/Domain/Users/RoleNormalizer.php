<?php

namespace App\Domain\Users;

use App\Models\Role;

class RoleNormalizer
{
    /**
     * @param  list<string>  $roles
     * @param  array<int, string>|null  $allowedRoles
     * @return list<string>
     */
    public static function normalize(array $roles, ?array $allowedRoles = null): array
    {
        $normalizedRoles = array_values(array_unique($roles));

        if ($allowedRoles !== null) {
            $normalizedRoles = array_values(array_intersect($normalizedRoles, $allowedRoles));
        }

        $adminRole = RoleConfig::adminRole();

        if (in_array($adminRole, $normalizedRoles, true)) {
            return [$adminRole];
        }

        return $normalizedRoles;
    }

    /**
     * @return list<string>
     */
    public static function available(): array
    {
        /** @var list<string> */
        return Role::query()
            ->where('guard_name', 'web')
            ->active()
            ->orderBy('sort_order')
            ->pluck('name')
            ->all();
    }
}
