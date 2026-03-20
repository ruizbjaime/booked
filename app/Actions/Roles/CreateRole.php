<?php

namespace App\Actions\Roles;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateRole
{
    /**
     * @var list<string>
     */
    public const array AVAILABLE_COLORS = [
        'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal',
        'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'zinc',
    ];

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): Role
    {
        Gate::forUser($actor)->authorize('create', Role::class);

        $this->validate($input);

        $role = new Role([
            'name' => $input['name'],
            'guard_name' => 'web',
            'en_label' => $input['en_label'],
            'es_label' => $input['es_label'],
            'color' => $input['color'],
            'sort_order' => $input['sort_order'],
            'is_active' => (bool) ($input['is_active'] ?? false),
        ]);

        $role->save();

        return $role;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'en_label' => ['required', 'string', 'max:255'],
            'es_label' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', Rule::in(self::AVAILABLE_COLORS)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ])->validate();
    }
}
