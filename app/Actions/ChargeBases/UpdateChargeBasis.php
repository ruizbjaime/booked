<?php

namespace App\Actions\ChargeBases;

use App\Models\ChargeBasis;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateChargeBasis
{
    /**
     * @var list<string>
     */
    private const array QUANTITY_SUBJECTS = ['guest', 'pet', 'vehicle', 'use'];

    public function handle(User $actor, ChargeBasis $chargeBasis, string $field, mixed $value): void
    {
        Gate::forUser($actor)->authorize('update', $chargeBasis);

        $normalized = $this->normalize($field, $value, $chargeBasis);

        $this->validate($chargeBasis, $field, $normalized);

        $chargeBasis->update($this->payload($field, $normalized, $chargeBasis));
    }

    private function normalize(string $field, mixed $value, ChargeBasis $chargeBasis): mixed
    {
        return match ($field) {
            'name' => is_string($value) ? Str::lower(trim($value)) : $value,
            'en_name', 'es_name', 'description' => is_string($value) ? trim($value) : $value,
            'metadata.requires_quantity' => (bool) $value,
            default => $value,
        };
    }

    private function validate(ChargeBasis $chargeBasis, string $field, mixed $value): void
    {
        $rules = match ($field) {
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('charge_bases', 'name')->ignore($chargeBasis->id)],
            'en_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-\/]+$/u'],
            'es_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-\/]+$/u'],
            'description' => ['nullable', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'metadata.requires_quantity' => ['required', 'boolean'],
            'metadata.quantity_subject' => ['nullable', 'string', Rule::in(self::QUANTITY_SUBJECTS)],
            default => abort(422),
        };

        Validator::make($this->validationData($field, $value), [$field => $rules])->after($this->quantitySubjectValidator($chargeBasis, $field, $value))->validate();
    }

    private function quantitySubjectValidator(ChargeBasis $chargeBasis, string $field, mixed $value): Closure
    {
        return function (ValidatorContract $validator) use ($chargeBasis, $field, $value): void {
            $metadata = $this->metadata($chargeBasis);

            if ($field === 'metadata.quantity_subject' && $metadata['requires_quantity'] && $value === null) {
                $validator->errors()->add('metadata.quantity_subject', __('charge_bases.validation.quantity_subject_required'));
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validationData(string $field, mixed $value): array
    {
        if (! str_starts_with($field, 'metadata.')) {
            return [$field => $value];
        }

        /** @var array<string, mixed> $data */
        $data = [];
        data_set($data, $field, $value);

        /** @var array<string, mixed> $metadata */
        $metadata = data_get($data, 'metadata', []);

        return ['metadata' => $metadata];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $field, mixed $value, ChargeBasis $chargeBasis): array
    {
        if (str_starts_with($field, 'metadata.')) {
            $metadata = $this->metadata($chargeBasis);
            data_set($metadata, Str::after($field, 'metadata.'), $value);

            return ['metadata' => $metadata];
        }

        return [$field => $value];
    }

    /**
     * @return array{requires_quantity: bool, quantity_subject: string|null}
     */
    private function metadata(ChargeBasis $chargeBasis): array
    {
        $metadata = $chargeBasis->getAttributeValue('metadata');

        if (! is_array($metadata)) {
            return [
                'requires_quantity' => false,
                'quantity_subject' => null,
            ];
        }

        return [
            'requires_quantity' => (bool) ($metadata['requires_quantity'] ?? false),
            'quantity_subject' => is_string($metadata['quantity_subject'] ?? null) ? $metadata['quantity_subject'] : null,
        ];
    }
}
