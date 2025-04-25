<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataTableQueryService
{
    protected DataTableValidationService $validationService;

    protected DataTableConfigService $configService;

    public function __construct(DataTableValidationService $validationService, DataTableConfigService $configService)
    {
        $this->validationService = $validationService;
        $this->configService = $configService;
    }

    /**
     * Construye la consulta principal para la tabla dinámica.
     *
     * @param  Model  $model  Instancia del modelo base.
     * @param  array  $columnMap  Configuración de columnas.
     * @param  string  $search  Término de búsqueda.
     * @param  array  $searchableFields  Campos en los que buscar.
     * @param  string  $sortBy  Campo por el cual ordenar.
     * @param  array  $sortableFields  Campos por los que se puede ordenar.
     * @param  string  $sortDirection  Dirección de ordenamiento ('asc' o 'desc').
     * @param  string  $likePattern  Patrón para búsquedas LIKE.
     * @return Builder Constructor de consultas Eloquent configurado.
     */
    public function buildQuery(
        Model $model,
        array $columnMap,
        string $search,
        array $searchableFields,
        string $sortBy,
        array $sortableFields,
        string $sortDirection,
        string $likePattern = '%'
    ): Builder {
        $query = $model::query();
        $mainTable = $model->getTable();

        // Determinar si necesitamos una estrategia específica para BelongsTo
        $needsSpecificColumns = $this->needsSpecificColumnsForBelongsToSort($model, $sortBy, $sortableFields);

        // Seleccionar columnas necesarias (evitar seleccionar todo si no es necesario)
        if (! $needsSpecificColumns) {
            $requiredColumns = $this->getRequiredColumns($model, $columnMap);
            // Asegurarse de que las columnas se califiquen con la tabla principal para evitar ambigüedades
            $qualifiedColumns = collect($requiredColumns)->map(fn ($col) => "{$mainTable}.{$col}")->all();
            $query->select($qualifiedColumns);
        } else {
            // Si se ordena por BelongsTo, la selección se maneja dentro de applySortingForBelongsTo
            // para incluir las columnas de la tabla principal explícitamente.
        }

        // Cargar relaciones necesarias para mostrar datos y potencialmente para búsqueda/ordenamiento
        if ($relations = $this->getRelationshipsToLoad($columnMap)) {
            $query->with($relations);
        }

        // Aplicar filtros de búsqueda si hay término de búsqueda y campos buscables
        if (! empty(trim($search)) && ! empty($searchableFields)) {
            $this->applySearchFilters($query, $search, $searchableFields, $columnMap, $likePattern);
        }

        // Aplicar ordenamiento si hay campo y dirección válidos
        if (! empty($sortBy) && in_array($sortBy, $sortableFields)) {
            $this->applySorting($query, $model, $columnMap, $sortBy, $sortDirection);
        } else {
            // Ordenamiento por defecto si no se especifica uno válido
            $defaultSortColumn = $model->getKeyName(); // O 'created_at' si existe
            if (in_array('created_at', $this->validationService->getTableColumns($model))) {
                $defaultSortColumn = 'created_at';
            }
            $query->orderBy("{$mainTable}.{$defaultSortColumn}", $sortDirection);
        }

        return $query;
    }

    /**
     * Determina si se necesita un manejo especial de selección para ordenar por relaciones BelongsTo.
     *
     * @param  Model  $model  Modelo base.
     * @param  string  $sortBy  Campo de ordenamiento actual.
     * @param  array  $sortableFields  Campos ordenables configurados.
     * @return bool True si se ordena por una relación BelongsTo válida.
     */
    protected function needsSpecificColumnsForBelongsToSort(Model $model, string $sortBy, array $sortableFields): bool
    {
        if (empty($sortBy) || ! in_array($sortBy, $sortableFields) || ! Str::contains($sortBy, '.')) {
            return false; // No se ordena, no es ordenable, o es un campo directo
        }

        $relationParts = $this->validationService->getRelationParts($sortBy);
        if (empty($relationParts)) {
            return false; // No debería ocurrir si Str::contains es true, pero por seguridad
        }

        $firstRelationName = $relationParts[0];
        $relation = $this->validationService->getRelationInstance($model, $firstRelationName);

        // Es necesario si la *primera* relación en la cadena es BelongsTo
        return $relation instanceof BelongsTo;
    }

    /**
     * Obtiene las columnas necesarias de la tabla principal para la consulta SELECT.
     *
     * @param  Model  $model  Instancia del modelo base.
     * @param  array  $columnMap  Configuración de columnas.
     * @return array Lista de nombres de columnas requeridas.
     */
    protected function getRequiredColumns(Model $model, array $columnMap): array
    {
        $tableColumns = $this->validationService->getTableColumns($model);
        $mainTable = $model->getTable();

        // Siempre incluir la clave primaria
        $columns = collect([$model->getKeyName()]);

        // Añadir columnas directas del modelo que están en el columnMap y existen en la tabla
        foreach ($columnMap as $field => $config) {
            if (! Str::contains($field, '.') && in_array($field, $tableColumns)) {
                $columns->push($field);
            }
        }

        // Agregar claves foráneas necesarias para las relaciones definidas en columnMap
        $foreignKeys = $this->getForeignKeysForRelations($model, $columnMap);
        $columns = $columns->merge($foreignKeys);

        // Asegurarse de que todas las columnas seleccionadas realmente existan en la tabla
        return $columns->unique()
            ->values()
            ->filter(fn ($col) => in_array($col, $tableColumns)) // Doble verificación
            ->all();
    }

    /**
     * Obtiene las claves foráneas de la tabla principal necesarias para cargar o filtrar relaciones.
     *
     * @param  Model  $model  Instancia del modelo base.
     * @param  array  $columnMap  Configuración de columnas.
     * @return Collection Colección de nombres de claves foráneas.
     */
    protected function getForeignKeysForRelations(Model $model, array $columnMap): Collection
    {
        $foreignKeys = collect();
        $tableColumns = $this->validationService->getTableColumns($model); // Columnas de la tabla principal

        foreach ($columnMap as $field => $config) {
            if (! Str::contains($field, '.')) {
                continue; // Solo procesar campos de relación
            }

            $relationParts = $this->validationService->getRelationParts($field);
            if (empty($relationParts)) {
                continue;
            }

            $currentModel = $model; // Empezar desde el modelo principal para cada campo del columnMap

            // Iterar sobre las partes de la relación para encontrar claves en el modelo *actual*
            $relationName = $relationParts[0]; // Solo necesitamos la primera relación para la FK en la tabla principal
            $relation = $this->validationService->getRelationInstance($currentModel, $relationName);

            if (! $relation) {
                continue; // Relación no válida
            }

            // Extraer claves específicas según el tipo de la *primera* relación
            match (true) {
                $relation instanceof BelongsTo => $foreignKeys->when(
                    // La FK está en la tabla principal ($currentModel)
                    in_array($relation->getForeignKeyName(), $tableColumns),
                    fn ($keys) => $keys->push($relation->getForeignKeyName())
                ),
                $relation instanceof MorphTo => $foreignKeys->when(
                    // La FK y el tipo están en la tabla principal ($currentModel)
                    in_array($relation->getForeignKeyName(), $tableColumns),
                    fn ($keys) => $keys->push($relation->getForeignKeyName())
                )->when(
                    in_array($relation->getMorphType(), $tableColumns),
                    fn ($keys) => $keys->push($relation->getMorphType())
                ),
                // Para otros tipos de relación (HasOne, HasMany, ManyToMany, etc.),
                // la clave foránea relevante no está en la tabla principal, sino en la relacionada o pivote.
                // No necesitamos añadirlas explícitamente al SELECT de la tabla principal.
                default => null
            };

        }

        return $foreignKeys->unique(); // Devolver claves únicas
    }

    /**
     * Obtiene las relaciones a cargar mediante `with()`.
     * Extrae todas las rutas de relación únicas del columnMap.
     *
     * @param  array  $columnMap  Configuración de columnas.
     * @return array Lista de cadenas de relación para eager loading (ej. ['relation1', 'relation1.relation2']).
     */
    public function getRelationshipsToLoad(array $columnMap): array
    {
        return collect($columnMap)
            ->keys()
            ->filter(fn ($field) => Str::contains($field, '.')) // Solo campos con punto
            ->flatMap(function ($field) {
                // Obtener todas las partes de la relación (ej. 'user.country.name' -> ['user', 'country'])
                $parts = $this->validationService->getRelationParts($field);

                // Construir todas las rutas anidadas (ej. ['user', 'user.country'])
                return $this->buildNestedRelationPaths($parts);
            })
            ->unique() // Evitar duplicados
            ->sortBy(fn ($path) => substr_count($path, '.') + 1) // Ordenar por profundidad (opcional, pero puede ayudar a Eloquent)
            ->values()
            ->all();
    }

    /**
     * Construye caminos de relación anidados a partir de las partes.
     * Ej: ['user', 'country'] -> ['user', 'user.country']
     *
     * @param  array  $parts  Array de nombres de relación.
     * @return array Array de caminos de relación anidados.
     */
    protected function buildNestedRelationPaths(array $parts): array
    {
        $paths = [];
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath = $currentPath ? "$currentPath.$part" : $part;
            $paths[] = $currentPath;
        }

        return $paths;
    }

    // --------------------------------------------------------------------------
    // Filtros de búsqueda
    // --------------------------------------------------------------------------

    /**
     * Aplica filtros de búsqueda a la consulta principal.
     *
     * @param  Builder  $query  Constructor de consultas.
     * @param  string  $search  Término de búsqueda.
     * @param  array  $searchableFields  Campos configurados como buscables.
     * @param  array  $columnMap  Configuración completa de columnas.
     * @param  string  $likePattern  Patrón para búsquedas LIKE.
     */
    protected function applySearchFilters(Builder $query, string $search, array $searchableFields, array $columnMap, string $likePattern): void
    {
        $searchTerm = $likePattern.$search.$likePattern;
        $mainTable = $query->getModel()->getTable(); // Obtener tabla principal para calificar columnas

        $query->where(function (Builder $query) use ($searchableFields, $searchTerm, $columnMap, $mainTable) {
            foreach ($searchableFields as $field) {
                if (Str::contains($field, '.')) {
                    // Búsqueda en relación anidada
                    $this->applyNestedRelationSearchFilter($query, $field, $searchTerm, $columnMap);
                } else {
                    // Búsqueda en campo directo de la tabla principal
                    // Calificar el nombre de la columna con el nombre de la tabla principal
                    $query->orWhere("{$mainTable}.{$field}", 'like', $searchTerm);
                }
            }
        });
    }

    /**
     * Aplica filtros de búsqueda para relaciones anidadas usando `orWhereHas`.
     *
     * @param  Builder  $query  Constructor de consultas principal.
     * @param  string  $field  Campo de relación anidada (ej. 'relation.property').
     * @param  string  $searchTerm  Término de búsqueda con patrones LIKE.
     * @param  array  $columnMap  Configuración completa de columnas.
     */
    protected function applyNestedRelationSearchFilter(Builder $query, string $field, string $searchTerm, array $columnMap): void
    {
        $relationParts = $this->validationService->getRelationParts($field);
        $relationPath = implode('.', $relationParts); // Ruta para whereHas (ej. 'relation1.relation2')
        $property = last(explode('.', $field)); // Propiedad final a buscar (ej. 'property')

        // Usar columnas de búsqueda configuradas o la propiedad por defecto
        $searchColumns = $this->configService->getSearchColumnsForRelation($columnMap, $field, $property);

        // Aplicar filtro usando orWhereHas para buscar dentro de la relación
        $query->orWhereHas($relationPath, function (Builder $relationQuery) use ($searchColumns, $searchTerm) {
            // Dentro de la subconsulta de la relación
            $relationQuery->where(function (Builder $nestedQuery) use ($searchColumns, $searchTerm) {
                $relatedTable = $nestedQuery->getModel()->getTable(); // Tabla de la relación
                foreach ($searchColumns as $column) {
                    // Calificar columna con la tabla de la relación
                    $nestedQuery->orWhere("{$relatedTable}.{$column}", 'like', $searchTerm);
                }
            });
        });
    }

    // --------------------------------------------------------------------------
    // Ordenamiento
    // --------------------------------------------------------------------------

    /**
     * Aplica ordenamiento a la consulta según la configuración.
     *
     * @param  Builder  $query  Constructor de consultas.
     * @param  Model  $model  Modelo base.
     * @param  array  $columnMap  Configuración de columnas.
     * @param  string  $sortBy  Campo por el cual ordenar.
     * @param  string  $sortDirection  Dirección de ordenamiento ('asc' o 'desc').
     */
    protected function applySorting(Builder $query, Model $model, array $columnMap, string $sortBy, string $sortDirection): void
    {
        $mainTable = $model->getTable();

        // Para campos sin relaciones (directos del modelo principal)
        if (! Str::contains($sortBy, '.')) {
            // Validar que la columna exista en la tabla principal
            $validSortBy = $this->validationService->validateSortColumn($sortBy, $model);
            // Calificar con el nombre de la tabla principal
            $query->orderBy("{$mainTable}.{$validSortBy}", $sortDirection);

            return;
        }

        // Para campos con relaciones (ej. 'relation.property')
        $relationParts = $this->validationService->getRelationParts($sortBy);
        $column = last(explode('.', $sortBy)); // La propiedad final
        $relationPath = implode('.', $relationParts); // La ruta de relación completa

        // Determinar tipo de la *primera* relación para elegir estrategia
        $firstRelationName = $relationParts[0];
        $relationInstance = $this->validationService->getRelationInstance($model, $firstRelationName);

        if (! $relationInstance) {
            // Si la primera relación no es válida, no se puede ordenar por ella.
            // Opcional: ordenar por defecto o lanzar error/log. Aquí ordenamos por PK.
            Log::warning("No se pudo ordenar por '$sortBy' porque la relación '$firstRelationName' no es válida.");
            $query->orderBy("{$mainTable}.".$model->getKeyName(), 'asc');

            return;
        }

        // Clasificar y aplicar estrategia según tipo de relación
        $relationType = $this->classifyRelation($relationInstance);

        // Obtener la columna física real para ordenar desde la configuración
        $physicalSortColumn = $this->configService->getSortingColumn($columnMap, $sortBy, $column);

        switch ($relationType) {
            case 'BelongsTo':
                // Pasar las partes de la relación, no solo la primera
                $this->applySortingForBelongsTo($query, $model, $relationParts, $physicalSortColumn, $sortDirection);
                break;
                // Para los demás casos, pasamos la ruta completa y la columna física
            case 'HasRelation': // HasOne, HasMany, MorphOne, MorphMany
                $this->applySortingForHasRelation($query, $model, $relationPath, $physicalSortColumn, $sortDirection);
                break;
            case 'ManyToMany': // BelongsToMany, MorphToMany
                $this->applySortingForManyToMany($query, $model, $relationPath, $physicalSortColumn, $sortDirection);
                break;
            case 'ThroughRelation': // HasOneThrough, HasManyThrough
                $this->applySortingForThroughRelation($query, $model, $relationPath, $physicalSortColumn, $sortDirection);
                break;
            default:
                // Fallback genérico (podría ser menos eficiente)
                Log::warning("Usando estrategia de ordenamiento genérica (subconsulta) para '$sortBy' (Tipo: $relationType).");
                $this->applySortingWithSubquery($query, $model, $relationPath, $physicalSortColumn, $sortDirection);
                break;
        }
    }

    /**
     * Clasifica la relación en uno de los grupos para estrategias de ordenamiento.
     *
     * @param  Relation  $relation  Instancia de la relación.
     * @return string Tipo de relación clasificado.
     */
    protected function classifyRelation(Relation $relation): string
    {
        return match (true) {
            $relation instanceof HasOne,
            $relation instanceof HasMany,
            $relation instanceof MorphOne,
            $relation instanceof MorphMany => 'HasRelation', // Relaciones donde la FK está en la tabla relacionada

            $relation instanceof BelongsTo, // Incluye MorphTo implícitamente si se usa correctamente
            $relation instanceof MorphTo => 'BelongsTo', // Relaciones donde la FK está en la tabla principal

            $relation instanceof BelongsToMany,
            $relation instanceof MorphToMany => 'ManyToMany', // Relaciones que usan tabla pivote

            $relation instanceof HasOneThrough,
            $relation instanceof HasManyThrough => 'ThroughRelation', // Relaciones a través de otra tabla

            default => 'Other' // Otros tipos o desconocidos
        };
    }

    /**
     * Ordenamiento para relaciones BelongsTo (incluyendo anidadas).
     * Usa LEFT JOINs para traer las columnas relacionadas y ordenar.
     *
     * @param  Builder  $query  Constructor de consultas.
     * @param  Model  $model  Modelo principal.
     * @param  array  $relationParts  Array con los nombres de las relaciones anidadas.
     * @param  string  $column  Columna final en la tabla relacionada por la cual ordenar.
     * @param  string  $sortDirection  Dirección de ordenamiento.
     */
    protected function applySortingForBelongsTo(Builder $query, Model $model, array $relationParts, string $column, string $sortDirection): void
    {
        $currentModel = $model;
        $mainTable = $currentModel->getTable();
        $aliasCounter = 0; // Para generar alias únicos
        $lastTableAlias = $mainTable; // Empezamos con la tabla principal

        // --- Inicio: Asegurar que las columnas de la tabla principal estén seleccionadas ---
        // Esto es crucial porque los JOINs pueden causar ambigüedad si no se seleccionan explícitamente.
        // Si $query->getQuery()->columns ya está poblado (caso !needsSpecificColumns...), lo respetamos.
        // Si está vacío (caso needsSpecificColumns...), seleccionamos todo de la tabla principal.
        if (empty($query->getQuery()->columns)) {
            $tableColumns = $this->validationService->getTableColumns($currentModel);
            foreach ($tableColumns as $col) {
                $query->addSelect("{$mainTable}.{$col}"); // Calificar con tabla principal
            }
        }
        // --- Fin: Asegurar selección ---

        // Construir JOINs encadenados para cada parte de la relación
        foreach ($relationParts as $relationName) {
            $relation = $this->validationService->getRelationInstance($currentModel, $relationName);

            // Si en la cadena encontramos una relación que no es BelongsTo,
            // no podemos continuar con JOINs simples. Podríamos cambiar a subconsulta,
            // pero por simplicidad, aquí paramos y ordenamos por lo que tenemos hasta ahora (o fallback).
            if (! $relation instanceof BelongsTo) {
                Log::warning("Cadena de ordenamiento '$relationName' contiene relación no BelongsTo. Ordenamiento puede ser impreciso.");
                // Ordenar por la clave del último modelo unido correctamente
                $fallbackSortColumn = $this->validationService->validateSortColumn($currentModel->getKeyName(), $currentModel);
                $query->orderBy("{$lastTableAlias}.{$fallbackSortColumn}", $sortDirection);

                return; // Salir del método de ordenamiento BelongsTo
            }

            $relatedTable = $relation->getRelated()->getTable();
            $tableAlias = 'sort_join_'.$aliasCounter++; // Alias único para la tabla unida

            $foreignKey = $relation->getForeignKeyName(); // Clave en la tabla "padre" (actual)
            $ownerKey = $relation->getOwnerKeyName(); // Clave en la tabla "hija" (relacionada)

            // Unir la tabla relacionada usando el alias
            $query->leftJoin(
                "{$relatedTable} as {$tableAlias}", // Tabla unida con alias
                "{$lastTableAlias}.{$foreignKey}", // Columna FK en la tabla/alias anterior
                '=',
                "{$tableAlias}.{$ownerKey}" // Columna PK (o owner key) en la tabla/alias actual
            );

            // Actualizar para la siguiente iteración
            $currentModel = $relation->getRelated(); // El modelo relacionado se vuelve el actual
            $lastTableAlias = $tableAlias; // El alias de la tabla unida se vuelve el último alias
        }

        // Al final del bucle, $currentModel es el modelo de la última relación
        // y $lastTableAlias es el alias de la tabla de esa última relación.

        // Validar la columna de ordenamiento final en el *último* modelo relacionado
        $validSortColumn = $this->validationService->validateSortColumn($column, $currentModel);

        // Aplicar el ORDER BY usando el último alias y la columna validada
        $query->orderBy("{$lastTableAlias}.{$validSortColumn}", $sortDirection);
    }

    /**
     * Ordenamiento para relaciones HasOne/HasMany/MorphOne/MorphMany.
     * Usa una subconsulta para obtener el valor ordenable.
     *
     * @param  Builder  $query  Constructor de consultas.
     * @param  Model  $model  Modelo principal.
     * @param  string  $relationPath  Ruta de la relación (puede ser anidada).
     * @param  string  $column  Columna en la tabla relacionada por la cual ordenar.
     * @param  string  $sortDirection  Dirección de ordenamiento.
     */
    protected function applySortingForHasRelation(Builder $query, Model $model, string $relationPath, string $column, string $sortDirection): void
    {
        $mainTable = $model->getTable();
        $mainTableKey = $model->getKeyName();

        // Necesitamos obtener la instancia de la relación final para validar la columna
        $relationInstance = $this->getNestedRelationInstance($model, $relationPath);
        if (! $relationInstance) {
            Log::warning("No se pudo obtener la instancia de la relación '$relationPath' para ordenar.");
            $query->orderBy("{$mainTable}.{$mainTableKey}", 'asc'); // Fallback

            return;
        }

        $relatedModel = $relationInstance->getRelated();
        $relatedTable = $relatedModel->getTable();
        // Validar la columna de ordenamiento final en el *último* modelo relacionado
        $validSortColumn = $this->validationService->validateSortColumn($column, $relatedModel);

        // Construir la subconsulta para obtener el valor ordenable (MIN/MAX dependiendo de la dirección podría ser mejor)
        // Usamos una subconsulta correlacionada
        $subQuery = $relatedModel->newQuery()
            ->select($validSortColumn)
            // La condición WHERE depende del tipo exacto de relación y anidamiento
            // Esto requiere una lógica más compleja para construir el WHERE dinámicamente
            // basado en la cadena de relaciones $relationPath.
            // Por simplicidad aquí, asumimos una relación directa (no anidada) para el ejemplo.
            // Una implementación completa necesitaría parsear $relationPath y construir joins/wheres internos.
            ->whereColumn(
                $this->buildSubqueryWhereColumnCondition($model, $relationPath), // Necesita lógica para construir la condición
                "{$mainTable}.{$mainTableKey}" // Comparar con la clave de la tabla principal
            )
             // Para relaciones Morph, se necesita añadir la condición del tipo
             // ->when($relationInstance instanceof MorphOne || $relationInstance instanceof MorphMany, fn($q) => $q->where($relationInstance->getMorphType(), $relationInstance->getMorphClass()))
            ->orderBy($validSortColumn, $sortDirection) // Ordenar dentro para tomar el primero/último
            ->limit(1);

        // Añadir la subconsulta como una columna seleccionada para ordenar
        $sortAlias = 'sort_subquery_val';
        $query->addSelect([
            $sortAlias => $subQuery,
        ]);

        // Ordenar por la columna de la subconsulta
        $query->orderBy($sortAlias, $sortDirection);

        // Limpiar la selección extra después de ordenar si es posible (puede complicar GROUP BY si se usa)
        // $query->getQuery()->columns = array_filter($query->getQuery()->columns, fn($col) => !($col instanceof \Illuminate\Database\Query\Expression && str_contains($col->getValue($query->getGrammar()), $sortAlias)));
        // Es más seguro dejarla seleccionada.
    }

    /**
     * Ordenamiento para relaciones BelongsToMany/MorphToMany.
     * Usa una subconsulta compleja con JOIN a la tabla pivote.
     *
     * @param  Builder  $query  Constructor de consultas.
     * @param  Model  $model  Modelo principal.
     * @param  string  $relationPath  Ruta de la relación.
     * @param  string  $column  Columna en la tabla relacionada final por la cual ordenar.
     * @param  string  $sortDirection  Dirección de ordenamiento.
     */
    protected function applySortingForManyToMany(Builder $query, Model $model, string $relationPath, string $column, string $sortDirection): void
    {
        $mainTable = $model->getTable();
        $mainTableKey = $model->getKeyName();

        $relationInstance = $this->getNestedRelationInstance($model, $relationPath);
        if (! $relationInstance || ! ($relationInstance instanceof BelongsToMany)) { // Asegurarse que sea ManyToMany
            Log::warning("No se pudo obtener la instancia de la relación ManyToMany '$relationPath' para ordenar.");
            $query->orderBy("{$mainTable}.{$mainTableKey}", 'asc');

            return;
        }

        $relatedModel = $relationInstance->getRelated();
        $relatedTable = $relatedModel->getTable();
        $pivotTable = $relationInstance->getTable(); // Tabla pivote
        $validSortColumn = $this->validationService->validateSortColumn($column, $relatedModel);

        // Claves de la relación
        $foreignPivotKey = $relationInstance->getForeignPivotKeyName(); // Clave del modelo principal en la tabla pivote
        $relatedPivotKey = $relationInstance->getRelatedPivotKeyName(); // Clave del modelo relacionado en la tabla pivote
        $relatedKey = $relatedModel->getKeyName(); // Clave primaria del modelo relacionado

        // Construir subconsulta
        $subQuery = $relatedModel->newQuery()
            ->select("{$relatedTable}.{$validSortColumn}")
            ->join($pivotTable, "{$pivotTable}.{$relatedPivotKey}", '=', "{$relatedTable}.{$relatedKey}")
            ->whereColumn("{$pivotTable}.{$foreignPivotKey}", "{$mainTable}.{$mainTableKey}")
            // Para MorphToMany, añadir condición de tipo
            ->when($relationInstance instanceof MorphToMany, function ($q) use ($relationInstance, $pivotTable) {
                $q->where("{$pivotTable}.".$relationInstance->getMorphType(), $relationInstance->getMorphClass());
            })
            ->orderBy("{$relatedTable}.{$validSortColumn}", $sortDirection)
            ->limit(1);

        // Añadir subconsulta y ordenar
        $sortAlias = 'sort_subquery_val';
        $query->addSelect([$sortAlias => $subQuery]);
        $query->orderBy($sortAlias, $sortDirection);
    }

    /**
     * Ordenamiento para relaciones HasOneThrough/HasManyThrough.
     * Usa una subconsulta con JOIN a través de la tabla intermedia.
     *
     * @param  Builder  $query  Constructor de consultas.
     * @param  Model  $model  Modelo principal.
     * @param  string  $relationPath  Ruta de la relación.
     * @param  string  $column  Columna en la tabla lejana por la cual ordenar.
     * @param  string  $sortDirection  Dirección de ordenamiento.
     */
    protected function applySortingForThroughRelation(Builder $query, Model $model, string $relationPath, string $column, string $sortDirection): void
    {
        $mainTable = $model->getTable();
        $mainTableKey = $model->getKeyName();

        $relationInstance = $this->getNestedRelationInstance($model, $relationPath);
        if (! $relationInstance || ! ($relationInstance instanceof HasManyThrough || $relationInstance instanceof HasOneThrough)) {
            Log::warning("No se pudo obtener la instancia de la relación Through '$relationPath' para ordenar.");
            $query->orderBy("{$mainTable}.{$mainTableKey}", 'asc');

            return;
        }

        $farModel = $relationInstance->getRelated(); // Modelo lejano
        $farTable = $farModel->getTable();
        $throughModel = $relationInstance->getParent(); // Modelo intermedio (OJO: getParent() puede no ser el intermedio directo en cadenas largas)
        $throughTable = $throughModel->getTable();

        $validSortColumn = $this->validationService->validateSortColumn($column, $farModel);

        // Claves (esto puede ser complejo y depende de la implementación exacta de la relación)
        // Usamos los métodos de la relación para obtener las claves correctas
        $firstKey = $relationInstance->getFirstKeyName(); // Clave en la tabla intermedia que referencia a la tabla lejana
        $foreignKey = $relationInstance->getForeignKeyName(); // Clave en la tabla intermedia que referencia a la tabla principal
        $localKey = $relationInstance->getLocalKeyName(); // Clave en la tabla principal
        $secondLocalKey = $relationInstance->getSecondLocalKeyName(); // Clave en la tabla lejana

        // Construir subconsulta
        $subQuery = $farModel->newQuery()
            ->select("{$farTable}.{$validSortColumn}")
            ->join($throughTable, "{$throughTable}.{$firstKey}", '=', "{$farTable}.{$secondLocalKey}")
            ->whereColumn("{$throughTable}.{$foreignKey}", "{$mainTable}.{$localKey}")
            ->orderBy("{$farTable}.{$validSortColumn}", $sortDirection)
            ->limit(1);

        // Añadir subconsulta y ordenar
        $sortAlias = 'sort_subquery_val';
        $query->addSelect([$sortAlias => $subQuery]);
        $query->orderBy($sortAlias, $sortDirection);
    }

    /**
     * Ordenamiento genérico con subconsulta (Fallback).
     * Menos eficiente, intenta construir una subconsulta correlacionada genérica.
     *
     * @param  Builder  $query  Constructor de consultas.
     * @param  Model  $model  Modelo principal.
     * @param  string  $relationPath  Ruta de la relación.
     * @param  string  $column  Columna en la tabla relacionada por la cual ordenar.
     * @param  string  $sortDirection  Dirección de ordenamiento.
     */
    protected function applySortingWithSubquery(Builder $query, Model $model, string $relationPath, string $column, string $sortDirection): void
    {
        // Esta es una implementación simplificada y puede no funcionar para todas las relaciones complejas.
        // Se basa en la idea de seleccionar el valor de ordenamiento de la relación asociada.
        $mainTable = $model->getTable();
        $mainTableKey = $model->getKeyName();

        $relationInstance = $this->getNestedRelationInstance($model, $relationPath);
        if (! $relationInstance) {
            Log::warning("Fallback: No se pudo obtener la instancia de la relación '$relationPath' para ordenar con subconsulta.");
            $query->orderBy("{$mainTable}.{$mainTableKey}", 'asc');

            return;
        }

        $relatedModel = $relationInstance->getRelated();
        $relatedTable = $relatedModel->getTable();
        $validSortColumn = $this->validationService->validateSortColumn($column, $relatedModel);

        // Intenta construir una subconsulta genérica. La condición WHERE es la parte difícil.
        // Necesitamos relacionar la subconsulta con la fila actual de la consulta principal.
        // Esto varía enormemente según el tipo de relación.
        // Aquí usamos un EXISTS genérico que puede ser ineficiente o incorrecto.
        $subQuery = $relatedModel->newQuery()
            ->select($validSortColumn)
            // WHERE condition needs to link back to the main query's current row.
            // This is highly dependent on the relationship type.
            // Example for a simple HasMany: ->whereColumn($relationInstance->getForeignKeyName(), "{$mainTable}.{$mainTableKey}")
            // Example for a simple BelongsTo: ->whereColumn($relationInstance->getOwnerKeyName(), "{$mainTable}.{$relationInstance->getForeignKeyName()}")
            // A truly generic solution is very complex.
            // Using a placeholder condition that likely needs adjustment:
            ->whereRaw("EXISTS (SELECT 1 FROM {$mainTable} WHERE /* complex condition linking subquery to main query */ 1=0)") // Placeholder - needs real logic
            ->orderBy($validSortColumn, $sortDirection)
            ->limit(1);

        $sortAlias = 'sort_subquery_val';
        $query->addSelect([$sortAlias => $subQuery]);
        $query->orderBy($sortAlias, $sortDirection);

        Log::warning("Usando ordenamiento genérico con subconsulta para '$relationPath.$column'. La eficiencia y corrección dependen de la lógica de la subconsulta.");
    }

    // --- Helper Methods ---

    /**
     * Obtiene la instancia de la relación final en una cadena anidada.
     *
     * @param  Model  $model  Modelo inicial.
     * @param  string  $relationPath  Cadena de relación (ej. 'user.country').
     * @return Relation|null Instancia de la última relación o null si falla.
     */
    protected function getNestedRelationInstance(Model $model, string $relationPath): ?Relation
    {
        $parts = explode('.', $relationPath);
        $currentModel = $model;
        $relationInstance = null;

        foreach ($parts as $part) {
            $relationInstance = $this->validationService->getRelationInstance($currentModel, $part);
            if (! $relationInstance) {
                return null; // Relación inválida en la cadena
            }
            $currentModel = $relationInstance->getRelated(); // Avanzar al siguiente modelo
        }

        return $relationInstance; // Devuelve la instancia de la *última* relación
    }

    /**
     * Construye la condición whereColumn para subconsultas de ordenamiento Has*.
     * Necesita determinar la clave foránea correcta basada en la relación.
     * Esta es una implementación simplificada para relaciones directas.
     *
     * @param  Model  $model  Modelo principal.
     * @param  string  $relationPath  Ruta de la relación.
     * @return string Nombre de la columna foránea en la tabla relacionada.
     */
    protected function buildSubqueryWhereColumnCondition(Model $model, string $relationPath): string
    {
        // Simplificación: Asume relación directa (no anidada)
        $relationInstance = $this->validationService->getRelationInstance($model, $relationPath);
        if ($relationInstance instanceof HasOne || $relationInstance instanceof HasMany) {
            return $relationInstance->getForeignKeyName(); // FK en la tabla relacionada
        }
        if ($relationInstance instanceof MorphOne || $relationInstance instanceof MorphMany) {
            return $relationInstance->getForeignKeyName(); // FK en la tabla relacionada (más la condición de tipo)
        }
        // Añadir lógica para otros tipos o anidados si es necesario
        Log::error("No se pudo determinar la condición whereColumn para la subconsulta de ordenamiento de '$relationPath'.");

        // Devolver un valor que probablemente falle para evitar resultados incorrectos
        return 'invalid_foreign_key_placeholder';
    }
}
