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

class CreateChargeBasis
{
    /**
     * @var list<string>
     */
    private const array QUANTITY_SUBJECTS = ['guest', 'pet', 'vehicle', 'use'];

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): ChargeBasis
    {
        Gate::forUser($actor)->authorize('create', ChargeBasis::class);

        $normalized = $this->normalize($input);

        $this->validate($normalized);

        return ChargeBasis::create($normalized);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     en_description: string|null,
     *     es_description: string|null,
     *     order: int,
     *     is_active: bool,
     *     metadata: array{requires_quantity: bool, quantity_subject: string|null}
     * }
     */
    private function normalize(array $input): array
    {
        $order = $input['order'] ?? 999;

        return [
            'name' => is_string($input['name'] ?? null) ? Str::lower(trim($input['name'])) : '',
            'en_name' => is_string($input['en_name'] ?? null) ? trim($input['en_name']) : '',
            'es_name' => is_string($input['es_name'] ?? null) ? trim($input['es_name']) : '',
            'en_description' => is_string($input['en_description'] ?? null) ? trim($input['en_description']) : null,
            'es_description' => is_string($input['es_description'] ?? null) ? trim($input['es_description']) : null,
            'order' => is_int($order) ? $order : 999,
            'is_active' => (bool) ($input['is_active'] ?? false),
            'metadata' => [
                'requires_quantity' => (bool) data_get($input, 'metadata.requires_quantity', false),
                'quantity_subject' => is_string($subject = data_get($input, 'metadata.quantity_subject')) ? $subject : null,
            ],
        ];
    }

    /**
     * @param  array{
     *     name: string,
     *     en_name: string,
     *     es_name: string,
     *     en_description: string|null,
     *     es_description: string|null,
     *     order: int,
     *     is_active: bool,
     *     metadata: array{requires_quantity: bool, quantity_subject: string|null}
     * }  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('charge_bases', 'name')],
            'en_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-\/]+$/u'],
            'es_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}][\p{L}\p{N}\s.,()\-\/]+$/u'],
            'en_description' => ['nullable', 'string', 'max:255'],
            'es_description' => ['nullable', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'metadata' => ['array'],
            'metadata.requires_quantity' => ['required', 'boolean'],
            'metadata.quantity_subject' => ['nullable', 'string', Rule::in(self::QUANTITY_SUBJECTS)],
        ])->after($this->quantitySubjectValidator($input))->validate();
    }

    /**
     * @param  array{
     *     metadata: array{requires_quantity: bool, quantity_subject: string|null}
     * }  $input
     */
    private function quantitySubjectValidator(array $input): Closure
    {
        return function (ValidatorContract $validator) use ($input): void {
            if ($input['metadata']['requires_quantity'] && $input['metadata']['quantity_subject'] === null) {
                $validator->errors()->add('metadata.quantity_subject', __('charge_bases.validation.quantity_subject_required'));
            }
        };
    }
}
