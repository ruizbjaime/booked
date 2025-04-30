<?php

namespace Database\Seeders;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /** @var array<string> Available roles in the system */
    private const ROLES = ['admin', 'registered', 'owner', 'agent'];

    /** @var array<string> All available permissions */
    private const USER_PERMISSIONS = [
        'view any user',
        'view user',
        'create user',
        'update user',
        'delete user',
    ];

    /**
     * @var array<string, array<string>> Mapping of roles to their permissions
     */
    private const ROLE_PERMISSIONS = [
        'admin' => self::USER_PERMISSIONS,
        'registered' => ['view user', 'update user'],
        'owner' => ['view any user', 'view user', 'update user'],
        'agent' => ['view any user', 'view user', 'update user'],
    ];

    /**
     * @throws BindingResolutionException
     */
    public function run(): void
    {

        foreach (self::USER_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (self::ROLES as $roleName) {
            $role = Role::findOrCreate($roleName);
            if (isset(self::ROLE_PERMISSIONS[$roleName])) {
                $role->givePermissionTo(self::ROLE_PERMISSIONS[$roleName]);
            }
        }

        // Clear the cache
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
