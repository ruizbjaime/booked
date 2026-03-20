<?php

use App\Actions\Platforms\UpdatePlatform;
use App\Models\Platform;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

test('renders show page with platform details', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Booking.com',
        'es_name' => 'Booking.com',
        'color' => 'blue',
        'sort_order' => 10,
        'commission' => 0.15,
        'commission_tax' => 0.03,
        'is_active' => true,
    ]);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertOk()
        ->assertSee('Booking.com')
        ->assertSee(__('platforms.show.status.active'));
});

test('autosaves field changes', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Old Name',
    ]);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'New Name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($platform->fresh()->en_name)->toBe('New Name');
});

test('validates unique names on autosave', function () {
    Platform::factory()->create(['en_name' => 'Booking.com']);

    $platform = Platform::factory()->create(['en_name' => 'Airbnb']);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'Booking.com')
        ->assertHasErrors(['en_name']);

    expect($platform->fresh()->en_name)->toBe('Airbnb');
});

test('active toggle autosaves', function () {
    $platform = Platform::factory()->create(['is_active' => true]);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details')
        ->set('is_active', false)
        ->assertDispatched('toast-show');

    expect($platform->fresh()->is_active)->toBeFalse();
});

test('delete confirmation and redirect', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('confirmPlatformDeletion')
        ->assertSet('platformIdPendingDeletion', $platform->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('platforms.index'));

    expect(Platform::query()->find($platform->id))->toBeNull();
});

test('non-admin cannot view show page', function () {
    $platform = Platform::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertForbidden();
});

test('cancel editing section restores original values and clears validation', function () {
    Platform::factory()->create(['en_name' => 'Booking.com']);

    $platform = Platform::factory()->create([
        'en_name' => 'Airbnb',
    ]);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'Booking.com')
        ->assertHasErrors(['en_name'])
        ->call('cancelEditingSection')
        ->assertSet('en_name', 'Airbnb')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $platform = Platform::factory()->create([
        'en_name' => 'Unchanged',
    ]);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertSet('editingSection', null)
        ->set('en_name', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($platform->fresh()->en_name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $platform = Platform::factory()->create();

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

// --- Rate limiting tests ---

test('show page autosave is rate limited', function () {
    $platform = Platform::factory()->create();

    $component = Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("platform-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('en_name', 'Rate Limited Name')
        ->assertStatus(429);
});

test('show page active toggle is rate limited', function () {
    $platform = Platform::factory()->create(['is_active' => true]);

    $component = Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("platform-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->set('is_active', false)
        ->assertStatus(429);
});

test('show page delete confirmation is rate limited', function () {
    $platform = Platform::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("platform-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('confirmPlatformDeletion')
        ->assertStatus(429);
});

test('show page modal-confirmed is rate limited', function () {
    $platform = Platform::factory()->create();

    $component = Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('confirmPlatformDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("platform-mgmt:confirmed-action:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertStatus(429);

    expect(Platform::query()->find($platform->id))->not->toBeNull();
});

// --- canEdit / canDelete ---

test('show page canEdit returns true for admin', function () {
    $platform = Platform::factory()->create();

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertSeeHtml('wire:click="startEditingSection');
});

test('show page canDelete returns true for admin', function () {
    $platform = Platform::factory()->create();

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertSeeHtml('wire:click="confirmPlatformDeletion');
});

// --- Modal cancel on show page ---

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $platform = Platform::factory()->create();

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('confirmPlatformDeletion')
        ->assertSet('platformIdPendingDeletion', $platform->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('platformIdPendingDeletion', null);
});

// --- Mount 404 ---

test('show page mount returns 404 for non-existent platform', function () {
    $this->get('/platforms/999999')
        ->assertNotFound();
});

// --- Update action aborts 422 for unknown field ---

test('update action aborts 422 for unknown field name', function () {
    $admin = makeAdmin();
    $platform = Platform::factory()->create();

    $action = app(UpdatePlatform::class);

    try {
        $action->handle($admin, $platform, 'nonexistent', 'value');
        $this->fail('Expected abort 422');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});

// --- Color mode & form state tests ---

test('autosaves color when changing color mode to a preset', function () {
    $platform = Platform::factory()->create(['color' => 'blue']);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details')
        ->set('colorMode', 'green')
        ->assertDispatched('toast-show');

    expect($platform->fresh()->color)->toBe('green');
});

test('autosaves color when switching to custom mode and entering hex', function () {
    $platform = Platform::factory()->create(['color' => 'blue']);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->call('startEditingSection', 'details')
        ->set('colorMode', 'custom')
        ->set('customColor', '#FF5733')
        ->assertDispatched('toast-show');

    expect($platform->fresh()->color)->toBe('#FF5733');
});

test('color mode change does not autosave without active editing section', function () {
    $platform = Platform::factory()->create(['color' => 'blue']);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->set('colorMode', 'green')
        ->assertNotDispatched('toast-show');

    expect($platform->fresh()->color)->toBe('blue');
});

test('displays commission as percentage with correct precision', function () {
    $platform = Platform::factory()->create([
        'commission' => 0.155,
        'commission_tax' => 0.0325,
    ]);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertSet('commission', 15.5)
        ->assertSet('commission_tax', 3.25);
});

test('detects custom color mode for hex colors on mount', function () {
    $platform = Platform::factory()->create(['color' => '#FF5733']);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertSet('colorMode', 'custom')
        ->assertSet('customColor', '#FF5733')
        ->assertSet('color', '#FF5733');
});

test('detects preset color mode for named colors on mount', function () {
    $platform = Platform::factory()->create(['color' => 'indigo']);

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->assertSet('colorMode', 'indigo')
        ->assertSet('customColor', '')
        ->assertSet('color', 'indigo');
});

test('modal-confirmed does nothing when no pending deletion', function () {
    $platform = Platform::factory()->create();

    Livewire::test('pages::platforms.show', ['platform' => (string) $platform->id])
        ->dispatch('modal-confirmed')
        ->assertNoRedirect();

    expect(Platform::query()->find($platform->id))->not->toBeNull();
});
