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
            'success' => ':count días del calendario fueron regenerados correctamente.',
        ],

        'saved' => 'Configuración guardada correctamente.',
        'rule_label' => ':name (#:id)',

        'stale' => [
            'title' => 'Regeneración del calendario pendiente',
            'description' => 'La configuración de tarifas cambió después de la última generación del calendario. Revisa la vista previa y regenera el calendario cuando estés listo.',
        ],

        'confirm_delete_rule' => [
            'title' => '¿Eliminar regla de tarifa?',
            'message' => 'Vas a eliminar :rule. Esto quitará la regla de las próximas generaciones del calendario.',
            'confirm_label' => 'Eliminar regla',
        ],

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

        'rule_form' => [
            'create_action' => 'Crear regla',
            'create_title' => 'Crear regla de tarifa',
            'create_description' => 'Construye una regla personalizada y previsualiza cómo cambia la proyección del calendario.',
            'edit_title' => 'Editar regla de tarifa',
            'edit_description' => 'Actualiza :rule y revisa el impacto proyectado antes de guardar.',
            'duplicate_title' => 'Duplicar regla de tarifa',
            'duplicate_description' => 'Parte de :rule y guárdala como una nueva regla de tarifa.',
            'submit' => 'Guardar regla',
            'active_help' => 'Las reglas activas se aplican durante la generación del calendario. Las reglas inactivas se guardan pero no se aplican.',
            'active_enabled' => 'Esta regla está activa',
            'active_disabled' => 'Esta regla está inactiva',
            'created' => 'La regla de tarifa :rule fue creada correctamente.',
            'updated' => 'La regla de tarifa :rule fue actualizada correctamente.',
            'duplicated' => 'La regla de tarifa :rule fue duplicada correctamente.',
            'deleted' => 'La regla de tarifa :rule fue eliminada correctamente.',
            'tabs' => [
                'basics' => 'Básicos',
                'conditions' => 'Condiciones',
                'preview' => 'Vista previa',
            ],
            'fields' => [
                'name_help' => 'Slug en minúsculas usado como identificador interno.',
                'priority_help' => 'Los valores más bajos se ejecutan primero. El fallback activo debe quedar al final.',
                'season_mode' => 'Origen de la condición',
                'season' => 'Bloque de temporada',
                'day_of_week' => 'Días de la semana',
                'day_of_week_help' => 'Déjalo vacío para aplicar a todos los días dentro de la temporada seleccionada.',
                'only_last_n_days' => 'Solo últimos N días',
                'exclude_last_n_days' => 'Excluir últimos N días',
                'recurring_dates' => 'Fechas recurrentes',
                'recurring_dates_help' => 'Usa fechas recurrentes MM-DD como 12-07 o 12-31.',
                'month' => 'Mes',
                'day' => 'Día',
                'is_bridge_weekend' => 'Debe ser día de puente',
                'is_first_bridge_day' => 'Solo primer día puente',
                'outside_season' => 'Solo fuera de temporada',
                'not_bridge' => 'Excluir días puente',
            ],
            'season_modes' => [
                'season' => 'Bloque de temporada',
                'dates' => 'Fechas recurrentes fijas',
            ],
            'actions' => [
                'add_date' => 'Agregar fecha',
            ],
            'empty_recurring_dates' => 'Aún no hay fechas recurrentes agregadas.',
            'fallback_title' => 'Regla fallback',
            'fallback_description' => 'La regla económica por defecto siempre actúa como el último fallback activo. Sus condiciones se fijan automáticamente.',
        ],

        'preview' => [
            'title' => 'Vista previa de impacto',
            'description' => 'Compara la proyección actual del calendario con la regla en borrador para el año actual y el siguiente.',
            'run' => 'Ejecutar vista previa',
            'affected_nights' => 'Noches afectadas',
            'range' => 'Rango de la vista previa',
            'transitions' => 'Transiciones de categoría',
            'sample_dates' => 'Fechas de muestra',
            'no_transitions' => 'No se detectaron cambios de categoría para esta regla.',
            'no_sample_dates' => 'No hay fechas de muestra disponibles.',
            'unassigned' => 'Sin asignar',
            'no_changes_warning' => 'Este borrador no cambia ninguna noche generada dentro del rango de la vista previa.',
            'priority_overlap_warning' => 'Reglas activas con mayor prioridad pueden estar absorbiendo esta regla antes de que aplique.',
        ],

        'rule_summaries' => [
            'specific_dates' => 'Fechas específicas: :dates',
            'season' => 'Temporada: :season',
            'days' => 'Días: :days',
            'only_last_days' => 'Solo últimos :count días',
            'exclude_last_days' => 'Excluir últimos :count días',
            'bridge_weekend' => 'Puente festivo',
            'first_bridge_day' => 'Solo primer día puente',
            'outside_season' => 'Fuera de temporada',
            'exclude_bridge_days' => 'Excluir días puente',
            'fallback' => 'Fallback para todos los días restantes',
        ],

        'validation' => [
            'season_or_dates' => 'Elige un bloque de temporada o fechas recurrentes, pero no ambos.',
            'last_day_filters_conflict' => 'Solo un filtro de últimos días puede estar activo al mismo tiempo.',
            'recurring_dates_required' => 'Agrega al menos una fecha recurrente para esta regla.',
            'unique_active_priority' => 'Otra regla de tarifa activa ya usa esta prioridad.',
            'single_active_fallback' => 'Debe existir exactamente una regla económica por defecto activa.',
            'fallback_must_be_last' => 'La regla económica por defecto activa debe tener el número de prioridad más alto.',
            'cannot_delete_active_fallback' => 'La regla económica por defecto activa no puede eliminarse mientras siga siendo el fallback.',
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

    'days_of_week' => [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ],

    'days_of_week_short' => [
        'monday' => 'Lun',
        'tuesday' => 'Mar',
        'wednesday' => 'Mié',
        'thursday' => 'Jue',
        'friday' => 'Vie',
        'saturday' => 'Sáb',
        'sunday' => 'Dom',
    ],
];
