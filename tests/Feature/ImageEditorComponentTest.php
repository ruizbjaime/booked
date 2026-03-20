<?php

use App\Domain\Users\RoleConfig;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->createOne();
    $this->admin->assignRole(RoleConfig::adminRole());

    $this->actingAs($this->admin);
});

describe('image-editor component', function () {

    it('renders the file input, modal markup, and preview container', function () {
        Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
            ->assertSeeHtml('id="avatar-upload"')
            ->assertSeeHtml('type="file"')
            ->assertSeeHtml('accept="image/jpeg,image/png,image/webp"')
            ->assertSeeHtml('data-modal="image-editor-avatar-upload"')
            ->assertSee(__('image-editor.title'))
            ->assertSee(__('image-editor.apply'))
            ->assertSeeHtml('id="image-editor-avatar-upload-container"')
            ->assertSeeHtml('aspect-ratio: 1/1');
    });

    it('renders toolbar controls', function () {
        Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
            ->assertSee(__('image-editor.rotate_left'))
            ->assertSee(__('image-editor.rotate_right'))
            ->assertSee(__('image-editor.flip_horizontal'))
            ->assertSee(__('image-editor.flip_vertical'))
            ->assertSee(__('image-editor.zoom_in'))
            ->assertSee(__('image-editor.zoom_out'))
            ->assertSee(__('image-editor.reset'));
    });

    it('passes the wire model target to alpine config', function () {
        Livewire::test('pages::users.show', ['user' => (string) $this->admin->id])
            ->assertSeeHtml("wireModel: 'photo'");
    });

    it('does not render the editor for users without edit permission', function () {
        /** @var User $viewer */
        $viewer = User::factory()->createOne();
        $viewer->assignRole(RoleConfig::defaultRole());

        $this->actingAs($viewer);

        /** @var User $target */
        $target = User::factory()->createOne();
        $target->assignRole(RoleConfig::defaultRole());

        Livewire::test('pages::users.show', ['user' => (string) $target->id])
            ->assertDontSeeHtml('id="avatar-upload"');
    });
});
