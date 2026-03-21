<?php

use App\Models\ChargeBasis;
use App\Models\FeeType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChargeBasisSeeder;
use Database\Seeders\FeeTypeChargeBasisSeeder;
use Database\Seeders\FeeTypeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, ChargeBasisSeeder::class, FeeTypeSeeder::class, FeeTypeChargeBasisSeeder::class]);

    $this->actingAs(makeAdmin());
});

test('show page renders assigned charge bases', function () {
    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->assertSee(__('fee_types.show.sections.charge_bases'))
        ->assertSee('Por mascota')
        ->assertSee('Por mascota por noche');
});

test('show page can enter charge bases edit mode', function () {
    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'charge_bases')
        ->assertSet('editingSection', 'charge_bases')
        ->assertSee(__('fee_types.show.charge_bases.save'));
});

test('show page updates allowed charge bases only', function () {
    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();
    $perStay = ChargeBasis::query()->where('name', 'per_stay')->firstOrFail();
    $perPetPerNight = ChargeBasis::query()->where('name', 'per_pet_per_night')->firstOrFail();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'charge_bases')
        ->set('selectedChargeBases', [$perStay->id, $perPetPerNight->id])
        ->call('saveChargeBases')
        ->assertDispatched('toast-show');

    $feeType->refresh()->load('chargeBases');

    expect($feeType->chargeBases)->toHaveCount(2)
        ->and($feeType->chargeBases->pluck('id')->sort()->values()->all())->toBe([
            $perStay->id,
            $perPetPerNight->id,
        ]);
});

test('show page can remove deselected charge bases on save', function () {
    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();
    $perPet = ChargeBasis::query()->where('name', 'per_pet')->firstOrFail();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'charge_bases')
        ->set('selectedChargeBases', [$perPet->id])
        ->call('saveChargeBases')
        ->assertDispatched('toast-show');

    $feeType->refresh()->load('chargeBases');

    expect($feeType->chargeBases)->toHaveCount(1)
        ->and($feeType->chargeBases->first()?->id)->toBe($perPet->id);
});

test('show page preserves charge basis catalog metadata when saving selections', function () {
    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();
    $perPet = ChargeBasis::query()->where('name', 'per_pet')->firstOrFail();

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'charge_bases')
        ->set('selectedChargeBases', [$perPet->id])
        ->call('saveChargeBases')
        ->assertDispatched('toast-show');

    expect($perPet->fresh()->metadata['quantity_subject'])->toBe('pet')
        ->and($perPet->fresh()->metadata['requires_quantity'])->toBeTrue();
});

test('show page save charge bases is rate limited', function () {
    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit('fee-type-mgmt:save-charge-bases:'.app('auth')->id(), 60);
    }

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'charge_bases')
        ->call('saveChargeBases')
        ->assertStatus(429);
});

test('show page forbids saving charge bases for users without update permission', function () {
    $role = Role::factory()->create(['name' => 'fee-type-viewer-charge-bases']);
    $role->givePermissionTo('fee_type.viewAny', 'fee_type.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $feeType = FeeType::query()->where('name', 'pet-fee')->firstOrFail();

    $this->actingAs($user);

    Livewire::test('pages::fee-types.show', ['feeType' => (string) $feeType->id])
        ->call('startEditingSection', 'charge_bases')
        ->assertForbidden();
});
