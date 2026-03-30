<?php

namespace Database\Seeders;

use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            RoleConfig::adminRole() => [
                'en_label' => 'Administrator',
                'es_label' => 'Administrador',
                'color' => 'red',
                'sort_order' => 1,
                'is_default' => false,
            ],
            'host' => [
                'en_label' => 'Host',
                'es_label' => 'Anfitrión',
                'color' => 'blue',
                'sort_order' => 2,
                'is_default' => false,
            ],
            Config::string('roles.default_role') => [
                'en_label' => 'Guest',
                'es_label' => 'Huésped',
                'color' => 'zinc',
                'sort_order' => 3,
                'is_default' => true,
            ],
        ];

        foreach ($roles as $name => $attributes) {
            Role::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                $attributes,
            );
        }

        RoleConfig::clearCache();

        $permissionNames = PermissionRegistry::allPermissionNames();

        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $adminRole = Role::query()->where('name', RoleConfig::adminRole())->where('guard_name', 'web')->firstOrFail();
        $adminPermissions = array_filter(
            $permissionNames,
            fn (string $name) => ! PermissionRegistry::isAdminExcludedPermission($name),
        );
        $adminRole->syncPermissions($adminPermissions);

        $hostRole = Role::query()->where('name', 'host')->where('guard_name', 'web')->firstOrFail();
        $hostRole->syncPermissions([
            ...(PermissionRegistry::permissionsGroupedByModel()['property'] ?? []),
            'calendar_day.viewAny',
            'calendar_day.view',
        ]);

        Cache::put('permissions:discovered_hash', PermissionRegistry::computeHash());
    }
}
