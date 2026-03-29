<?php

namespace App\Console\Commands;

use App\Domain\Auth\PermissionRegistry;
use App\Domain\Users\RoleConfig;
use App\Models\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

#[Signature('permissions:sync {--force : Skip hash check and sync regardless}')]
#[Description('Sync discovered permissions from policies to the database')]
class SyncPermissions extends Command
{
    public function handle(): int
    {
        $discovered = PermissionRegistry::allPermissionNames();
        $hash = PermissionRegistry::computeHash();

        if (! $this->option('force') && $hash === Cache::get('permissions:discovered_hash')) {
            $this->info('Permissions are up to date.');

            return self::SUCCESS;
        }

        /** @var list<string> $existing */
        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $missing = array_values(array_diff($discovered, $existing));
        $orphaned = array_diff($existing, $discovered);

        foreach ($missing as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        if ($orphaned !== []) {
            Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $orphaned)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->syncAdminRole($missing);

        Cache::put('permissions:discovered_hash', $hash);

        if ($missing !== []) {
            $this->info('Created '.count($missing).' new permission(s): '.implode(', ', $missing));
        }

        if ($orphaned !== []) {
            $this->info('Removed '.count($orphaned).' orphaned permission(s): '.implode(', ', $orphaned));
        }

        if ($missing === [] && $orphaned === []) {
            $this->info('Permissions are up to date.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $newPermissions
     */
    private function syncAdminRole(array $newPermissions): void
    {
        $adminRole = Role::query()
            ->where('name', RoleConfig::adminRole())
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole === null) {
            return;
        }

        $newForAdmin = array_filter(
            $newPermissions,
            fn (string $name) => ! PermissionRegistry::isAdminExcludedPermission($name),
        );

        if ($newForAdmin !== []) {
            $adminRole->givePermissionTo($newForAdmin);
        }

        /** @var list<string> $currentPermissions */
        $currentPermissions = $adminRole->permissions->pluck('name')->all();

        $excludedToRevoke = array_filter(
            $currentPermissions,
            fn (string $name) => PermissionRegistry::isAdminExcludedPermission($name),
        );

        if ($excludedToRevoke !== []) {
            $adminRole->revokePermissionTo($excludedToRevoke);
        }
    }
}
