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

        $missing = array_diff($discovered, $existing);

        foreach ($missing as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::query()
            ->where('name', RoleConfig::adminRole())
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole !== null) {
            $adminRole->syncPermissions($discovered);
        }

        Cache::put('permissions:discovered_hash', $hash);

        if ($missing === []) {
            $this->info('Permissions are up to date.');
        } else {
            $this->info('Created '.count($missing).' new permission(s): '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }
}
