<?php

namespace App\Actions\Roles;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateRole
{
    public function handle(User $actor, Role $role, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $role);

        $this->validate($role, $field, $value);

        $role->update([$field => $value]);
    }

    private function validate(Role $role, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'en_label' => ['required', 'string', 'max:255'],
            'es_label' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', Rule::in(CreateRole::AVAILABLE_COLORS)],
            'sort_order' => ['required', 'integer', 'min:0'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
