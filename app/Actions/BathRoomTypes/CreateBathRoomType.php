<?php

namespace App\Actions\BathRoomTypes;

use App\Models\BathRoomType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateBathRoomType
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): BathRoomType
    {
        Gate::forUser($actor)->authorize('create', BathRoomType::class);

        $normalized = $this->normalize($input);

        $this->validate($normalized);

        return BathRoomType::create($normalized);
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

        $input['en_name'] = is_string($input['en_name'] ?? null)
            ? trim($input['en_name'])
            : '';

        $input['es_name'] = is_string($input['es_name'] ?? null)
            ? trim($input['es_name'])
            : '';

        $input['description'] = is_string($input['description'] ?? null)
            ? trim($input['description'])
            : '';

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('bath_room_types', 'name')],
            'en_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'es_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-_]+$/u'],
            'description' => ['required', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ])->validate();
    }
}
