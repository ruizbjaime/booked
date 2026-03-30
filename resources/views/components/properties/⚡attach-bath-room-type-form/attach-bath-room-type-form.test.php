<?php

use App\Models\BathRoomType;
use App\Models\Bedroom;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->host = makeHost();
    $this->actingAs($this->host);
});

it('renders the attach bathroom type form with available options', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathRoomType = BathRoomType::factory()->create(['en_name' => 'Private Bathroom', 'es_name' => 'Baño privado']);

    Livewire::test('properties.attach-bath-room-type-form', ['context' => ['bedroom_id' => $bedroom->id]])
        ->assertStatus(200)
        ->assertSee(__('properties.show.accommodation.bath_room_types.fields.bath_room_type'))
        ->assertSee(__('properties.show.accommodation.bath_room_types.fields.quantity'))
        ->assertSee($bathRoomType->localizedName());
});

it('attaches the bathroom type with quantity', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bathRoomType = BathRoomType::factory()->create();

    Livewire::test('properties.attach-bath-room-type-form', ['context' => ['bedroom_id' => $bedroom->id]])
        ->set('bath_room_type_id', $bathRoomType->id)
        ->set('quantity', 2)
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('bedroom-bath-room-type-attached', bedroomId: $bedroom->id);

    expect($bedroom->fresh()->bathRoomTypes)->toHaveCount(1)
        ->and($bedroom->fresh()->bathRoomTypes->first()?->pivot->quantity)->toBe(2);
});

it('validates required bathroom type and quantity', function () {
    $property = Property::factory()->forUser($this->host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    Livewire::test('properties.attach-bath-room-type-form', ['context' => ['bedroom_id' => $bedroom->id]])
        ->set('bath_room_type_id', null)
        ->set('quantity', 0)
        ->call('save')
        ->assertHasErrors([
            'bath_room_type_id',
            'quantity',
        ]);
});
