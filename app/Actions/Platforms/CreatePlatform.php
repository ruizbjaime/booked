<?php

namespace App\Actions\Platforms;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreatePlatform
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
    public function handle(User $actor, array $input): Platform
    {
        Gate::forUser($actor)->authorize('create', Platform::class);

        $this->validate($input);

        $commission = is_numeric($input['commission']) ? (float) $input['commission'] / 100 : 0;
        $commissionTax = is_numeric($input['commission_tax']) ? (float) $input['commission_tax'] / 100 : 0;

        return Platform::create([
            'name' => $input['name'],
            'en_name' => $input['en_name'],
            'es_name' => $input['es_name'],
            'color' => $input['color'],
            'sort_order' => $input['sort_order'],
            'commission' => $commission,
            'commission_tax' => $commissionTax,
            'is_active' => (bool) ($input['is_active'] ?? false),
        ]);
    }

    /**
     * Validate the color value: must be a predefined FluxUI color OR a valid hex color.
     */
    public static function isValidColor(string $value): bool
    {
        return in_array($value, self::AVAILABLE_COLORS, true)
            || (bool) preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('platforms', 'name')],
            'en_name' => ['required', 'string', 'max:255', Rule::unique('platforms', 'en_name')],
            'es_name' => ['required', 'string', 'max:255', Rule::unique('platforms', 'es_name')],
            'color' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! self::isValidColor($value)) {
                    $fail(__('validation.in', ['attribute' => $attribute]));
                }
            }],
            'sort_order' => ['required', 'integer', 'min:0'],
            'commission' => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_tax' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ])->validate();
    }
}
