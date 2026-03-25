<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Calendario',
        'settings' => 'Configuración del calendario',
    ],

    'index' => [
        'title' => 'Calendario de tarifas',
        'description' => 'Calendario anual que muestra la temperatura de demanda y las categorías de tarifa para cada noche.',
        'year_label' => 'Año',
        'previous_year' => 'Año anterior',
        'next_year' => 'Año siguiente',
        'no_data' => 'No hay datos de calendario generados para este año.',
        'generate_prompt' => 'Ejecuta el generador de calendario para poblar este año.',

        'legend' => [
            'title' => 'Categorías de tarifa',
        ],

        'stats' => [
            'title' => 'Resumen del año',
            'total_holidays' => 'Festivos',
            'bridge_weekends' => 'Puentes',
            'cat_1' => 'Noches premium',
            'cat_2' => 'Noches altas',
            'cat_3' => 'Noches fin de semana estándar',
            'cat_4' => 'Noches económicas',
        ],

        'day_detail' => [
            'holiday' => 'Festivo',
            'season' => 'Temporada',
            'impact' => 'Puntaje de impacto',
            'pricing' => 'Categoría de tarifa',
            'bridge' => 'Día puente',
            'quincena' => 'Cerca de quincena',
            'original_date' => 'Fecha original',
            'observed_date' => 'Fecha observada',
            'moved' => 'Trasladado desde :date',
        ],

        'months' => [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ],

        'weekdays' => [
            'mon' => 'Lu',
            'tue' => 'Ma',
            'wed' => 'Mi',
            'thu' => 'Ju',
            'fri' => 'Vi',
            'sat' => 'Sá',
            'sun' => 'Do',
        ],
    ],

    'settings' => [
        'title' => 'Configuración del calendario',
        'description' => 'Configura festivos, temporadas, categorías de tarifa y reglas utilizadas para generar el calendario.',

        'sections' => [
            'holidays' => 'Definiciones de festivos',
            'holidays_description' => 'Festivos colombianos y sus pesos de impacto.',
            'seasons' => 'Bloques de temporada',
            'seasons_description' => 'Períodos de alta demanda y sus estrategias de cálculo.',
            'categories' => 'Categorías de tarifa',
            'categories_description' => 'Niveles de tarifa con colores y multiplicadores.',
            'rules' => 'Reglas de tarifa',
            'rules_description' => 'Reglas que asignan días a categorías de tarifa según condiciones.',
        ],

        'regenerate' => [
            'button' => 'Regenerar calendario',
            'title' => '¿Regenerar calendario?',
            'message' => 'Esto recalculará todos los días del calendario para el año actual y el siguiente usando la configuración actual. Los datos existentes serán actualizados.',
            'confirm_label' => 'Regenerar',
            'success' => ':count días del calendario fueron regenerados exitosamente.',
        ],

        'saved' => 'Configuración guardada exitosamente.',

        'fields' => [
            'name' => 'Slug',
            'en_name' => 'Nombre (EN)',
            'es_name' => 'Nombre (ES)',
            'group' => 'Grupo',
            'month' => 'Mes',
            'day' => 'Día',
            'easter_offset' => 'Desplazamiento de Pascua',
            'moves_to_monday' => 'Se traslada al lunes',
            'base_impact_weights' => 'Pesos de impacto',
            'special_overrides' => 'Excepciones especiales',
            'calculation_strategy' => 'Estrategia de cálculo',
            'priority' => 'Prioridad',
            'level' => 'Nivel',
            'color' => 'Color',
            'multiplier' => 'Multiplicador',
            'rule_type' => 'Tipo de regla',
            'conditions' => 'Condiciones',
            'en_description' => 'Descripción (EN)',
            'es_description' => 'Descripción (ES)',
            'sort_order' => 'Orden',
            'is_active' => 'Activo',
            'pricing_category' => 'Categoría de tarifa',
        ],
    ],

    'show' => [
        'title' => 'Detalle del día',
        'description' => 'Análisis completo para :date.',
        'back' => 'Volver al calendario',

        'sections' => [
            'general' => 'General',
            'holiday' => 'Información del festivo',
            'season' => 'Información de temporada',
            'pricing' => 'Información de tarifa',
        ],

        'fields' => [
            'date' => 'Fecha',
            'day_of_week' => 'Día de la semana',
            'is_holiday' => 'Festivo',
            'holiday_name' => 'Nombre del festivo',
            'holiday_group' => 'Grupo del festivo',
            'holiday_impact' => 'Puntaje de impacto',
            'original_date' => 'Fecha original',
            'observed_date' => 'Fecha observada',
            'is_bridge_day' => 'Día puente',
            'season' => 'Temporada',
            'pricing_category' => 'Categoría de tarifa',
            'pricing_level' => 'Nivel de tarifa',
            'is_quincena' => 'Cerca de quincena',
            'notes' => 'Notas',
        ],
    ],

    'command' => [
        'generating' => 'Generando días del calendario de :from a :to...',
        'generated' => ':count días del calendario generados.',
        'error_from_after_to' => '--from debe ser anterior a --to.',
        'error_invalid_year' => 'El año debe estar entre 2000 y 2100.',
    ],

    'holiday_groups' => [
        'fixed' => 'Fijo',
        'emiliani' => 'Emiliani (trasladable)',
        'easter_based' => 'Basado en Pascua',
    ],

    'season_strategies' => [
        'holy_week' => 'Semana Santa',
        'year_end' => 'Fin de Año',
        'october_recess' => 'Receso de Octubre',
        'fixed_range' => 'Rango Fijo',
    ],

    'rule_types' => [
        'season_days' => 'Días de temporada',
        'holiday_bridge' => 'Puente festivo',
        'normal_weekend' => 'Fin de semana normal',
        'economy_default' => 'Economía por defecto',
    ],
];
