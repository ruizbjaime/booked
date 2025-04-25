<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private const ADMIN_USER = [
        'name' => 'Admin',
        'email' => 'admin@localhost',
        'password' => 'password',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $user = User::updateOrCreate(
            ['email' => self::ADMIN_USER['email']],
            [
                'name' => self::ADMIN_USER['name'],
                'password' => Hash::make(self::ADMIN_USER['password']),
            ]
        );

        $user->assignRole('admin');

        User::factory(10)->withRoles('registered')->create();
        User::factory(10)->withRoles('owner')->create();
        User::factory(10)->withRoles('agent')->create();
        User::factory(5)->withRoles('owner', 'agent')->create();

    }
}
