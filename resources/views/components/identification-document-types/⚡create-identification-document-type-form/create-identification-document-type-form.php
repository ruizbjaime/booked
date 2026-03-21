<?php

use App\Actions\IdentificationDocumentTypes\CreateIdentificationDocumentType;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Concerns\ThrottlesFormActions;
use App\Infrastructure\UiFeedback\ToastService;
use App\Models\IdentificationDocumentType;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    use ResolvesAuthenticatedUser;
    use ThrottlesFormActions;

    private const string THROTTLE_KEY_PREFIX = 'doc-type-mgmt';

    public string $code = '';

    public string $en_name = '';

    public string $es_name = '';

    public int $sort_order = 999;

    public bool $is_active = true;

    /**
     * @var array<string, mixed>
     */
    public array $context = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function mount(array $context = []): void
    {
        Gate::authorize('create', IdentificationDocumentType::class);

        $this->context = $context;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['code', 'en_name', 'es_name', 'sort_order', 'is_active'], true)) {
            $this->resetValidation($property);
        }
    }

    public function save(CreateIdentificationDocumentType $createDocType): void
    {
        if ($this->throttle('create', 5)) {
            return;
        }

        $docType = $createDocType->handle($this->actor(), [
            'code' => $this->code,
            'en_name' => $this->en_name,
            'es_name' => $this->es_name,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        ToastService::success(__('identification_document_types.create.created', [
            'doc_type' => __('identification_document_types.doc_type_label', ['name' => $docType->localizedName(), 'id' => $docType->id]),
        ]));

        $this->resetForm();

        $this->dispatch('close-form-modal');
        $this->dispatch('doc-type-created', docTypeId: $docType->id);
    }

    private function resetForm(): void
    {
        $this->reset('code', 'en_name', 'es_name');
        $this->sort_order = 999;
        $this->is_active = true;
    }
};
