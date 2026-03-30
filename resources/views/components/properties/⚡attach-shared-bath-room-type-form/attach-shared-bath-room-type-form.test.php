<?php

use App\Models\BathRoomType;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->host = makeHost();
    $this->actingAs($this->host);
});

it('renders the attach shared bathroom type form with available options', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Shared Bathroom', 'es_name' => 'Baño compartido']);

    Livewire::test('properties.attach-shared-bath-room-type-form', ['context' => ['property_id' => $property->id]])
        ->assertStatus(200)
        ->assertSee(__('properties.show.accommodation.shared_bath_room_types.fields.bath_room_type'))
        ->assertSee(__('properties.show.accommodation.shared_bath_room_types.fields.quantity'))
        ->assertSee($bathRoomType->localizedName());
});

it('attaches the shared bathroom type with quantity', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bathRoomType = BathRoomType::factory()->create();

    Livewire::test('properties.attach-shared-bath-room-type-form', ['context' => ['property_id' => $property->id]])
        ->set('bath_room_type_id', $bathRoomType->id)
        ->set('quantity', 2)
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('property-shared-bath-room-type-attached', propertyId: $property->id);

    expect($property->fresh()->bathRoomTypes)->toHaveCount(1)
        ->and($property->fresh()->bathRoomTypes->first()?->pivot->quantity)->toBe(2);
});

it('validates required shared bathroom type and quantity', function () {
    $property = Property::factory()->forUser($this->host)->create();

    Livewire::test('properties.attach-shared-bath-room-type-form', ['context' => ['property_id' => $property->id]])
        ->set('bath_room_type_id', null)
        ->set('quantity', 0)
        ->call('save')
        ->assertHasErrors([
            'bath_room_type_id',
            'quantity',
        ]);
});
