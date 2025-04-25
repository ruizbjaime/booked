<?php

namespace App\Services;

class DataTableConfigService
{
    /**
     * Extrae campos configurados con una propiedad específica.
     *
     * @param  array  $columnMap  Configuración de columnas.
     * @param  string  $key  Clave de configuración a buscar (ej. 'searchable', 'sortable').
     * @return array Lista de campos que tienen la clave configurada como true.
     */
    public function extractConfiguredFields(array $columnMap, string $key): array
    {
        return collect($columnMap)
            ->filter(fn ($value) => is_array($value) && ($value[$key] ?? false) === true)
            ->keys()
            ->all();
    }

    /**
     * Obtiene las columnas de búsqueda configuradas para un campo de relación.
     *
     * @param  array  $columnMap  Configuración de columnas.
     * @param  string  $field  Campo de relación (ej. 'relation.property').
     * @param  string  $defaultColumn  Columna por defecto si no hay configuración específica.
     * @return array Lista de columnas físicas para buscar.
     */
    public function getSearchColumnsForRelation(array $columnMap, string $field, string $defaultColumn): array
    {
        return $columnMap[$field]['search_columns'] ?? [$defaultColumn];
    }

    /**
     * Obtiene la columna física para ordenar (considerando accessors y configuración).
     *
     * @param  array  $columnMap  Configuración de columnas.
     * @param  string  $fullFieldName  Nombre completo del campo (ej. 'relation.property').
     * @param  string  $defaultColumn  Columna por defecto si no hay configuración específica.
     * @return string Columna física para ordenar.
     */
    public function getSortingColumn(array $columnMap, string $fullFieldName, string $defaultColumn): string
    {
        // Verificar configuración específica de sort_column
        if (isset($columnMap[$fullFieldName]['sort_column'])) {
            $sortColumns = $columnMap[$fullFieldName]['sort_column'];

            // Sí hay múltiples columnas, elegir según locale
            if (is_array($sortColumns)) {
                $locale = app()->getLocale();

                return match ($locale) {
                    'es' => $sortColumns[1] ?? $sortColumns[0],
                    default => $sortColumns[0]
                };
            }

            return $sortColumns;
        }

        return $defaultColumn;
    }
}
