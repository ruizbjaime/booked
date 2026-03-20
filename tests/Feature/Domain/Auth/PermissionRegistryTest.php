<?php

use App\Domain\Auth\PermissionRegistry;

afterEach(function () {
    PermissionRegistry::resetCache();
});

it('returns all permission names in model.ability format', function () {
    $names = PermissionRegistry::allPermissionNames();

    expect($names)->toBeArray()->not->toBeEmpty();

    foreach ($names as $name) {
        expect($name)->toContain('.');
    }
});

it('auto-discovers all existing policy models', function () {
    $keys = PermissionRegistry::modelKeys();

    expect($keys)->toContain('user')
        ->and($keys)->toContain('country')
        ->and($keys)->toContain('identification_document_type')
        ->and($keys)->toContain('role');
});

it('discovers abilities from policy methods', function () {
    expect(PermissionRegistry::abilitiesForModel('country'))
        ->toBe(['viewAny', 'view', 'create', 'update', 'delete']);
});

it('discovers user policy with extra abilities', function () {
    $abilities = PermissionRegistry::abilitiesForModel('user');

    expect($abilities)->toContain('viewAny')
        ->and($abilities)->toContain('restore')
        ->and($abilities)->toContain('forceDelete');
});

it('returns empty abilities for unknown model', function () {
    expect(PermissionRegistry::abilitiesForModel('nonexistent'))->toBe([]);
});

it('builds permission name correctly', function () {
    expect(PermissionRegistry::permissionName('country', 'viewAny'))->toBe('country.viewAny');
});

it('identifies admin protected permissions correctly', function () {
    expect(PermissionRegistry::isAdminProtectedPermission('user.viewAny'))->toBeTrue()
        ->and(PermissionRegistry::isAdminProtectedPermission('role.delete'))->toBeTrue()
        ->and(PermissionRegistry::isAdminProtectedPermission('country.viewAny'))->toBeFalse()
        ->and(PermissionRegistry::isAdminProtectedPermission('identification_document_type.create'))->toBeFalse();
});

it('returns admin protected models', function () {
    expect(PermissionRegistry::adminProtectedModels())->toBe(['user', 'role']);
});

it('groups permissions by model', function () {
    $grouped = PermissionRegistry::permissionsGroupedByModel();

    expect($grouped)->toHaveKeys(['user', 'country', 'identification_document_type', 'role'])
        ->and($grouped['country'])->toBe([
            'country.viewAny',
            'country.view',
            'country.create',
            'country.update',
            'country.delete',
        ]);
});

it('returns admin protected permissions list', function () {
    $protected = PermissionRegistry::adminProtectedPermissions();

    expect($protected)->toContain('user.viewAny')
        ->and($protected)->toContain('role.delete')
        ->and($protected)->not->toContain('country.viewAny');
});

it('caches discovery results', function () {
    $first = PermissionRegistry::discoverModelAbilities();
    $second = PermissionRegistry::discoverModelAbilities();

    expect($first)->toBe($second);
});

it('resets cache when requested', function () {
    PermissionRegistry::discoverModelAbilities();
    PermissionRegistry::resetCache();

    $result = PermissionRegistry::discoverModelAbilities();

    expect($result)->not->toBeEmpty();
});

it('returns model label from translation when available', function () {
    expect(PermissionRegistry::modelLabel('country'))->toBe(__('roles.show.permissions.models.country'));
});

it('returns headline fallback for unknown model label', function () {
    expect(PermissionRegistry::modelLabel('some_new_model'))->toBe('Some New Model');
});

it('returns ability label from translation when available', function () {
    expect(PermissionRegistry::abilityLabel('viewAny'))->toBe(__('roles.show.permissions.abilities.viewAny'));
});

it('returns headline fallback for unknown ability label', function () {
    expect(PermissionRegistry::abilityLabel('someCustomAbility'))->toBe('Some Custom Ability');
});
