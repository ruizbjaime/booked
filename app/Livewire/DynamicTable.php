<?php

namespace App\Livewire;

use App\Services\DataTableConfigService;
use App\Services\DataTablePresentationService;
use App\Services\DataTableQueryService;
use App\Services\DataTableValidationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Log;

/**
 * Componente Livewire para crear tablas dinámicas basadas en modelos Eloquent.
 * Utiliza servicios dedicados para validación, configuración, construcción de consultas y presentación.
 */
class DynamicTable extends Component
{
    use WithPagination;

    // --------------------------------------------------------------------------
    // Propiedades públicas (Estado del componente)
    // --------------------------------------------------------------------------

    /** @var string Nombre de la clase del modelo principal */
    public string $modelClass;

    /** @var array Configuración de columnas original */
    public array $originalColumnMap = [];

    /** @var string Dirección de ordenamiento (asc/desc) */
    public string $sortDirection = 'asc';

    /** @var string Campo por el cual ordenar */
    public $sortBy = 'created_at'; // Default sort

    /** @var array Acciones de tabla disponibles */
    public array $tableActions = [];

    /** @var string Término de búsqueda */
    public string $search = '';

    /** @var int Elementos por página */
    public int $perPage = 15;

    // --------------------------------------------------------------------------
    // Propiedades calculadas o derivadas (No son estado directo)
    // --------------------------------------------------------------------------

    /** @var Model|null Instancia del modelo validado */
    public ?Model $modelInstance = null;

    /** @var array Configuración de columnas validada */
    public array $validatedColumnMap = [];

    /** @var array Campos en los que se puede buscar */
    public array $searchableFields = [];

    /** @var array Campos por los que se puede ordenar */
    public array $sortableFields = [];

    // --------------------------------------------------------------------------
    // Servicios Inyectados (o localizados)
    // --------------------------------------------------------------------------
    protected DataTableValidationService $validationService;

    protected DataTableConfigService $configService;

    protected DataTableQueryService $queryService;

    protected DataTablePresentationService $presentationService;

    // --------------------------------------------------------------------------
    // Propiedades protegidas
    // --------------------------------------------------------------------------

    /** @var string Patrón para búsquedas LIKE */
    protected string $likePattern = '%';

    // --------------------------------------------------------------------------
    // Inicialización y eventos del ciclo de vida
    // --------------------------------------------------------------------------

    /**
     * Inicializa los servicios necesarios.
     * Este método es llamado por Livewire antes de mount().
     */
    public function boot(
        DataTableValidationService $validationService,
        DataTableConfigService $configService,
        DataTableQueryService $queryService,
        DataTablePresentationService $presentationService
    ): void {
        $this->validationService = $validationService;
        $this->configService = $configService;
        $this->queryService = $queryService;
        $this->presentationService = $presentationService;
    }

    /**
     * Inicializa el estado del componente y válida la configuración inicial.
     */
    public function mount(string $modelClass, array $columnMap, string $sortDirection = 'asc', array $tableActions = []): void
    {
        $this->modelClass = $modelClass;
        $this->originalColumnMap = $columnMap;
        $this->sortDirection = $sortDirection;
        $this->tableActions = $tableActions;

        // Validar modelo y mapa de columnas al montar
        $this->modelInstance = $this->validationService->validateModel($this->modelClass);
        $this->validatedColumnMap = $this->validationService->validateColumnMap($this->modelInstance, $this->originalColumnMap);

        // Extraer campos configurados usando el servicio
        $this->searchableFields = $this->configService->extractConfiguredFields($this->validatedColumnMap, 'searchable');
        $this->sortableFields = $this->configService->extractConfiguredFields($this->validatedColumnMap, 'sortable');

        // Establecer ordenamiento inicial si no es el default y es válido
        if ($this->sortBy !== 'created_at' && ! in_array($this->sortBy, $this->sortableFields)) {
            // Si el sortBy inicial no es válido, usar la clave primaria o created_at como fallback
            $defaultSort = 'created_at';
            if (! in_array('created_at', $this->validationService->getTableColumns($this->modelInstance))) {
                $defaultSort = $this->modelInstance->getKeyName();
            }
            $this->sortBy = $defaultSort;
        }
    }

    /**
     * Cambia el sentido de ordenamiento y el campo.
     */
    public function sort(string $field): void
    {
        // Solo permitir ordenar por campos configurados como 'sortable'
        if (! in_array($field, $this->sortableFields)) {
            return;
        }

        // Si se ordena por el mismo campo, invertir dirección, si no, establecer campo y dirección asc
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage(); // Resetear paginación al cambiar orden
    }

    /**
     * Reinicia la paginación cuando se cambia el término de búsqueda.
     * Livewire maneja esto automáticamente si se nombra `updating<PropertyName>`.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // Nota: updatingSortBy y updatingSortDirection no son necesarios si
    // resetPage() se llama directamente en el método sort().

    // --------------------------------------------------------------------------
    // Presentación de datos (Delegado al servicio)
    // --------------------------------------------------------------------------

    /**
     * Obtiene el valor formateado para una columna usando el servicio de presentación.
     */
    public function getValueForColumn(Model $item, string $column): mixed
    {
        // Asegurarse de que el modelo y el mapa de columnas estén inicializados
        if (! $this->modelInstance || empty($this->validatedColumnMap)) {
            return 'Error: Componente no inicializado correctamente.';
        }

        return $this->presentationService->getFormattedValue($item, $column);
    }

    /**
     * Verifica si un índice es par (utilidad simple para la vista).
     */
    public function isEven(int $index): bool
    {
        return $index % 2 !== 0;
    }

    /**
     * No hacer nada
     */
    public function noOp(): void {}

    // --------------------------------------------------------------------------
    // Renderizado
    // --------------------------------------------------------------------------

    /**
     * Renderiza el componente, construyendo la consulta y paginando los resultados.
     */
    #[On('refresh-table')]
    public function render(): View
    {
        // Asegurarse de que el modelo y el mapa de columnas estén inicializados
        if (! $this->modelInstance || empty($this->validatedColumnMap)) {
            // Mostrar una vista de error o un estado vacío si el componente no está listo.
            // Esto evita errores fatales si algo falla durante la inicialización.
            Log::warning('DynamicTable render attempt failed: Component not properly initialized.', [
                'modelClass' => $this->modelClass ?? 'N/A',
                'hasModelInstance' => ! is_null($this->modelInstance),
                'hasValidatedColumnMap' => ! empty($this->validatedColumnMap),
            ]);

            // Puedes retornar una vista específica para errores o un mensaje simple.
            // Asegúrate de que la vista 'livewire. Dynamic-table-error' exista o crea una.
            // Return view('livewire. Dynamic-table-error', ['message' => 'Error: El componente de tabla dinámica no se inicializó correctamente.']);
            // O retornar una vista vacía o con un mensaje inline si prefieres no crear otra vista:
            return view('livewire.dynamic-table', [
                'items' => collect(), // Colección vacía para evitar errores en la vista
                'columnMap' => [],
                'tableActions' => [],
                'sortBy' => $this->sortBy,
                'sortDirection' => $this->sortDirection,
                'initializationError' => 'El componente no se pudo inicializar correctamente.', // Pasar un mensaje de error
            ]);
        }

        // Construir la consulta usando el servicio de consulta
        $query = $this->queryService->buildQuery(
            $this->modelInstance,
            $this->validatedColumnMap,
            $this->search,
            $this->searchableFields,
            $this->sortBy,
            $this->sortableFields,
            $this->sortDirection,
            $this->likePattern
        );

        // Paginar los resultados
        $items = $query->paginate($this->perPage);

        // Pasar los datos necesarios a la vista
        return view('livewire.dynamic-table', [
            'items' => $items,
            'columnMap' => $this->validatedColumnMap, // Pasar el mapa validado
            'tableActions' => $this->tableActions,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'initializationError' => null, // Asegurarse de que no haya error si todo va bien
        ]);
    }
}
