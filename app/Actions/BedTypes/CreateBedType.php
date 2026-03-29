<?php

namespace App\Actions\BedTypes;

use App\Models\BedType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

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
        $input['en_name'] = is_string($input['en_name'] ?? null)
            ? trim($input['en_name'])
            : '';

        $input['es_name'] = is_string($input['es_name'] ?? null)
            ? trim($input['es_name'])
            : '';

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'en_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'es_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'bed_capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ])->validate();
    }
}
