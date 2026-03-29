<?php

use App\Actions\Countries\UpdateCountry;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

test('renders show page with country details', function () {
    $country = Country::factory()->create([
        'en_name' => 'Colombia',
        'es_name' => 'Colombia',
        'iso_alpha2' => 'CO',
        'iso_alpha3' => 'COL',
        'phone_code' => '+57',
        'sort_order' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->assertOk()
        ->assertSee('Colombia')
        ->assertSee('CO')
        ->assertSee('COL')
        ->assertSee('+57')
        ->assertSee(__('countries.show.status.active'));
});

test('autosaves field changes', function () {
    $country = Country::factory()->create([
        'en_name' => 'Old Name',
    ]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'New Name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($country->fresh()->en_name)->toBe('New Name');
});

test('validates unique ISO codes on autosave', function () {
    Country::factory()->create(['iso_alpha2' => 'CO']);

    $country = Country::factory()->create(['iso_alpha2' => 'AR']);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'details')
        ->set('iso_alpha2', 'CO')
        ->assertHasErrors(['iso_alpha2']);

    expect($country->fresh()->iso_alpha2)->toBe('AR');
});

test('active toggle autosaves', function () {
    $country = Country::factory()->create(['is_active' => true]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'details')
        ->set('is_active', false)
        ->assertDispatched('toast-show');

    expect($country->fresh()->is_active)->toBeFalse();
});

test('delete confirmation and redirect', function () {
    $country = Country::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('confirmCountryDeletion')
        ->assertSet('countryIdPendingDeletion', $country->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('countries.index'));

    expect(Country::query()->find($country->id))->toBeNull();
});

test('country with associated users is deactivated instead of deleted from show page', function () {
    $country = Country::factory()->create(['is_active' => true]);

    User::factory()->create(['country_id' => $country->id]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('confirmCountryDeletion')
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return ($params['title'] ?? null) === __('countries.show.quick_actions.deactivate.title');
        })
        ->dispatch('modal-confirmed')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        })
        ->assertNoRedirect();

    $fresh = Country::query()->find($country->id);

    expect($fresh)->not->toBeNull()
        ->and($fresh->is_active)->toBeFalse();
});

test('non-admin cannot view show page', function () {
    $country = Country::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->assertForbidden();
});

test('show page displays associated users count', function () {
    $country = Country::factory()->create();

    User::factory()->count(3)->create(['country_id' => $country->id]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->assertOk()
        ->assertSee('3');
});

test('cancel editing section restores original values and clears validation', function () {
    Country::factory()->create(['iso_alpha2' => 'CO']);

    $country = Country::factory()->create([
        'iso_alpha2' => 'AR',
    ]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'details')
        ->set('iso_alpha2', 'CO')
        ->assertHasErrors(['iso_alpha2'])
        ->call('cancelEditingSection')
        ->assertSet('iso_alpha2', 'AR')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $country = Country::factory()->create([
        'en_name' => 'Unchanged',
    ]);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->assertSet('editingSection', null)
        ->set('en_name', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($country->fresh()->en_name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $country = Country::factory()->create();

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

// --- Autosave normalization ---

test('autosave normalizes ISO codes to uppercase on show page', function () {
    $country = Country::factory()->create(['iso_alpha2' => 'OL', 'iso_alpha3' => 'OLD']);

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'details')
        ->set('iso_alpha2', 'nw')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($country->fresh()->iso_alpha2)->toBe('NW');
});

// --- Rate limiting tests ---

test('show page autosave is rate limited', function () {
    $country = Country::factory()->create();

    $component = Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("country-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('en_name', 'Rate Limited Name')
        ->assertDispatched('open-info-modal');
});

test('show page active toggle is rate limited', function () {
    $country = Country::factory()->create(['is_active' => true]);

    $component = Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("country-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->set('is_active', false)
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $country = Country::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("country-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('confirmCountryDeletion')
        ->assertDispatched('open-info-modal');
});

test('show page modal-confirmed is rate limited', function () {
    $country = Country::factory()->create();

    $component = Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('confirmCountryDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("country-mgmt:confirmed-action:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(Country::query()->find($country->id))->not->toBeNull();
});

// --- canEdit / canDelete ---

test('show page canEdit returns true for admin', function () {
    $country = Country::factory()->create();

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->assertSeeHtml('wire:click="startEditingSection');
});

test('show page canDelete returns true for admin', function () {
    $country = Country::factory()->create();

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->assertSeeHtml('wire:click="confirmCountryDeletion');
});

// --- Modal cancel on show page ---

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $country = Country::factory()->create();

    Livewire::test('pages::countries.show', ['country' => (string) $country->id])
        ->call('confirmCountryDeletion')
        ->assertSet('countryIdPendingDeletion', $country->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('countryIdPendingDeletion', null);
});

// --- Mount 404 ---

test('show page mount returns 404 for non-existent country', function () {
    $this->get('/countries/999999')
        ->assertNotFound();
});

// --- Update action aborts 422 for unknown field ---

test('update action aborts 422 for unknown field name', function () {
    $admin = makeAdmin();
    $country = Country::factory()->create();

    $action = app(UpdateCountry::class);

    try {
        $action->handle($admin, $country, 'nonexistent', 'value');
        $this->fail('Expected abort 422');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});

// --- Model scope tests ---

test('scopeActive filters only active countries', function () {
    Country::factory()->create(['iso_alpha2' => 'AC', 'is_active' => true]);
    Country::factory()->create(['iso_alpha2' => 'IN', 'is_active' => false]);

    $active = Country::query()->active()->pluck('iso_alpha2')->all();

    expect($active)->toContain('AC')
        ->and($active)->not->toContain('IN');
});

test('scopeSearch filters by en_name, es_name and phone_code', function () {
    Country::factory()->create(['en_name' => 'Colombia', 'es_name' => 'Colombia', 'phone_code' => '+57']);
    Country::factory()->create(['en_name' => 'Argentina', 'es_name' => 'Argentina', 'phone_code' => '+54']);

    expect(Country::query()->search('Colombia')->pluck('en_name')->all())->toBe(['Colombia'])
        ->and(Country::query()->search('+57')->pluck('en_name')->all())->toBe(['Colombia']);
});

// --- Localized name tests ---

test('localizedName returns es_name when locale is es', function () {
    $country = Country::factory()->create([
        'en_name' => 'Colombia',
        'es_name' => 'Colombia ES',
    ]);

    $originalLocale = app()->getLocale();

    try {
        app()->setLocale('es');
        expect($country->localizedName())->toBe('Colombia ES');

        app()->setLocale('en');
        expect($country->localizedName())->toBe('Colombia');
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('localizedNameColumn returns es_name when locale is es', function () {
    $originalLocale = app()->getLocale();

    try {
        app()->setLocale('es');
        expect(Country::localizedNameColumn())->toBe('es_name');

        app()->setLocale('en');
        expect(Country::localizedNameColumn())->toBe('en_name');
    } finally {
        app()->setLocale($originalLocale);
    }
});
