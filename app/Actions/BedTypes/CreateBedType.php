<?php

namespace App\Actions\BedTypes;

use App\Models\BedType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateBedType
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): BedType
    {
        Gate::forUser($actor)->authorize('create', BedType::class);

        $normalized = $this->normalize($input);

        $this->validate($normalized);

        return BedType::create($normalized);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        $input['name'] = is_string($input['name'] ?? null)
            ? Str::lower(trim($input['name']))
            : '';

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('bed_types', 'name')],
            'name_en' => ['required', 'string', 'max:255'],
            'name_es' => ['required', 'string', 'max:255'],
            'bed_capacity' => ['required', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ])->validate();
    }
}
