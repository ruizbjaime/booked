<?php

use App\Livewire\Settings\Profile;
use App\Models\Country;
use App\Models\IdentificationDocumentType;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\IdentificationDocumentTypeSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('profile page shows translated settings navigation labels', function () {
    $originalLocale = app()->getLocale();
    app()->setLocale('es');

    try {
        $this->actingAs(User::factory()->create())
            ->get('/settings/profile')
            ->assertOk()
            ->assertSee('Perfil')
            ->assertSee('Seguridad')
            ->assertSee('Apariencia');
    } finally {
        app()->setLocale($originalLocale);
    }
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('avatar can be uploaded', function () {
    Storage::fake('public');

    $setting = SystemSetting::instance();
    $setting->update(['avatar_size' => 120]);
    SystemSetting::clearCache();

    $user = User::factory()->create();
    $this->actingAs($user);

    $photo = UploadedFile::fake()->image('avatar.jpg', 600, 600);

    $component = Livewire::test(Profile::class)
        ->set('photo', $photo)
        ->assertHasNoErrors();

    $avatarPath = $user->refresh()->getFirstMediaPath('avatar');
    $avatarDimensions = getimagesize($avatarPath);

    expect($component->instance()->userAvatarUrl)->not->toBeNull()
        ->and($user->refresh()->getFirstMedia('avatar'))->not->toBeNull()
        ->and($avatarDimensions)->not->toBeFalse()
        ->and($avatarDimensions[0])->toBe(120)
        ->and($avatarDimensions[1])->toBe(120);
});

test('avatar can be deleted', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $photo = UploadedFile::fake()->image('avatar.jpg', 600, 600);

    $component = Livewire::test(Profile::class)
        ->set('photo', $photo)
        ->call('deleteAvatar')
        ->assertHasNoErrors();

    expect($component->instance()->userAvatarUrl)->toBeNull()
        ->and($user->refresh()->getFirstMedia('avatar'))->toBeNull();
});

test('avatar can be replaced', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $firstPhoto = UploadedFile::fake()->image('first.jpg', 400, 400);
    $secondPhoto = UploadedFile::fake()->image('second.jpg', 400, 400);

    Livewire::test(Profile::class)
        ->set('photo', $firstPhoto)
        ->assertHasNoErrors();

    Livewire::test(Profile::class)
        ->set('photo', $secondPhoto)
        ->assertHasNoErrors();

    expect($user->refresh()->getMedia('avatar'))->toHaveCount(1);
});

test('non-image files are rejected for avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    Livewire::test(Profile::class)
        ->set('photo', $file)
        ->assertHasErrors('photo');
});

test('personal information fields are rendered', function () {
    $this->seed([CountrySeeder::class, IdentificationDocumentTypeSeeder::class]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSee(__('settings.personal_information.heading'));
});

test('personal information can be saved', function () {
    $this->seed([CountrySeeder::class, IdentificationDocumentTypeSeeder::class]);

    $country = Country::query()->where('iso_alpha2', 'CO')->first();
    $docType = IdentificationDocumentType::query()->where('code', 'C.C.')->first();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('phone', '+573001234567')
        ->set('country_id', $country->id)
        ->set('document_type_id', $docType->id)
        ->set('document_number', '1234567890')
        ->set('state', 'Antioquia')
        ->set('city', 'Medellin')
        ->set('address', 'Calle 10 #30-45')
        ->call('updatePersonalInformation')
        ->assertHasNoErrors()
        ->assertDispatched('personal-info-updated');

    $user->refresh();

    expect($user)
        ->phone->toBe('+573001234567')
        ->country_id->toBe($country->id)
        ->document_type_id->toBe($docType->id)
        ->document_number->toBe('1234567890')
        ->state->toBe('Antioquia')
        ->city->toBe('Medellin')
        ->address->toBe('Calle 10 #30-45');
});

test('phone validation rejects invalid numbers', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('phone', 'not-a-phone')
        ->call('updatePersonalInformation')
        ->assertHasErrors('phone');
});

test('document type requires document number', function () {
    $this->seed(IdentificationDocumentTypeSeeder::class);

    $docType = IdentificationDocumentType::query()->first();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('document_type_id', $docType->id)
        ->set('document_number', '')
        ->call('updatePersonalInformation')
        ->assertHasErrors('document_number');
});

test('document number requires document type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('document_type_id', null)
        ->set('document_number', '1234567890')
        ->call('updatePersonalInformation')
        ->assertHasErrors('document_type_id');
});

test('both document fields empty passes validation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('document_type_id', null)
        ->set('document_number', '')
        ->call('updatePersonalInformation')
        ->assertHasNoErrors(['document_type_id', 'document_number']);
});

test('both document fields filled passes validation', function () {
    $this->seed(IdentificationDocumentTypeSeeder::class);

    $docType = IdentificationDocumentType::query()->first();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('document_type_id', $docType->id)
        ->set('document_number', '1234567890')
        ->call('updatePersonalInformation')
        ->assertHasNoErrors(['document_type_id', 'document_number']);
});

test('country dropdown is populated', function () {
    $this->seed(CountrySeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Profile::class);

    expect($component->instance()->countries)->not->toBeEmpty();
});

test('document type dropdown is populated', function () {
    $this->seed(IdentificationDocumentTypeSeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Profile::class);

    expect($component->instance()->documentTypes)->not->toBeEmpty();
});

test('phone auto-detects country on blur', function () {
    $this->seed(CountrySeeder::class);

    $country = Country::query()->where('iso_alpha2', 'CO')->first();
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('phone', '+573001234567')
        ->assertSet('country_id', $country->id);
});

test('country search filters by term', function (string $search, string $expectedIso) {
    $this->seed(CountrySeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Livewire::test(Profile::class)
        ->set('countrySearch', $search);

    expect($component->instance()->countries->pluck('iso_alpha2'))->toContain($expectedIso);
})->with([
    'spanish name' => ['Estados', 'US'],
    'english name' => ['United', 'US'],
    'phone code' => ['+57', 'CO'],
]);

test('country search returns all countries when cleared', function () {
    $this->seed(CountrySeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    $totalCountries = Country::query()->active()->count();

    $component = Livewire::test(Profile::class)
        ->set('countrySearch', 'Colombia')
        ->set('countrySearch', '');

    expect($component->instance()->countries)->toHaveCount($totalCountries);
});

test('country search escapes SQL wildcards', function () {
    $this->seed(CountrySeeder::class);

    $user = User::factory()->create();
    $this->actingAs($user);

    $totalCountries = Country::query()->active()->count();

    $component = Livewire::test(Profile::class)
        ->set('countrySearch', '%');

    expect($component->instance()->countries->count())->toBeLessThan($totalCountries);
});

test('profile detects unverified email users', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSet('hasUnverifiedEmail', true)
        ->assertSee(__('Your email address is unverified.'));
});

test('profile resend verification notification flashes status for unverified users', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->call('resendVerificationNotification')
        ->assertHasNoErrors()
        ->assertSee(__('A new verification link has been sent to your email address.'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('profile resend verification notification redirects verified users to dashboard', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->call('resendVerificationNotification')
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});
