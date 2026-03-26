<?php

use App\Infrastructure\UiFeedback\ModalService;
use App\Models\Platform;
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

function platformsIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::platforms.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the platforms index page', function () {
    $this->get(route('platforms.index'))
        ->assertOk()
        ->assertSeeText(__('platforms.index.title'));
});

test('admins can visit the platforms show page', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Booking.com',
        'es_name' => 'Booking.com',
        'color' => 'blue',
        'commission' => 0.15,
        'commission_tax' => 0.03,
        'is_active' => true,
    ]);

    $this->get(route('platforms.show', $platform))
        ->assertOk()
        ->assertSeeText(__('platforms.show.placeholder_title'))
        ->assertSeeText('Booking.com');
});

test('non admins cannot visit the platforms index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('platforms.index'))->assertForbidden();
});

test('non admins cannot visit the platforms show page', function () {
    $platform = Platform::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('platforms.show', $platform))->assertForbidden();
});

test('sidebar hides the platforms navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('platforms.navigation.label'));
});

test('sidebar shows the platforms navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('platforms.navigation.label'));
});

test('platforms index sorts by sort_order asc by default', function () {
    Platform::factory()->create([
        'en_name' => 'Zulu Platform',
        'es_name' => 'Zulu Plataforma',
        'sort_order' => 200,
    ]);

    Platform::factory()->create([
        'en_name' => 'Alpha Platform',
        'es_name' => 'Alpha Plataforma',
        'sort_order' => 100,
    ]);

    platformsIndexComponent()
        ->assertSeeInOrder(['Alpha', 'Zulu'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'asc');
});

test('platforms index can sort by localized name', function () {
    $nameColumn = Platform::localizedNameColumn();

    Platform::factory()->create([$nameColumn => 'Zulu Platform']);
    Platform::factory()->create([$nameColumn => 'Alpha Platform']);

    platformsIndexComponent()
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Alpha Platform', 'Zulu Platform'])
        ->assertSet('sortBy', $nameColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Zulu Platform', 'Alpha Platform'])
        ->assertSet('sortDirection', 'desc');
});

test('platforms index can sort by created_at', function () {
    $nameColumn = Platform::localizedNameColumn();

    Platform::factory()->create([
        $nameColumn => 'Older Platform',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    Platform::factory()->create([
        $nameColumn => 'Newest Platform',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    platformsIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest Platform', 'Older Platform'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('platforms index search filters by name', function () {
    Platform::factory()->create([
        'en_name' => 'Booking.com',
        'es_name' => 'Booking.com',
    ]);
    Platform::factory()->create([
        'en_name' => 'Airbnb',
        'es_name' => 'Airbnb',
    ]);

    platformsIndexComponent()
        ->set('search', 'Booking')
        ->assertSee('Booking.com')
        ->assertDontSee('Airbnb');
});

test('admin can open the platform create modal from the platforms index', function () {
    $component = platformsIndexComponent()
        ->call('openCreatePlatformModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'platforms.create'
            && ($dispatch['params']['title'] ?? null) === __('platforms.create.title')
            && ($dispatch['params']['description'] ?? null) === __('platforms.create.description');
    }))->toBeTrue();
});

test('admin can create a platform from the create modal', function () {
    Livewire::test('platforms.create-platform-form')
        ->assertSet('is_active', true)
        ->assertSet('sort_order', 999)
        ->set('name', 'test-platform')
        ->set('en_name', 'Test Platform')
        ->set('es_name', 'Plataforma de Prueba')
        ->set('sort_order', 100)
        ->set('commission', '10.00')
        ->set('commission_tax', '2.50')
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('en_name', '')
        ->assertSet('es_name', '')
        ->assertSet('sort_order', 999)
        ->assertSet('is_active', true)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('platform-created');

    $created = Platform::query()->where('name', 'test-platform')->first();

    expect($created)->not->toBeNull()
        ->and($created?->name)->toBe('test-platform')
        ->and($created?->en_name)->toBe('Test Platform')
        ->and($created?->es_name)->toBe('Plataforma de Prueba')
        ->and($created?->color)->toBe('zinc')
        ->and($created?->sort_order)->toBe(100)
        ->and($created?->commission)->toBe('0.1000')
        ->and($created?->commission_tax)->toBe('0.0250')
        ->and($created?->is_active)->toBeTrue();
});

test('create form validates duplicate en_name', function () {
    Platform::factory()->create(['en_name' => 'Booking.com']);

    Livewire::test('platforms.create-platform-form')
        ->set('name', 'dup-en')
        ->set('en_name', 'Booking.com')
        ->set('es_name', 'Nuevo')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['en_name'])
        ->assertNotDispatched('platform-created');
});

test('create form validates duplicate es_name', function () {
    Platform::factory()->create(['es_name' => 'Booking.com']);

    Livewire::test('platforms.create-platform-form')
        ->set('name', 'dup-es')
        ->set('en_name', 'New Platform')
        ->set('es_name', 'Booking.com')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['es_name'])
        ->assertNotDispatched('platform-created');
});

test('admin can toggle platform active status', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Toggle Me',
        'is_active' => false,
    ]);

    platformsIndexComponent()
        ->call('togglePlatformActiveStatus', $platform->id, 'is_active', true)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($platform->fresh()->is_active)->toBeTrue();
});

test('admin can deactivate a platform', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Deactivate Me',
        'is_active' => true,
    ]);

    platformsIndexComponent()
        ->call('togglePlatformActiveStatus', $platform->id, 'is_active', false)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($platform->fresh()->is_active)->toBeFalse();
});

test('admin can delete a platform from the platforms index', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    platformsIndexComponent()
        ->call('confirmPlatformDeletion', $platform->id)
        ->assertSet('platformIdPendingDeletion', $platform->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('platforms.index.confirm_delete.title')
                && ($params['variant'] ?? null) === ModalService::VARIANT_PASSWORD;
        })
        ->dispatch('modal-confirmed')
        ->assertSet('platformIdPendingDeletion', null)
        ->assertDontSee('Delete Me');

    expect(Platform::query()->find($platform->id))->toBeNull();
});

test('platforms index clears a pending deletion when the confirm modal is cancelled', function () {
    $platform = Platform::factory()->create();

    platformsIndexComponent()
        ->call('confirmPlatformDeletion', $platform->id)
        ->assertSet('platformIdPendingDeletion', $platform->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('platformIdPendingDeletion', null);
});

test('platforms pages render Spanish translations', function () {
    $originalLocale = app()->getLocale();

    app()->setLocale('es');

    try {
        $this->actingAs(makeAdmin());

        $this->get(route('platforms.index'))
            ->assertOk()
            ->assertSeeText(__('platforms.navigation.label'))
            ->assertSeeText(__('platforms.index.description'))
            ->assertSeeText(__('platforms.index.create_action'));

        $platform = Platform::factory()->create();

        $this->get(route('platforms.show', $platform))
            ->assertOk()
            ->assertSeeText(__('platforms.show.placeholder_title'));
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('platforms pages render successfully as livewire components', function () {
    platformsIndexComponent()
        ->assertOk()
        ->assertSee(__('platforms.index.title'));

    Livewire::test('platforms.create-platform-form')
        ->assertOk()
        ->assertSee(__('platforms.create.fields.name'))
        ->assertSee(__('platforms.create.fields.en_name'))
        ->assertSee(__('platforms.create.fields.es_name'))
        ->assertSee(__('platforms.create.fields.color'))
        ->assertSee(__('platforms.create.fields.commission'))
        ->assertSee(__('platforms.create.fields.commission_tax'))
        ->assertSee(__('platforms.create.submit'));

    $showPlatform = Platform::factory()->create();

    Livewire::test('pages::platforms.show', ['platform' => (string) $showPlatform->id])
        ->assertOk()
        ->assertSee(__('platforms.show.placeholder_title'))
        ->assertSee($showPlatform->en_name);
});

test('non admins cannot trigger platform deletion from the platforms index', function () {
    $this->actingAs(makeGuest());

    Livewire::test('pages::platforms.index')
        ->assertForbidden();
});

test('platforms index search input defines non-auth autofill metadata', function () {
    platformsIndexComponent()
        ->assertSeeHtml('name="platforms_search"')
        ->assertSeeHtml('id="platforms-search"')
        ->assertSeeHtml('autocomplete="off"');
});

test('admin can create an inactive platform from the create modal', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'inactive-platform')
        ->set('en_name', 'Inactive Platform')
        ->set('es_name', 'Plataforma Inactiva')
        ->set('sort_order', 50)
        ->set('commission', '5.00')
        ->set('commission_tax', '1.00')
        ->set('is_active', false)
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('platform-created');

    $created = Platform::query()->where('en_name', 'Inactive Platform')->first();

    expect($created)->not->toBeNull()
        ->and($created?->is_active)->toBeFalse();
});

test('create form validates required fields', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', '')
        ->set('en_name', '')
        ->set('es_name', '')
        ->call('save')
        ->assertHasErrors(['name', 'en_name', 'es_name'])
        ->assertNotDispatched('platform-created');
});

test('create form with custom color saves hex value', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'custom-color')
        ->set('en_name', 'Custom Color Platform')
        ->set('es_name', 'Plataforma Color Custom')
        ->set('colorMode', 'custom')
        ->set('customColor', '#FF5733')
        ->set('sort_order', 100)
        ->set('commission', '10.00')
        ->set('commission_tax', '2.00')
        ->call('save')
        ->assertDispatched('platform-created');

    $created = Platform::query()->where('en_name', 'Custom Color Platform')->first();

    expect($created)->not->toBeNull()
        ->and($created?->color)->toBe('#FF5733');
});

test('platforms index can sort by sort_order column', function () {
    $nameColumn = Platform::localizedNameColumn();

    Platform::factory()->create([$nameColumn => 'High Order', 'sort_order' => 500]);
    Platform::factory()->create([$nameColumn => 'Low Order', 'sort_order' => 10]);

    platformsIndexComponent()
        ->assertSeeInOrder(['Low Order', 'High Order'])
        ->call('sort', 'sort_order')
        ->assertSeeInOrder(['High Order', 'Low Order'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'desc');
});

test('platforms index resets page when a platform is created', function () {
    Platform::factory()->count(15)->create();

    platformsIndexComponent()
        ->set('perPage', 10)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->dispatch('platform-created')
        ->assertSet('paginators.page', 1);
});

// --- Rate limiting tests ---

test('index toggle active status is rate limited', function () {
    $platform = Platform::factory()->create(['is_active' => false]);

    $component = platformsIndexComponent();

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("platform-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->call('togglePlatformActiveStatus', $platform->id, 'is_active', true)
        ->assertDispatched('open-info-modal');
});

test('index delete confirmation is rate limited', function () {
    $platform = Platform::factory()->create();

    $component = platformsIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("platform-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->call('confirmPlatformDeletion', $platform->id)
        ->assertDispatched('open-info-modal');
});

test('index modal-confirmed delete is rate limited', function () {
    $platform = Platform::factory()->create();

    $component = platformsIndexComponent()
        ->call('confirmPlatformDeletion', $platform->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("platform-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(Platform::query()->find($platform->id))->not->toBeNull();
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("platform-mgmt:create:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('platforms.create-platform-form')
        ->set('name', 'rate-limited')
        ->set('en_name', 'Rate Limited')
        ->set('es_name', 'Limitado')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertDispatched('open-info-modal')
        ->assertNotDispatched('platform-created');

    expect(Platform::query()->where('name', 'rate-limited')->exists())->toBeFalse();
});

// --- Validation boundary tests ---

test('create form rejects negative sort_order', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'neg-order')
        ->set('en_name', 'Negative Order')
        ->set('es_name', 'Orden Negativo')
        ->set('sort_order', -1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['sort_order'])
        ->assertNotDispatched('platform-created');
});

// --- Create form resetValidation test ---

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', '')
        ->set('en_name', '')
        ->set('es_name', '')
        ->call('save')
        ->assertHasErrors(['name', 'en_name'])
        ->set('name', 'fixed')
        ->assertHasNoErrors(['name'])
        ->set('en_name', 'Fixed')
        ->assertHasNoErrors(['en_name']);
});

// --- Abort path tests ---

test('index deletePlatform aborts 404 when no pending deletion exists', function () {
    platformsIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index togglePlatformActiveStatus throws on non-existent ID', function () {
    platformsIndexComponent()
        ->call('togglePlatformActiveStatus', 999999, 'is_active', true);
})->throws(ModelNotFoundException::class);

// --- Name regex & validation boundary tests ---

test('create form rejects name starting with uppercase', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'Airbnb')
        ->set('en_name', 'Airbnb')
        ->set('es_name', 'Airbnb')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('platform-created');
});

test('create form rejects name with spaces', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'my platform')
        ->set('en_name', 'My Platform')
        ->set('es_name', 'Mi Plataforma')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('platform-created');
});

test('create form rejects name starting with number', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', '123test')
        ->set('en_name', '123 Test')
        ->set('es_name', '123 Prueba')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('platform-created');
});

test('create form rejects name with special characters', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'my@platform')
        ->set('en_name', 'My Platform')
        ->set('es_name', 'Mi Plataforma')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('platform-created');
});

test('create form accepts valid name patterns', function (string $name) {
    Livewire::test('platforms.create-platform-form')
        ->set('name', $name)
        ->set('en_name', "Platform {$name}")
        ->set('es_name', "Plataforma {$name}")
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasNoErrors(['name'])
        ->assertDispatched('platform-created');
})->with(['my-platform', 'my_platform', 'a123', 'x']);

test('create form rejects duplicate name', function () {
    Platform::factory()->create(['name' => 'airbnb']);

    Livewire::test('platforms.create-platform-form')
        ->set('name', 'airbnb')
        ->set('en_name', 'New Airbnb')
        ->set('es_name', 'Nuevo Airbnb')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('platform-created');
});

test('create form rejects commission greater than 100', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'over-commission')
        ->set('en_name', 'Over Commission')
        ->set('es_name', 'Sobre Comision')
        ->set('sort_order', 1)
        ->set('commission', '101')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['commission'])
        ->assertNotDispatched('platform-created');
});

test('create form rejects commission_tax greater than 100', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'over-tax')
        ->set('en_name', 'Over Tax')
        ->set('es_name', 'Sobre Impuesto')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '101')
        ->call('save')
        ->assertHasErrors(['commission_tax'])
        ->assertNotDispatched('platform-created');
});

test('create form rejects invalid hex color', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'bad-color')
        ->set('en_name', 'Bad Color')
        ->set('es_name', 'Color Malo')
        ->set('colorMode', 'custom')
        ->set('customColor', '#GGGGGG')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['color'])
        ->assertNotDispatched('platform-created');
});

test('create form accepts valid 3-digit hex color', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'hex-three')
        ->set('en_name', 'Hex Three')
        ->set('es_name', 'Hex Tres')
        ->set('colorMode', 'custom')
        ->set('customColor', '#F00')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasNoErrors(['color'])
        ->assertDispatched('platform-created');
});

test('index confirmPlatformDeletion throws on non-existent ID', function () {
    platformsIndexComponent()
        ->call('confirmPlatformDeletion', 999999);
})->throws(ModelNotFoundException::class);

test('create form accepts commission at exactly 100', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', 'max-commission')
        ->set('en_name', 'Max Commission')
        ->set('es_name', 'Comision Maxima')
        ->set('sort_order', 1)
        ->set('commission', '100')
        ->set('commission_tax', '100')
        ->call('save')
        ->assertHasNoErrors(['commission', 'commission_tax'])
        ->assertDispatched('platform-created');
});

test('create form rejects name exceeding 255 characters', function () {
    Livewire::test('platforms.create-platform-form')
        ->set('name', str_repeat('a', 256))
        ->set('en_name', 'Long Name')
        ->set('es_name', 'Nombre Largo')
        ->set('sort_order', 1)
        ->set('commission', '0')
        ->set('commission_tax', '0')
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertNotDispatched('platform-created');
});
