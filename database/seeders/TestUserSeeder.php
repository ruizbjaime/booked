<?php

namespace Database\Seeders;

use App\Domain\Users\RoleConfig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestUserSeeder extends Seeder
{
    private const TOTAL = 50;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $nonAdminRoles = array_values(array_filter(
            RoleConfig::names(),
            fn (string $role) => ! RoleConfig::isAdminRole($role),
        ));

        $perRole = (int) floor(self::TOTAL / count($nonAdminRoles));
        $remainder = self::TOTAL % count($nonAdminRoles);

        DB::transaction(function () use ($nonAdminRoles, $perRole, $remainder): void {
            foreach ($nonAdminRoles as $i => $role) {
                $count = $perRole + ($i < $remainder ? 1 : 0);
                $this->createUsers($count, [$role]);
            }
        });
    }

    /** @param list<string> $roles */
    private function createUsers(int $count, array $roles): void
    {
        User::factory()
            ->count($count)
            ->create()
            ->each(fn (User $user) => $user->syncRoles($roles));
    }
}
