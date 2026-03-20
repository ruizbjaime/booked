<?php

namespace App\Actions\IdentificationDocumentTypes;

use App\Models\IdentificationDocumentType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateIdentificationDocumentType
{
    public function handle(User $actor, IdentificationDocumentType $type, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $type);

        $normalized = $field === 'code' && is_string($value)
            ? strtoupper($value)
            : $value;

        $this->validate($type, $field, $normalized);

        $type->update([$field => $normalized]);
    }

    private function validate(IdentificationDocumentType $type, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'code' => ['required', 'string', 'max:20', Rule::unique('identification_document_types', 'code')->ignore($type->id)],
            'en_name' => ['required', 'string', 'max:255'],
            'es_name' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            default => abort(422),
        };

        Validator::make([$field => $value], [$field => $rules])->validate();
    }
}
