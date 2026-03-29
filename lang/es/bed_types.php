<?php

declare(strict_types=1);

return [
    'bed_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Tipos de cama',
    ],
    'index' => [
        'title' => 'Tipos de cama',
        'description' => 'Gestiona los tipos de cama disponibles en el sistema.',
        'search_placeholder' => 'Buscar por slug o etiqueta...',
        'create_action' => 'Nuevo tipo de cama',
        'columns' => [
            'active' => 'Activo',
            'name' => 'Nombre',
            'slug' => 'Etiqueta',
            'bed_capacity' => 'Capacidad de la cama',
            'sort_order' => 'Orden',
            'created' => 'Creado',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar tipo de cama?',
            'message' => 'Está a punto de eliminar el tipo de cama :bed_type. Esta acción lo elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar tipo de cama',
        ],
        'deleted' => 'El tipo de cama :bed_type fue eliminado correctamente.',
        'activated' => 'El tipo de cama :bed_type fue activado correctamente.',
        'deactivated' => 'El tipo de cama :bed_type fue desactivado correctamente.',
    ],
    'create' => [
        'title' => 'Crear tipo de cama',
        'description' => 'Agrega un nuevo tipo de cama al sistema.',
        'submit' => 'Crear tipo de cama',
        'created' => 'El tipo de cama :bed_type fue creado correctamente.',
        'fields' => [
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'bed_capacity' => 'Capacidad de la cama',
            'sort_order' => 'Orden',
        ],
    ],
    'show' => [
        'title' => 'Detalle del tipo de cama',
        'description' => 'Revisa la información disponible de este tipo de cama.',
        'placeholder_title' => 'Perfil del tipo de cama',
        'sections' => [
            'details' => 'Datos del tipo de cama',
            'details_description' => 'Información base asociada a este tipo de cama.',
        ],
        'fields' => [
            'slug' => 'Slug',
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'bed_capacity' => 'Capacidad de la cama',
            'sort_order' => 'Orden',
        ],
        'saved' => [
            'details' => 'Los datos del tipo de cama se actualizaron correctamente.',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar tipo de cama',
                'title' => '¿Eliminar tipo de cama?',
                'message' => 'Está a punto de eliminar el tipo de cama :bed_type. Esta acción lo elimina permanentemente del sistema.',
                'confirm_label' => 'Eliminar tipo de cama',
                'deleted' => 'El tipo de cama :bed_type fue eliminado correctamente.',
            ],
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'bed_type_id' => 'ID del tipo de cama',
            'bed_capacity' => 'Capacidad de la cama',
            'updated' => 'Última actualización',
        ],
        'autosave' => [
            'details' => 'Los cambios de esta sección se guardan automáticamente al salir del campo.',
        ],
    ],
];
