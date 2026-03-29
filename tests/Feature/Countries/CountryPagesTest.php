<?php

use App\Infrastructure\UiFeedback\ModalService;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

function countriesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::countries.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the countries index page', function () {
    $this->get(route('countries.index'))
        ->assertOk()
        ->assertSeeText(__('countries.index.title'));
});

test('admins can visit the countries show page', function () {
    $country = Country::factory()->create([
        'en_name' => 'Colombia',
        'es_name' => 'Colombia',
        'iso_alpha2' => 'CO',
        'iso_alpha3' => 'COL',
        'phone_code' => '+57',
        'is_active' => true,
    ]);

    $this->get(route('countries.show', $country))
        ->assertOk()
        ->assertSeeText(__('countries.show.placeholder_title'))
        ->assertSeeText('Colombia')
        ->assertSeeText('CO')
        ->assertSeeText('COL')
        ->assertSeeText('+57');
});

test('non admins cannot visit the countries index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('countries.index'))->assertForbidden();
});

test('non admins cannot visit the countries show page', function () {
    $country = Country::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('countries.show', $country))->assertForbidden();
});

test('sidebar hides the countries navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('Parameterization'))
        ->assertDontSeeText(__('countries.navigation.label'));
});

test('sidebar shows the countries navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('Parameterization'))
        ->assertSeeText(__('countries.navigation.label'));
});

test('countries index sorts by sort_order asc by default', function () {
    Country::factory()->create([
        'en_name' => 'Zulu Country',
        'es_name' => 'Zulu País',
        'sort_order' => 200,
    ]);

    Country::factory()->create([
        'en_name' => 'Alpha Country',
        'es_name' => 'Alpha País',
        'sort_order' => 100,
    ]);

    countriesIndexComponent()
        ->assertSeeInOrder(['Alpha', 'Zulu'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'asc');
});

test('countries index can sort by localized name', function () {
    $nameColumn = Country::localizedNameColumn();

    Country::factory()->create([$nameColumn => 'Zulu Country']);
    Country::factory()->create([$nameColumn => 'Alpha Country']);

    countriesIndexComponent()
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Alpha Country', 'Zulu Country'])
        ->assertSet('sortBy', $nameColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Zulu Country', 'Alpha Country'])
        ->assertSet('sortDirection', 'desc');
});

test('countries index can sort by created_at', function () {
    $nameColumn = Country::localizedNameColumn();

    Country::factory()->create([
        $nameColumn => 'Older Country',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    Country::factory()->create([
        $nameColumn => 'Newest Country',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    countriesIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest Country', 'Older Country'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('countries index search filters by name', function () {
    Country::factory()->create([
        'en_name' => 'Colombia',
        'es_name' => 'Colombia',
    ]);
    Country::factory()->create([
        'en_name' => 'Argentina',
        'es_name' => 'Argentina',
    ]);

    countriesIndexComponent()
        ->set('search', 'Colombia')
        ->assertSee('Colombia')
        ->assertDontSee('Argentina');
});

test('countries index search filters by phone_code', function () {
    $nameColumn = Country::localizedNameColumn();

    Country::factory()->create([
        $nameColumn => 'Colombia',
        'phone_code' => '+57',
    ]);
    Country::factory()->create([
        $nameColumn => 'Argentina',
        'phone_code' => '+54',
    ]);

    countriesIndexComponent()
        ->set('search', '+57')
        ->assertSee('Colombia')
        ->assertDontSee('Argentina');
});

test('admin can open the country create modal from the countries index', function () {
    $component = countriesIndexComponent()
        ->call('openCreateCountryModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'countries.create'
            && ($dispatch['params']['title'] ?? null) === __('countries.create.title')
            && ($dispatch['params']['description'] ?? null) === __('countries.create.description');
    }))->toBeTrue();
});

test('admin can create a country from the create modal', function () {
    Livewire::test('countries.create-country-form')
        ->assertSet('is_active', true)
        ->assertSet('sort_order', 999)
        ->set('en_name', 'Test Country')
        ->set('es_name', 'País de Prueba')
        ->set('iso_alpha2', 'TC')
        ->set('iso_alpha3', 'TST')
        ->set('phone_code', '+99')
        ->set('sort_order', 100)
        ->call('save')
        ->assertSet('en_name', '')
        ->assertSet('es_name', '')
        ->assertSet('iso_alpha2', '')
        ->assertSet('iso_alpha3', '')
        ->assertSet('phone_code', '')
        ->assertSet('sort_order', 999)
        ->assertSet('is_active', true)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('country-created');

    $created = Country::query()->where('iso_alpha2', 'TC')->first();

    expect($created)->not->toBeNull()
        ->and($created?->en_name)->toBe('Test Country')
        ->and($created?->es_name)->toBe('País de Prueba')
        ->and($created?->iso_alpha3)->toBe('TST')
        ->and($created?->phone_code)->toBe('+99')
        ->and($created?->sort_order)->toBe(100)
        ->and($created?->is_active)->toBeTrue();
});

test('create form validates duplicate iso_alpha2', function () {
    Country::factory()->create(['iso_alpha2' => 'CO']);

    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Duplicate')
        ->set('es_name', 'Duplicado')
        ->set('iso_alpha2', 'CO')
        ->set('iso_alpha3', 'DUP')
        ->set('phone_code', '+00')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['iso_alpha2'])
        ->assertNotDispatched('country-created');
});

test('create form validates duplicate iso_alpha3', function () {
    Country::factory()->create(['iso_alpha3' => 'COL']);

    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Duplicate')
        ->set('es_name', 'Duplicado')
        ->set('iso_alpha2', 'DU')
        ->set('iso_alpha3', 'COL')
        ->set('phone_code', '+00')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['iso_alpha3'])
        ->assertNotDispatched('country-created');
});

test('admin can toggle country active status', function () {
    $country = Country::factory()->create([
        'en_name' => 'Toggle Me',
        'is_active' => false,
    ]);

    countriesIndexComponent()
        ->call('toggleCountryActiveStatus', $country->id, 'is_active', true)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($country->fresh()->is_active)->toBeTrue();
});

test('admin can deactivate a country', function () {
    $country = Country::factory()->create([
        'en_name' => 'Deactivate Me',
        'is_active' => true,
    ]);

    countriesIndexComponent()
        ->call('toggleCountryActiveStatus', $country->id, 'is_active', false)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($country->fresh()->is_active)->toBeFalse();
});

function countryDeleteModalMessage(Country $country): string
{
    return __('countries.index.confirm_delete.message', [
        'country' => __('countries.country_label', ['name' => $country->localizedName(), 'id' => $country->id]),
    ]);
}

test('admin can delete a country from the countries index', function () {
    $country = Country::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    countriesIndexComponent()
        ->call('confirmCountryDeletion', $country->id)
        ->assertSet('countryIdPendingDeletion', $country->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($country) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('countries.index.confirm_delete.title')
                && ($params['message'] ?? null) === countryDeleteModalMessage($country)
                && ($params['confirmLabel'] ?? null) === __('countries.index.confirm_delete.confirm_label')
                && ($params['variant'] ?? null) === ModalService::VARIANT_PASSWORD;
        })
        ->dispatch('modal-confirmed')
        ->assertSet('countryIdPendingDeletion', null)
        ->assertDontSee('Delete Me');

    expect(Country::query()->find($country->id))->toBeNull();
});

test('country with associated users is deactivated instead of deleted', function () {
    $country = Country::factory()->create([
        'en_name' => 'Has Users',
        'is_active' => true,
    ]);

    User::factory()->create(['country_id' => $country->id]);

    countriesIndexComponent()
        ->call('confirmCountryDeletion', $country->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return ($params['title'] ?? null) === __('countries.index.confirm_deactivate.title');
        })
        ->dispatch('modal-confirmed')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    $fresh = Country::query()->find($country->id);

    expect($fresh)->not->toBeNull()
        ->and($fresh->is_active)->toBeFalse();
});

test('countries index clears a pending deletion when the confirm modal is cancelled', function () {
    $country = Country::factory()->create();

    countriesIndexComponent()
        ->call('confirmCountryDeletion', $country->id)
        ->assertSet('countryIdPendingDeletion', $country->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('countryIdPendingDeletion', null);
});

test('countries pages render Spanish translations', function () {
    $originalLocale = app()->getLocale();

    app()->setLocale('es');

    try {
        $this->actingAs(makeAdmin());

        $this->get(route('countries.index'))
            ->assertOk()
            ->assertSeeText(__('countries.navigation.label'))
            ->assertSeeText(__('countries.index.description'))
            ->assertSeeText(__('countries.index.create_action'));

        $country = Country::factory()->create();

        $this->get(route('countries.show', $country))
            ->assertOk()
            ->assertSeeText(__('countries.show.placeholder_title'));
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('countries pages render successfully as livewire components', function () {
    countriesIndexComponent()
        ->assertOk()
        ->assertSee(__('countries.index.title'));

    Livewire::test('countries.create-country-form')
        ->assertOk()
        ->assertSee(__('countries.create.fields.en_name'))
        ->assertSee(__('countries.create.fields.es_name'))
        ->assertSee(__('countries.create.fields.iso_alpha2'))
        ->assertSee(__('countries.create.fields.iso_alpha3'))
        ->assertSee(__('countries.create.fields.phone_code'))
        ->assertSee(__('countries.create.fields.sort_order'))
        ->assertSee(__('countries.create.submit'));

    $showCountry = Country::factory()->create();

    Livewire::test('pages::countries.show', ['country' => (string) $showCountry->id])
        ->assertOk()
        ->assertSee(__('countries.show.placeholder_title'))
        ->assertSee($showCountry->en_name);
});

test('non admins cannot trigger country deletion from the countries index', function () {
    $this->actingAs(makeGuest());

    Livewire::test('pages::countries.index')
        ->assertForbidden();
});

test('countries index search input defines non-auth autofill metadata', function () {
    countriesIndexComponent()
        ->assertSeeHtml('name="countries_search"')
        ->assertSeeHtml('id="countries-search"')
        ->assertSeeHtml('autocomplete="off"');
});

test('admin can create an inactive country from the create modal', function () {
    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Inactive Country')
        ->set('es_name', 'País Inactivo')
        ->set('iso_alpha2', 'IC')
        ->set('iso_alpha3', 'INC')
        ->set('phone_code', '+88')
        ->set('sort_order', 50)
        ->set('is_active', false)
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('country-created');

    $created = Country::query()->where('iso_alpha2', 'IC')->first();

    expect($created)->not->toBeNull()
        ->and($created?->is_active)->toBeFalse();
});

test('create form validates required fields', function () {
    Livewire::test('countries.create-country-form')
        ->set('en_name', '')
        ->set('es_name', '')
        ->set('iso_alpha2', '')
        ->set('iso_alpha3', '')
        ->set('phone_code', '')
        ->call('save')
        ->assertHasErrors(['en_name', 'es_name', 'iso_alpha2', 'iso_alpha3', 'phone_code'])
        ->assertNotDispatched('country-created');
});

test('create form normalizes ISO codes to uppercase', function () {
    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Lowercase Test')
        ->set('es_name', 'Test Minúsculas')
        ->set('iso_alpha2', 'lc')
        ->set('iso_alpha3', 'low')
        ->set('phone_code', '+11')
        ->set('sort_order', 1)
        ->call('save')
        ->assertDispatched('country-created');

    $created = Country::query()->where('en_name', 'Lowercase Test')->first();

    expect($created)->not->toBeNull()
        ->and($created?->iso_alpha2)->toBe('LC')
        ->and($created?->iso_alpha3)->toBe('LOW');
});

test('create form rejects non-alphabetic ISO codes', function () {
    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Bad ISO')
        ->set('es_name', 'ISO Malo')
        ->set('iso_alpha2', '!@')
        ->set('iso_alpha3', '1AB')
        ->set('phone_code', '+55')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['iso_alpha2', 'iso_alpha3'])
        ->assertNotDispatched('country-created');
});

test('create form rejects invalid phone code format', function () {
    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Bad Phone')
        ->set('es_name', 'Teléfono Malo')
        ->set('iso_alpha2', 'BP')
        ->set('iso_alpha3', 'BPH')
        ->set('phone_code', 'abc')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['phone_code'])
        ->assertNotDispatched('country-created');
});

test('countries index can sort by sort_order column', function () {
    $nameColumn = Country::localizedNameColumn();

    Country::factory()->create([$nameColumn => 'High Order', 'sort_order' => 500]);
    Country::factory()->create([$nameColumn => 'Low Order', 'sort_order' => 10]);

    countriesIndexComponent()
        ->assertSeeInOrder(['Low Order', 'High Order'])
        ->call('sort', 'sort_order')
        ->assertSeeInOrder(['High Order', 'Low Order'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'desc');
});

test('countries index resets page when a country is created', function () {
    Country::factory()->count(15)->create();

    countriesIndexComponent()
        ->set('perPage', 10)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->dispatch('country-created')
        ->assertSet('paginators.page', 1);
});

// --- Rate limiting tests ---

test('index toggle active status is rate limited', function () {
    $country = Country::factory()->create(['is_active' => false]);

    $component = countriesIndexComponent();

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("country-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->call('toggleCountryActiveStatus', $country->id, 'is_active', true)
        ->assertDispatched('open-info-modal');
});

test('index delete confirmation is rate limited', function () {
    $country = Country::factory()->create();

    $component = countriesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("country-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->call('confirmCountryDeletion', $country->id)
        ->assertDispatched('open-info-modal');
});

test('index modal-confirmed delete is rate limited', function () {
    $country = Country::factory()->create();

    $component = countriesIndexComponent()
        ->call('confirmCountryDeletion', $country->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("country-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(Country::query()->find($country->id))->not->toBeNull();
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("country-mgmt:create:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Rate Limited')
        ->set('es_name', 'Limitado')
        ->set('iso_alpha2', 'RL')
        ->set('iso_alpha3', 'RLM')
        ->set('phone_code', '+99')
        ->set('sort_order', 1)
        ->call('save')
        ->assertDispatched('open-info-modal')
        ->assertNotDispatched('country-created');

    expect(Country::query()->where('iso_alpha2', 'RL')->exists())->toBeFalse();
});

// --- Validation boundary tests ---

test('create form rejects negative sort_order', function () {
    Livewire::test('countries.create-country-form')
        ->set('en_name', 'Negative Order')
        ->set('es_name', 'Orden Negativo')
        ->set('iso_alpha2', 'NO')
        ->set('iso_alpha3', 'NEG')
        ->set('phone_code', '+77')
        ->set('sort_order', -1)
        ->call('save')
        ->assertHasErrors(['sort_order'])
        ->assertNotDispatched('country-created');
});

// --- Create form resetValidation test ---

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('countries.create-country-form')
        ->set('en_name', '')
        ->set('es_name', '')
        ->set('iso_alpha2', '')
        ->set('iso_alpha3', '')
        ->set('phone_code', '')
        ->call('save')
        ->assertHasErrors(['en_name', 'iso_alpha2'])
        ->set('en_name', 'Fixed')
        ->assertHasNoErrors(['en_name'])
        ->set('iso_alpha2', 'FX')
        ->assertHasNoErrors(['iso_alpha2']);
});

// --- Abort path tests ---

test('index deleteCountry aborts 404 when no pending deletion exists', function () {
    countriesIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index toggleCountryActiveStatus throws on non-existent ID', function () {
    countriesIndexComponent()
        ->call('toggleCountryActiveStatus', 999999, 'is_active', true);
})->throws(ModelNotFoundException::class);
