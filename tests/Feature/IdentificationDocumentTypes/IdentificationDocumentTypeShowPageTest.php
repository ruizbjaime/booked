<?php

use App\Actions\IdentificationDocumentTypes\UpdateIdentificationDocumentType;
use App\Models\IdentificationDocumentType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(makeAdmin());
});

test('renders show page with document type details', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'code' => 'DNI',
        'en_name' => 'National ID',
        'es_name' => 'Documento Nacional',
        'sort_order' => 10,
        'is_active' => true,
    ]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->assertOk()
        ->assertSee('National ID')
        ->assertSee('DNI')
        ->assertSee(__('identification_document_types.show.status.active'));
});

test('autosaves field changes', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Old Name',
    ]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'details')
        ->set('en_name', 'New Name')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($docType->fresh()->en_name)->toBe('New Name');
});

test('validates unique code on autosave', function () {
    IdentificationDocumentType::factory()->create(['code' => 'DNI']);

    $docType = IdentificationDocumentType::factory()->create(['code' => 'PAS']);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'details')
        ->set('code', 'DNI')
        ->assertHasErrors(['code']);

    expect($docType->fresh()->code)->toBe('PAS');
});

test('active toggle autosaves', function () {
    $docType = IdentificationDocumentType::factory()->create(['is_active' => true]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'details')
        ->set('is_active', false)
        ->assertDispatched('toast-show');

    expect($docType->fresh()->is_active)->toBeFalse();
});

test('delete confirmation and redirect', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('confirmDocTypeDeletion')
        ->assertSet('docTypeIdPendingDeletion', $docType->id)
        ->dispatch('modal-confirmed')
        ->assertRedirect(route('identification-document-types.index'));

    expect(IdentificationDocumentType::query()->find($docType->id))->toBeNull();
});

test('document type with associated users is deactivated instead of deleted from show page', function () {
    $docType = IdentificationDocumentType::factory()->create(['is_active' => true]);

    User::factory()->create(['document_type_id' => $docType->id]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('confirmDocTypeDeletion')
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return ($params['title'] ?? null) === __('identification_document_types.show.quick_actions.deactivate.title');
        })
        ->dispatch('modal-confirmed')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        })
        ->assertNoRedirect();

    $fresh = IdentificationDocumentType::query()->find($docType->id);

    expect($fresh)->not->toBeNull()
        ->and($fresh->is_active)->toBeFalse();
});

test('non-admin cannot view show page', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $this->actingAs(makeGuest());

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->assertForbidden();
});

test('show page displays associated users count', function () {
    $docType = IdentificationDocumentType::factory()->create();

    User::factory()->count(3)->create(['document_type_id' => $docType->id]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->assertOk()
        ->assertSee('3');
});

test('cancel editing section restores original values and clears validation', function () {
    IdentificationDocumentType::factory()->create(['code' => 'DNI']);

    $docType = IdentificationDocumentType::factory()->create([
        'code' => 'PAS',
    ]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'details')
        ->set('code', 'DNI')
        ->assertHasErrors(['code'])
        ->call('cancelEditingSection')
        ->assertSet('code', 'PAS')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();
});

test('autosave does not trigger without active editing section', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Unchanged',
    ]);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->assertSet('editingSection', null)
        ->set('en_name', 'Should Not Save')
        ->assertNotDispatched('toast-show');

    expect($docType->fresh()->en_name)->toBe('Unchanged');
});

test('start editing section with invalid section returns 404', function () {
    $docType = IdentificationDocumentType::factory()->create();

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'nonexistent')
        ->assertNotFound();
});

// --- Autosave normalization ---

test('autosave normalizes code to uppercase on show page', function () {
    $docType = IdentificationDocumentType::factory()->create(['code' => 'OLD']);

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'details')
        ->set('code', 'abc')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($docType->fresh()->code)->toBe('ABC');
});

// --- Rate limiting tests ---

test('show page autosave is rate limited', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $component = Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("doc-type-mgmt:autosave:{$this->app['auth']->id()}", 60);
    }

    $component->set('en_name', 'Rate Limited Name')
        ->assertDispatched('open-info-modal');
});

test('show page active toggle is rate limited', function () {
    $docType = IdentificationDocumentType::factory()->create(['is_active' => true]);

    $component = Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('startEditingSection', 'details');

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("doc-type-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->set('is_active', false)
        ->assertDispatched('open-info-modal');
});

test('show page delete confirmation is rate limited', function () {
    $docType = IdentificationDocumentType::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("doc-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('confirmDocTypeDeletion')
        ->assertDispatched('open-info-modal');
});

test('show page modal-confirmed is rate limited', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $component = Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('confirmDocTypeDeletion');

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("doc-type-mgmt:confirmed-action:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertDispatched('open-info-modal');

    expect(IdentificationDocumentType::query()->find($docType->id))->not->toBeNull();
});

// --- canEdit / canDelete ---

test('show page canEdit returns true for admin', function () {
    $docType = IdentificationDocumentType::factory()->create();

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->assertSeeHtml('wire:click="startEditingSection');
});

test('show page canDelete returns true for admin', function () {
    $docType = IdentificationDocumentType::factory()->create();

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->assertSeeHtml('wire:click="confirmDocTypeDeletion');
});

// --- Modal cancel on show page ---

test('show page clears pending deletion when confirm modal is cancelled', function () {
    $docType = IdentificationDocumentType::factory()->create();

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $docType->id])
        ->call('confirmDocTypeDeletion')
        ->assertSet('docTypeIdPendingDeletion', $docType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('docTypeIdPendingDeletion', null);
});

// --- Mount 404 ---

test('show page mount returns 404 for non-existent document type', function () {
    $this->get('/identification-document-types/999999')
        ->assertNotFound();
});

// --- Update action aborts 422 for unknown field ---

test('update action aborts 422 for unknown field name', function () {
    $admin = makeAdmin();
    $docType = IdentificationDocumentType::factory()->create();

    $action = app(UpdateIdentificationDocumentType::class);

    try {
        $action->handle($admin, $docType, 'nonexistent', 'value');
        $this->fail('Expected abort 422');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422);
    }
});

// --- Model scope tests ---

test('scopeActive filters only active document types', function () {
    IdentificationDocumentType::factory()->create(['code' => 'ACT', 'is_active' => true]);
    IdentificationDocumentType::factory()->create(['code' => 'INA', 'is_active' => false]);

    $active = IdentificationDocumentType::query()->active()->pluck('code')->all();

    expect($active)->toContain('ACT')
        ->and($active)->not->toContain('INA');
});

test('scopeSearch filters by code, en_name and es_name', function () {
    IdentificationDocumentType::factory()->create(['code' => 'DNI', 'en_name' => 'National ID', 'es_name' => 'Documento Nacional']);
    IdentificationDocumentType::factory()->create(['code' => 'PAS', 'en_name' => 'Passport', 'es_name' => 'Pasaporte']);

    expect(IdentificationDocumentType::query()->search('DNI')->pluck('code')->all())->toBe(['DNI'])
        ->and(IdentificationDocumentType::query()->search('Passport')->pluck('code')->all())->toBe(['PAS'])
        ->and(IdentificationDocumentType::query()->search('Nacional')->pluck('code')->all())->toBe(['DNI']);
});

// --- Localized name tests ---

test('localizedName returns es_name when locale is es', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Passport',
        'es_name' => 'Pasaporte',
    ]);

    $originalLocale = app()->getLocale();

    try {
        app()->setLocale('es');
        expect($docType->localizedName())->toBe('Pasaporte');

        app()->setLocale('en');
        expect($docType->localizedName())->toBe('Passport');
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('localizedNameColumn returns es_name when locale is es', function () {
    $originalLocale = app()->getLocale();

    try {
        app()->setLocale('es');
        expect(IdentificationDocumentType::localizedNameColumn())->toBe('es_name');

        app()->setLocale('en');
        expect(IdentificationDocumentType::localizedNameColumn())->toBe('en_name');
    } finally {
        app()->setLocale($originalLocale);
    }
});
