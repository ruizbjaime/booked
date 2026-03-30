<?php

use App\Models\Bedroom;
use App\Models\BedType;
use App\Models\Property;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('renders successfully', function () {
    $host = makeHost();
    $this->actingAs($host);

    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    BedType::factory()->create(['is_active' => true, 'en_name' => 'Queen Bed', 'es_name' => 'Cama Queen']);

    Livewire::test('properties.attach-bed-type-form', ['context' => ['bedroom_id' => $bedroom->id]])
        ->assertOk()
        ->assertSee(__('properties.show.accommodation.bed_types.fields.bed_type'))
        ->assertSee(__('properties.show.accommodation.bed_types.fields.quantity'))
        ->assertSee('Cama Queen');
});

it('creates the association and dispatches close and refresh events', function () {
    $host = makeHost();
    $this->actingAs($host);

    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);
    $bedType = BedType::factory()->create(['is_active' => true]);

    Livewire::test('properties.attach-bed-type-form', ['context' => ['bedroom_id' => $bedroom->id]])
        ->set('bed_type_id', $bedType->id)
        ->set('quantity', 2)
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('bedroom-bed-type-attached');

    expect($bedroom->fresh()->bedTypes->first()?->pivot->quantity)->toBe(2);
});

it('shows validation errors for invalid modal data', function () {
    $host = makeHost();
    $this->actingAs($host);

    $property = Property::factory()->forUser($host)->create();
    $bedroom = Bedroom::factory()->create(['property_id' => $property->id]);

    Livewire::test('properties.attach-bed-type-form', ['context' => ['bedroom_id' => $bedroom->id]])
        ->set('quantity', 0)
        ->call('save')
        ->assertHasErrors(['bed_type_id', 'quantity']);
});
