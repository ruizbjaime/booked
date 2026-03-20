<?php

namespace App\Actions\IdentificationDocumentTypes;

use App\Models\IdentificationDocumentType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateIdentificationDocumentType
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): IdentificationDocumentType
    {
        Gate::forUser($actor)->authorize('create', IdentificationDocumentType::class);

        $this->validate($input);

        $code = is_string($input['code'] ?? null) ? strtoupper($input['code']) : '';

        return IdentificationDocumentType::create([
            'code' => $code,
            'en_name' => $input['en_name'],
            'es_name' => $input['es_name'],
            'sort_order' => $input['sort_order'],
            'is_active' => (bool) ($input['is_active'] ?? false),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'code' => ['required', 'string', 'max:20', Rule::unique('identification_document_types', 'code')],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ])->validate();
    }
}
