<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DataTablePresentationService
{
    protected DataTableValidationService $validationService;

    public function __construct(DataTableValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Obtiene el valor formateado para mostrar en una celda de la tabla.
     * Maneja relaciones anidadas y formateo básico (fechas Carbon).
     *
     * @param  Model  $model  Instancia del modelo para la fila actual.
     * @param  string  $column  Nombre de la columna/relación (ej. 'name', 'country.name').
     * @return mixed Valor formateado para mostrar.
     */
    public function getFormattedValue(Model $model, string $column): mixed
    {
        // Si es una relación anidada (contiene '.')
        if (Str::contains($column, '.')) {
            // Extraer la ruta de la relación y la propiedad final
            $relationParts = $this->validationService->getRelationParts($column);
            $relationPath = implode('.', $relationParts); // Ej: 'user.country'
            $property = last(explode('.', $column)); // Ej: 'name'

            // Obtener el valor de la relación usando data_get para manejar anidamiento
            // data_get cargará automáticamente las relaciones si no están ya cargadas (pero es mejor eager load)
            $relationValue = data_get($model, $relationPath);

            // Determinar cómo mostrar el valor basado en el tipo de relación/valor
            return match (true) {
                // Si el valor es una colección (ej. HasMany, ManyToMany)
                $relationValue instanceof Collection => $relationValue
                    ->pluck($property) // Obtener la propiedad de cada item de la colección
                    ->filter() // Quitar valores vacíos/nulos
                    ->implode(', '), // Unir con comas

                // Si el valor es un objeto (modelo relacionado, ej. BelongsTo, HasOne)
                is_object($relationValue) => $this->computeModelPropertyValue($relationValue, $property),

                // Si la relación es nula o no se encontró
                default => null // O podrías devolver 'N/A', null, etc.
            };
        }

        // Sí es una propiedad directa del modelo principal
        return $this->computeModelPropertyValue($model, $column);
    }

    /**
     * Calcula y formatea el valor de una propiedad de un modelo.
     * Maneja accessors y formatea instancias de Carbon.
     *
     * @param  Model  $model  Instancia del modelo.
     * @param  string  $property  Nombre de la propiedad/accessor.
     * @return mixed Valor calculado y/o formateado.
     */
    protected function computeModelPropertyValue(Model $model, string $property): mixed
    {
        // Usar getAttribute para invocar accessors si existen
        $value = $model->getAttribute($property);

        // Formatear fechas Carbon
        if ($value instanceof Carbon) {
            // Usar el locale de la aplicación para el formato localizado
            $locale = config('app.locale', 'en'); // Fallback a 'en'
            try {
                // 'LL' es un formato común (ej. "19 de abril de 2025")
                // Puedes cambiarlo a 'L' (04/19/2025) u otro formato de Carbon/ISO
                return $value->locale($locale)->isoFormat('LL');
            } catch (Exception) {
                // En caso de problemas con el locale o formato
                return $value->toDateString(); // Fallback a Y-m-d
            }
        }

        // Devolver otros tipos de valor tal cual
        return empty($value) ? '-' : $value;
    }
}
