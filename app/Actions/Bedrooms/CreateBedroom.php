<?php

namespace App\Actions\Bedrooms;

use App\Models\Bedroom;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateBedroom
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'en_description' => ['nullable', 'string', 'max:65535'],
            'es_description' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, Property $property, array $input): Bedroom
    {
        Gate::forUser($actor)->authorize('update', $property);

        $validated = $this->validate($input);

        $bedroom = $property->bedrooms()->create($validated);

        $property->flushAccommodationTotals();

        return $bedroom;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{en_name: string, es_name: string, en_description: string|null, es_description: string|null}
     */
    private function validate(array $input): array
    {
        $validated = Validator::make($input, self::rules())->validate();

        $enName = $validated['en_name'];
        $esName = $validated['es_name'];
        $enDescription = $validated['en_description'] ?? null;
        $esDescription = $validated['es_description'] ?? null;

        abort_unless(is_string($enName) && is_string($esName), 422);
        abort_unless($enDescription === null || is_string($enDescription), 422);
        abort_unless($esDescription === null || is_string($esDescription), 422);

        return [
            'en_name' => trim($enName),
            'es_name' => trim($esName),
            'en_description' => blank($enDescription) ? null : trim($enDescription),
            'es_description' => blank($esDescription) ? null : trim($esDescription),
        ];
    }
}
