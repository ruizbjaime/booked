<?php

declare(strict_types=1);

return [
    'bath_room_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Tipos de baño',
    ],
    'index' => [
        'title' => 'Tipos de baño',
        'description' => 'Gestiona los tipos de baño disponibles en el sistema.',
        'search_placeholder' => 'Buscar por slug, etiqueta o descripción...',
        'create_action' => 'Nuevo tipo de baño',
        'columns' => [
            'name' => 'Etiqueta',
            'slug' => 'Slug',
            'description' => 'Descripción',
            'sort_order' => 'Orden',
            'created' => 'Creado',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar tipo de baño?',
            'message' => 'Está a punto de eliminar el tipo de baño :bath_room_type. Esta acción lo elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar tipo de baño',
        ],
        'deleted' => 'El tipo de baño :bath_room_type fue eliminado correctamente.',
    ],
    'create' => [
        'title' => 'Crear tipo de baño',
        'description' => 'Agrega un nuevo tipo de baño al sistema.',
        'submit' => 'Crear tipo de baño',
        'created' => 'El tipo de baño :bath_room_type fue creado correctamente.',
        'fields' => [
            'name' => 'Slug',
            'name_help' => 'Formato slug: letras minúsculas, números, guiones y guiones bajos.',
            'name_en' => 'Etiqueta (EN)',
            'name_es' => 'Etiqueta (ES)',
            'description' => 'Descripción',
            'sort_order' => 'Orden',
        ],
    ],
    'show' => [
        'title' => 'Detalle del tipo de baño',
        'description' => 'Revisa la información disponible de este tipo de baño.',
        'placeholder_title' => 'Perfil del tipo de baño',
        'sections' => [
            'details' => 'Datos del tipo de baño',
            'details_description' => 'Información base asociada a este tipo de baño.',
        ],
        'fields' => [
            'name' => 'Slug',
            'name_en' => 'Etiqueta (EN)',
            'name_es' => 'Etiqueta (ES)',
            'description' => 'Descripción',
            'sort_order' => 'Orden',
        ],
        'saved' => [
            'details' => 'Los datos del tipo de baño se actualizaron correctamente.',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar tipo de baño',
                'title' => '¿Eliminar tipo de baño?',
                'message' => 'Estás a punto de eliminar el tipo de baño :bath_room_type. Esta acción lo elimina del sistema de forma permanente.',
                'confirm_label' => 'Eliminar tipo de baño',
                'deleted' => 'El tipo de baño :bath_room_type fue eliminado correctamente.',
            ],
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'bath_room_type_id' => 'ID del tipo de baño',
            'sort_order' => 'Orden',
            'updated' => 'Última actualización',
        ],
        'autosave' => [
            'details' => 'Los cambios de esta sección se guardan automáticamente al salir del campo.',
        ],
    ],
];
