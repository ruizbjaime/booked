<?php

namespace Database\Seeders;

use App\Domain\Users\RoleConfig;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@localhost'],
            [
                'name' => 'Administrator',
                'is_active' => true,
                'password' => 'password',
            ],
        );

        $admin->syncRoles([RoleConfig::adminRole()]);
    }
}
