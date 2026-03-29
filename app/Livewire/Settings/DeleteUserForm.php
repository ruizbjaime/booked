<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Livewire\Actions\Logout;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;
    use ResolvesAuthenticatedUser;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $user = $this->actor();

        abort_if(! $user->hasVerifiedEmail(), 403);

        if ($user->properties()->exists()) {
            $this->addError('password', __('users.cannot_delete_with_properties'));

            return;
        }

        $logout();

        $user->delete();

        $this->redirect('/', navigate: true);
    }
}
