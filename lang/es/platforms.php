<?php

declare(strict_types=1);

return [
    'platform_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Plataformas',
    ],
    'index' => [
        'title' => 'Plataformas',
        'description' => 'Gestiona las plataformas de reserva disponibles en el sistema.',
        'search_placeholder' => 'Buscar por nombre...',
        'create_action' => 'Nueva plataforma',
        'columns' => [
            'active' => 'Activo',
            'localized_name' => 'Nombre',
            'slug' => 'Identificador',
            'color' => 'Color',
            'commission' => 'Comisión %',
            'commission_tax' => 'Impuesto comisión %',
            'sort_order' => 'Orden',
            'created' => 'Creado',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar plataforma?',
            'message' => 'Está a punto de eliminar la plataforma :platform. Esta acción la elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar plataforma',
        ],
        'deleted' => 'La plataforma :platform fue eliminada correctamente.',
        'activated' => 'La plataforma :platform fue activada correctamente.',
        'deactivated' => 'La plataforma :platform fue desactivada correctamente.',
    ],
    'create' => [
        'title' => 'Crear plataforma',
        'description' => 'Agrega una nueva plataforma de reserva al sistema.',
        'submit' => 'Crear plataforma',
        'created' => 'La plataforma :platform fue creada correctamente.',
        'active_help' => 'Hacer esta plataforma disponible para selección de inmediato.',
        'active_enabled' => 'La plataforma inicia activa.',
        'active_disabled' => 'La plataforma inicia inactiva.',
        'fields' => [
            'name_help' => 'Formato slug: letras minúsculas, números, guiones y guiones bajos.',
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'color' => 'Color',
            'color_custom' => 'Color personalizado (hex)',
            'color_custom_option' => 'Personalizado...',
            'sort_order' => 'Orden',
            'commission' => 'Comisión %',
            'commission_tax' => 'Impuesto comisión %',
            'active' => 'Activo',
        ],
    ],
    'show' => [
        'title' => 'Detalle de la plataforma',
        'description' => 'Revisa la información disponible de esta plataforma.',
        'placeholder_title' => 'Perfil de la plataforma',
        'sections' => [
            'details' => 'Datos de la plataforma',
            'details_description' => 'Información base asociada a esta plataforma.',
        ],
        'fields' => [
            'slug' => 'Nombre',
            'name_help' => 'Formato slug: letras minúsculas, números, guiones y guiones bajos.',
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'color' => 'Color',
            'color_custom' => 'Color personalizado (hex)',
            'color_custom_option' => 'Personalizado...',
            'sort_order' => 'Orden',
            'commission' => 'Comisión %',
            'commission_tax' => 'Impuesto comisión %',
            'active' => 'Activo',
        ],
        'saved' => [
            'details' => 'Los datos de la plataforma se actualizaron correctamente.',
            'active' => 'El estado activo se actualizó correctamente.',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar plataforma',
                'title' => '¿Eliminar plataforma?',
                'message' => 'Está a punto de eliminar la plataforma :platform. Esta acción la elimina permanentemente del sistema.',
                'confirm_label' => 'Eliminar plataforma',
                'deleted' => 'La plataforma :platform fue eliminada correctamente.',
            ],
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'platform_id' => 'ID de la plataforma',
            'updated' => 'Última actualización',
        ],
        'status' => [
            'active' => 'Activo',
            'inactive' => 'Inactivo',
        ],
        'autosave' => [
            'details' => 'Los cambios de esta sección se guardan automáticamente al salir del campo.',
        ],
    ],
];
