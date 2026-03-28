<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('scopeActive filters only active roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Role::factory()->create(['name' => 'active-test', 'is_active' => true]);
    Role::factory()->create(['name' => 'inactive-test', 'is_active' => false]);

    $activeNames = Role::query()->active()->pluck('name')->all();

    expect($activeNames)->toContain('active-test')
        ->and($activeNames)->not->toContain('inactive-test');
});

test('scopeSearch filters by name, en_label, and es_label', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Role::factory()->create(['name' => 'editor', 'en_label' => 'Editor', 'es_label' => 'Editor']);
    Role::factory()->create(['name' => 'viewer', 'en_label' => 'Viewer', 'es_label' => 'Visor']);

    expect(Role::query()->search('editor')->pluck('name')->all())->toBe(['editor'])
        ->and(Role::query()->search('Visor')->pluck('name')->all())->toBe(['viewer']);
});

test('is_active is cast to boolean', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create(['is_active' => true]);

    expect($role->fresh()->is_active)->toBeTrue()->toBeBool();
});

test('localizedLabel returns en_label when locale is en', function () {
    $role = Role::factory()->create([
        'en_label' => 'English Label',
        'es_label' => 'Spanish Label',
    ]);

    app()->setLocale('en');
    expect($role->localizedLabel())->toBe('English Label');

    app()->setLocale('es');
    expect($role->localizedLabel())->toBe('Spanish Label');
});

test('localizedLabel falls back to translation then headline', function () {
    $role = Role::factory()->create([
        'name' => 'unknown_test',
        'en_label' => null,
        'es_label' => null,
    ]);

    app()->setLocale('en');
    expect($role->localizedLabel())->toBe('Unknown Test');
});

test('localizedLabelColumn returns correct column for locale', function () {
    app()->setLocale('en');
    expect(Role::localizedLabelColumn())->toBe('en_label');

    app()->setLocale('es');
    expect(Role::localizedLabelColumn())->toBe('es_label');
});

test('users relationship returns users with the role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $role = Role::factory()->create();
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($role->users()->count())->toBe(1);
});

test('localizedLabel returns headline fallback when role labels are empty strings', function () {
    $role = Role::factory()->create([
        'name' => 'custom_role',
        'en_label' => '',
        'es_label' => '',
    ]);

    expect($role->localizedLabel())->toBe('Custom Role');
});

test('localizedLabel returns translation when labels are null and translation exists', function () {
    $role = Role::factory()->create([
        'name' => 'owner',
        'en_label' => null,
        'es_label' => null,
    ]);

    app()->setLocale('en');
    expect($role->localizedLabel())->toBe('Owner');
});
