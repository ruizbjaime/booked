<?php

namespace App\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DataTableValidationService
{
    /** @var array Cache de columnas de tabla */
    protected static array $cachedTableColumns = [];

    /** @var array Cache de relaciones */
    protected array $cachedRelations = [];

    /**
     * Valida y crea una instancia del modelo.
     *
     * @param  string  $modelClass  Nombre de la clase del modelo.
     * @return Model Instancia del modelo validado.
     *
     * @throws InvalidArgumentException Si la clase no es un modelo Eloquent válido.
     */
    public function validateModel(string $modelClass): Model
    {
        try {
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                throw new InvalidArgumentException(
                    "Clase inválida. '$modelClass' debe ser un modelo Eloquent válido."
                );
            }

            return new $modelClass;
        } catch (InvalidArgumentException $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    /**
     * Valida la configuración de columnas, filtrando las no válidas.
     *
     * @param  Model  $model  Instancia del modelo base.
     * @param  array  $columnMap  Configuración de columnas a validar.
     * @return array Configuración de columnas validada.
     */
    public function validateColumnMap(Model $model, array $columnMap): array
    {
        return collect($columnMap)
            ->filter(fn ($configData, string $field) => $this->validateModelRelationOrProperty($model, $field))
            ->all();
    }

    /**
     * Valida si un campo es una propiedad o relación válida del modelo.
     *
     * @param  Model  $model  Instancia del modelo.
     * @param  string  $field  Nombre del campo o relación (puede ser anidado, ej. 'relation.property').
     * @return bool True si es válido, false en caso contrario.
     */
    public function validateModelRelationOrProperty(Model $model, string $field): bool
    {
        if (Str::contains($field, '.')) {
            $relations = $this->getRelationParts($field);

            return $this->validateNestedRelations($model, $relations);
        }

        $tableColumns = $this->getTableColumns($model);

        return method_exists($model, $field)
            || $model->hasGetMutator($field)
            || in_array($field, $model->getFillable())
            || in_array($field, $tableColumns);
    }

    /**
     * Valida si las relaciones anidadas son válidas.
     *
     * @param  Model  $model  Modelo inicial.
     * @param  array  $relations  Array con los nombres de las relaciones anidadas.
     * @return bool True si la cadena de relaciones es válida, false si no.
     */
    protected function validateNestedRelations(Model $model, array $relations): bool
    {
        $currentModel = $model;

        foreach ($relations as $relationName) {
            $relation = $this->getRelationInstance($currentModel, $relationName);

            if (! $relation) {
                return false; // La relación no existe o no es válida
            }

            $currentModel = $relation->getRelated(); // Avanzar al siguiente modelo en la cadena
        }

        return true; // Todas las relaciones en la cadena son válidas
    }

    /**
     * Obtiene una instancia de relación o null si no es válida (con caché).
     *
     * @param  Model  $model  Modelo que define la relación.
     * @param  string  $relationName  Nombre de la relación.
     * @return Relation|null Instancia de la relación o null.
     */
    public function getRelationInstance(Model $model, string $relationName): ?Relation
    {
        $cacheKey = get_class($model).'::'.$relationName;

        // Devolver desde caché si existe
        if (isset($this->cachedRelations[$cacheKey])) {
            return $this->cachedRelations[$cacheKey]['relation'];
        }

        // Validar si el método existe en el modelo
        if (! method_exists($model, $relationName)) {
            return null;
        }

        try {
            // Intentar obtener la instancia de la relación
            $relation = $model->$relationName();

            // Asegurarse de que sea una instancia de Relation
            if (! $relation instanceof Relation) {
                return null;
            }

            // Guardar en caché la relación y el modelo relacionado
            $this->cachedRelations[$cacheKey] = [
                'relation' => $relation,
                'related' => $relation->getRelated(),
            ];

            return $relation;
        } catch (Exception $e) {
            // Registrar error si falla la obtención de la relación
            Log::error("Error obteniendo relación $relationName: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Obtiene las columnas de una tabla (con caché estático).
     *
     * @param  Model  $model  Modelo del cual obtener las columnas de la tabla.
     * @return array Lista de nombres de columnas.
     */
    public function getTableColumns(Model $model): array
    {
        $table = $model->getTable();

        // Usar caché estático para evitar consultas repetidas al esquema
        if (! isset(self::$cachedTableColumns[$table])) {
            self::$cachedTableColumns[$table] = Schema::getColumnListing($table);
        }

        return self::$cachedTableColumns[$table];
    }

    /**
     * Verifica la existencia de una columna y provee un fallback si es necesario.
     *
     * @param  string  $column  Nombre de la columna a validar.
     * @param  Model  $model  Modelo asociado a la tabla de la columna.
     * @return string Nombre de la columna validada o la clave primaria como fallback.
     */
    public function validateSortColumn(string $column, Model $model): string
    {
        $tableColumns = $this->getTableColumns($model);

        if (! in_array($column, $tableColumns)) {
            Log::warning("Columna de ordenamiento '$column' no encontrada en tabla {$model->getTable()}. Usando clave primaria '{$model->getKeyName()}'.");

            return $model->getKeyName(); // Fallback a la clave primaria
        }

        return $column;
    }

    /**
     * Extrae las partes de relación de un campo compuesto (ej. 'relation1.relation2.property').
     *
     * @param  string  $field  Campo compuesto.
     * @return array Array con los nombres de las relaciones (ej. ['relation1', 'relation2']).
     */
    public function getRelationParts(string $field): array
    {
        if (! Str::contains($field, '.')) {
            return []; // No es un campo de relación anidada
        }

        $parts = explode('.', $field);
        array_pop($parts); // Quitar la propiedad final

        return $parts;
    }
}
