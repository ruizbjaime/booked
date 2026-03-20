<?php

use App\Infrastructure\UiFeedback\ModalService;
use App\Models\IdentificationDocumentType;
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

function docTypesIndexComponent(?bool $mobileViewport = false): Testable
{
    $component = Livewire::test('pages::identification-document-types.index');

    if ($mobileViewport !== null) {
        $component->call('syncTableViewport', $mobileViewport);
    }

    return $component;
}

test('admins can visit the document types index page', function () {
    $this->get(route('identification-document-types.index'))
        ->assertOk()
        ->assertSeeText(__('identification_document_types.index.title'));
});

test('admins can visit the document types show page', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'code' => 'DNI',
        'en_name' => 'National ID',
        'es_name' => 'Documento Nacional',
        'is_active' => true,
    ]);

    $this->get(route('identification-document-types.show', $docType))
        ->assertOk()
        ->assertSeeText(__('identification_document_types.show.placeholder_title'))
        ->assertSeeText('National ID')
        ->assertSeeText('DNI');
});

test('non admins cannot visit the document types index page', function () {
    $this->actingAs(makeGuest());

    $this->get(route('identification-document-types.index'))->assertForbidden();
});

test('non admins cannot visit the document types show page', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $this->actingAs(makeGuest());

    $this->get(route('identification-document-types.show', $docType))->assertForbidden();
});

test('sidebar hides the document types navigation item for non admins', function () {
    $this->actingAs(makeGuest());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText(__('identification_document_types.navigation.label'));
});

test('sidebar shows the document types navigation item for admins', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText(__('Parameterization'))
        ->assertSeeText(__('identification_document_types.navigation.label'));
});

test('document types index sorts by sort_order asc by default', function () {
    IdentificationDocumentType::factory()->create([
        'en_name' => 'Zulu Type',
        'es_name' => 'Zulu Tipo',
        'sort_order' => 200,
    ]);

    IdentificationDocumentType::factory()->create([
        'en_name' => 'Alpha Type',
        'es_name' => 'Alpha Tipo',
        'sort_order' => 100,
    ]);

    docTypesIndexComponent()
        ->assertSeeInOrder(['Alpha', 'Zulu'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'asc');
});

test('document types index can sort by localized name', function () {
    $nameColumn = IdentificationDocumentType::localizedNameColumn();

    IdentificationDocumentType::factory()->create([$nameColumn => 'Zulu Type']);
    IdentificationDocumentType::factory()->create([$nameColumn => 'Alpha Type']);

    docTypesIndexComponent()
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Alpha Type', 'Zulu Type'])
        ->assertSet('sortBy', $nameColumn)
        ->assertSet('sortDirection', 'asc')
        ->call('sort', $nameColumn)
        ->assertSeeInOrder(['Zulu Type', 'Alpha Type'])
        ->assertSet('sortDirection', 'desc');
});

test('document types index can sort by created_at', function () {
    $nameColumn = IdentificationDocumentType::localizedNameColumn();

    IdentificationDocumentType::factory()->create([
        $nameColumn => 'Older Type',
        'created_at' => Carbon::parse('2026-03-10 09:00:00'),
    ]);

    IdentificationDocumentType::factory()->create([
        $nameColumn => 'Newest Type',
        'created_at' => Carbon::parse('2026-03-15 09:00:00'),
    ]);

    docTypesIndexComponent()
        ->call('sort', 'created_at')
        ->assertSeeInOrder(['Newest Type', 'Older Type'])
        ->assertSet('sortBy', 'created_at')
        ->assertSet('sortDirection', 'desc');
});

test('document types index search filters by name', function () {
    IdentificationDocumentType::factory()->create([
        'en_name' => 'Passport',
        'es_name' => 'Passport',
    ]);
    IdentificationDocumentType::factory()->create([
        'en_name' => 'Driver License',
        'es_name' => 'Driver License',
    ]);

    docTypesIndexComponent()
        ->set('search', 'Passport')
        ->assertSee('Passport')
        ->assertDontSee('Driver License');
});

test('document types index search filters by code', function () {
    $nameColumn = IdentificationDocumentType::localizedNameColumn();

    IdentificationDocumentType::factory()->create([
        $nameColumn => 'Passport',
        'code' => 'PAS',
    ]);
    IdentificationDocumentType::factory()->create([
        $nameColumn => 'Driver License',
        'code' => 'DLI',
    ]);

    docTypesIndexComponent()
        ->set('search', 'PAS')
        ->assertSee('Passport')
        ->assertDontSee('Driver License');
});

test('admin can open the document type create modal from the index', function () {
    $component = docTypesIndexComponent()
        ->call('openCreateDocTypeModal');

    expect(collect(data_get($component->effects, 'dispatches', []))->contains(function (array $dispatch): bool {
        return ($dispatch['name'] ?? null) === 'open-form-modal'
            && ($dispatch['params']['name'] ?? null) === 'identification-document-types.create'
            && ($dispatch['params']['title'] ?? null) === __('identification_document_types.create.title')
            && ($dispatch['params']['description'] ?? null) === __('identification_document_types.create.description');
    }))->toBeTrue();
});

test('modal service resolves the document type create form component', function () {
    Livewire::test('modal-service')
        ->dispatch('open-form-modal',
            name: 'identification-document-types.create',
            title: __('identification_document_types.create.title'),
            description: __('identification_document_types.create.description'),
        )
        ->assertSet('formModalName', 'identification-document-types.create')
        ->assertSee(__('identification_document_types.create.fields.code'));
});

test('admin can create a document type from the create modal', function () {
    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->assertSet('is_active', true)
        ->assertSet('sort_order', 999)
        ->set('code', 'DNI')
        ->set('en_name', 'National ID')
        ->set('es_name', 'Documento Nacional')
        ->set('sort_order', 100)
        ->call('save')
        ->assertSet('code', '')
        ->assertSet('en_name', '')
        ->assertSet('es_name', '')
        ->assertSet('sort_order', 999)
        ->assertSet('is_active', true)
        ->assertDispatched('close-form-modal')
        ->assertDispatched('doc-type-created');

    $created = IdentificationDocumentType::query()->where('code', 'DNI')->first();

    expect($created)->not->toBeNull()
        ->and($created?->en_name)->toBe('National ID')
        ->and($created?->es_name)->toBe('Documento Nacional')
        ->and($created?->sort_order)->toBe(100)
        ->and($created?->is_active)->toBeTrue();
});

test('create form validates duplicate code', function () {
    IdentificationDocumentType::factory()->create(['code' => 'DNI']);

    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', 'DNI')
        ->set('en_name', 'Duplicate')
        ->set('es_name', 'Duplicado')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['code'])
        ->assertNotDispatched('doc-type-created');
});

test('admin can toggle document type active status', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Toggle Me',
        'is_active' => false,
    ]);

    docTypesIndexComponent()
        ->call('toggleDocTypeActiveStatus', $docType->id, true)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($docType->fresh()->is_active)->toBeTrue();
});

test('admin can deactivate a document type', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Deactivate Me',
        'is_active' => true,
    ]);

    docTypesIndexComponent()
        ->call('toggleDocTypeActiveStatus', $docType->id, false)
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    expect($docType->fresh()->is_active)->toBeFalse();
});

function docTypeDeleteModalMessage(IdentificationDocumentType $docType): string
{
    return __('identification_document_types.index.confirm_delete.message', [
        'doc_type' => __('identification_document_types.doc_type_label', ['name' => $docType->localizedName(), 'id' => $docType->id]),
    ]);
}

test('admin can delete a document type from the index', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Delete Me',
    ]);

    docTypesIndexComponent()
        ->call('confirmDocTypeDeletion', $docType->id)
        ->assertSet('docTypeIdPendingDeletion', $docType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) use ($docType) {
            return $event === 'open-confirm-modal'
                && ($params['title'] ?? null) === __('identification_document_types.index.confirm_delete.title')
                && ($params['message'] ?? null) === docTypeDeleteModalMessage($docType)
                && ($params['confirmLabel'] ?? null) === __('identification_document_types.index.confirm_delete.confirm_label')
                && ($params['variant'] ?? null) === ModalService::VARIANT_PASSWORD;
        })
        ->dispatch('modal-confirmed')
        ->assertSet('docTypeIdPendingDeletion', null)
        ->assertDontSee('Delete Me');

    expect(IdentificationDocumentType::query()->find($docType->id))->toBeNull();
});

test('document type with associated users is deactivated instead of deleted', function () {
    $docType = IdentificationDocumentType::factory()->create([
        'en_name' => 'Has Users',
        'is_active' => true,
    ]);

    User::factory()->create(['document_type_id' => $docType->id]);

    docTypesIndexComponent()
        ->call('confirmDocTypeDeletion', $docType->id)
        ->assertDispatched('open-confirm-modal', function (string $event, array $params) {
            return ($params['title'] ?? null) === __('identification_document_types.index.confirm_deactivate.title');
        })
        ->dispatch('modal-confirmed')
        ->assertDispatched('toast-show', function (string $event, array $params) {
            return $event === 'toast-show'
                && ($params['dataset']['variant'] ?? null) === 'success';
        });

    $fresh = IdentificationDocumentType::query()->find($docType->id);

    expect($fresh)->not->toBeNull()
        ->and($fresh->is_active)->toBeFalse();
});

test('document types index clears a pending deletion when the confirm modal is cancelled', function () {
    $docType = IdentificationDocumentType::factory()->create();

    docTypesIndexComponent()
        ->call('confirmDocTypeDeletion', $docType->id)
        ->assertSet('docTypeIdPendingDeletion', $docType->id)
        ->dispatch('modal-confirm-cancelled')
        ->assertSet('docTypeIdPendingDeletion', null);
});

test('document types pages render Spanish translations', function () {
    $originalLocale = app()->getLocale();

    app()->setLocale('es');

    try {
        $this->actingAs(makeAdmin());

        $this->get(route('identification-document-types.index'))
            ->assertOk()
            ->assertSeeText(__('identification_document_types.navigation.label'))
            ->assertSeeText(__('identification_document_types.index.description'))
            ->assertSeeText(__('identification_document_types.index.create_action'));

        $docType = IdentificationDocumentType::factory()->create();

        $this->get(route('identification-document-types.show', $docType))
            ->assertOk()
            ->assertSeeText(__('identification_document_types.show.placeholder_title'));
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('document types pages render successfully as livewire components', function () {
    docTypesIndexComponent()
        ->assertOk()
        ->assertSee(__('identification_document_types.index.title'));

    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->assertOk()
        ->assertSee(__('identification_document_types.create.fields.code'))
        ->assertSee(__('identification_document_types.create.fields.en_name'))
        ->assertSee(__('identification_document_types.create.fields.es_name'))
        ->assertSee(__('identification_document_types.create.fields.sort_order'))
        ->assertSee(__('identification_document_types.create.submit'));

    $showDocType = IdentificationDocumentType::factory()->create();

    Livewire::test('pages::identification-document-types.show', ['identificationDocumentType' => (string) $showDocType->id])
        ->assertOk()
        ->assertSee(__('identification_document_types.show.placeholder_title'))
        ->assertSee($showDocType->en_name);
});

test('non admins cannot trigger document type deletion from the index', function () {
    $this->actingAs(makeGuest());

    Livewire::test('pages::identification-document-types.index')
        ->assertForbidden();
});

test('document types index search input defines non-auth autofill metadata', function () {
    docTypesIndexComponent()
        ->assertSeeHtml('name="doc_types_search"')
        ->assertSeeHtml('id="doc-types-search"')
        ->assertSeeHtml('autocomplete="off"');
});

test('admin can create an inactive document type from the create modal', function () {
    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', 'INC')
        ->set('en_name', 'Inactive Type')
        ->set('es_name', 'Tipo Inactivo')
        ->set('sort_order', 50)
        ->set('is_active', false)
        ->call('save')
        ->assertDispatched('close-form-modal')
        ->assertDispatched('doc-type-created');

    $created = IdentificationDocumentType::query()->where('code', 'INC')->first();

    expect($created)->not->toBeNull()
        ->and($created?->is_active)->toBeFalse();
});

test('create form validates required fields', function () {
    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', '')
        ->set('en_name', '')
        ->set('es_name', '')
        ->call('save')
        ->assertHasErrors(['code', 'en_name', 'es_name'])
        ->assertNotDispatched('doc-type-created');
});

test('create form normalizes code to uppercase', function () {
    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', 'abc')
        ->set('en_name', 'Lowercase Test')
        ->set('es_name', 'Test Minúsculas')
        ->set('sort_order', 1)
        ->call('save')
        ->assertDispatched('doc-type-created');

    $created = IdentificationDocumentType::query()->where('en_name', 'Lowercase Test')->first();

    expect($created)->not->toBeNull()
        ->and($created?->code)->toBe('ABC');
});

test('document types index can sort by sort_order column', function () {
    $nameColumn = IdentificationDocumentType::localizedNameColumn();

    IdentificationDocumentType::factory()->create([$nameColumn => 'High Order', 'sort_order' => 500]);
    IdentificationDocumentType::factory()->create([$nameColumn => 'Low Order', 'sort_order' => 10]);

    docTypesIndexComponent()
        ->assertSeeInOrder(['Low Order', 'High Order'])
        ->call('sort', 'sort_order')
        ->assertSeeInOrder(['High Order', 'Low Order'])
        ->assertSet('sortBy', 'sort_order')
        ->assertSet('sortDirection', 'desc');
});

test('document types index resets page when a document type is created', function () {
    IdentificationDocumentType::factory()->count(15)->create();

    docTypesIndexComponent()
        ->set('perPage', 10)
        ->call('gotoPage', 2)
        ->assertSet('paginators.page', 2)
        ->dispatch('doc-type-created')
        ->assertSet('paginators.page', 1);
});

// --- Rate limiting tests ---

test('index toggle active status is rate limited', function () {
    $docType = IdentificationDocumentType::factory()->create(['is_active' => false]);

    $component = docTypesIndexComponent();

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit("doc-type-mgmt:toggle-active:{$this->app['auth']->id()}", 60);
    }

    $component->call('toggleDocTypeActiveStatus', $docType->id, true)
        ->assertStatus(429);
});

test('index delete confirmation is rate limited', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $component = docTypesIndexComponent();

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("doc-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->call('confirmDocTypeDeletion', $docType->id)
        ->assertStatus(429);
});

test('index modal-confirmed delete is rate limited', function () {
    $docType = IdentificationDocumentType::factory()->create();

    $component = docTypesIndexComponent()
        ->call('confirmDocTypeDeletion', $docType->id);

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("doc-type-mgmt:delete:{$this->app['auth']->id()}", 60);
    }

    $component->dispatch('modal-confirmed')
        ->assertStatus(429);

    expect(IdentificationDocumentType::query()->find($docType->id))->not->toBeNull();
});

test('create form save is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit("doc-type-mgmt:create:{$this->app['auth']->id()}", 60);
    }

    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', 'RTL')
        ->set('en_name', 'Rate Limited')
        ->set('es_name', 'Limitado')
        ->set('sort_order', 1)
        ->call('save')
        ->assertStatus(429);

    expect(IdentificationDocumentType::query()->where('code', 'RTL')->exists())->toBeFalse();
});

// --- Validation boundary tests ---

test('create form rejects code exceeding max length', function () {
    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', str_repeat('A', 21))
        ->set('en_name', 'Too Long Code')
        ->set('es_name', 'Código Largo')
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasErrors(['code'])
        ->assertNotDispatched('doc-type-created');
});

test('create form rejects negative sort_order', function () {
    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', 'NEG')
        ->set('en_name', 'Negative Order')
        ->set('es_name', 'Orden Negativo')
        ->set('sort_order', -1)
        ->call('save')
        ->assertHasErrors(['sort_order'])
        ->assertNotDispatched('doc-type-created');
});

// --- Create form resetValidation test ---

test('create form clears field validation error when user corrects the field', function () {
    Livewire::test('identification-document-types.create-identification-document-type-form')
        ->set('code', '')
        ->set('en_name', '')
        ->set('es_name', 'Algo')
        ->call('save')
        ->assertHasErrors(['code', 'en_name'])
        ->set('code', 'FIX')
        ->assertHasNoErrors(['code'])
        ->set('en_name', 'Fixed')
        ->assertHasNoErrors(['en_name']);
});

// --- Index abort path tests ---

test('index deleteDocType aborts 404 when no pending deletion exists', function () {
    docTypesIndexComponent()
        ->dispatch('modal-confirmed')
        ->assertNotFound();
});

test('index toggleDocTypeActiveStatus throws on non-existent ID', function () {
    docTypesIndexComponent()
        ->call('toggleDocTypeActiveStatus', 999999, true);
})->throws(ModelNotFoundException::class);
