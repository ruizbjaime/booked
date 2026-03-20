<?php

namespace App\Livewire\Settings\TwoFactor;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RecoveryCodes extends Component
{
    /**
     * @var list<string>
     */
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes($this->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = $this->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $decrypted = decrypt($user->two_factor_recovery_codes);

                if (! is_string($decrypted)) {
                    throw new Exception('Invalid recovery codes format.');
                }

                $decoded = json_decode($decrypted, true);

                $this->recoveryCodes = is_array($decoded)
                    ? array_values(array_filter($decoded, 'is_string'))
                    : [];
            } catch (Exception) {
                $this->addError('recoveryCodes', __('Failed to load recovery codes.'));

                $this->recoveryCodes = [];
            }
        }
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_if(! $user instanceof User, 403);

        return $user;
    }
}
