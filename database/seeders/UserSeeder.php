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
        'country_id' => 49,
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
                'country_id' => self::ADMIN_USER['country_id'],
            ]
        );

        $user->assignRole('admin');

        User::factory(50)->withRoles('registered')->create();
        User::factory(50)->withRoles('owner')->create();
        User::factory(50)->withRoles('agent')->create();
        User::factory(50)->withRoles('owner', 'agent')->create();

    }
}
