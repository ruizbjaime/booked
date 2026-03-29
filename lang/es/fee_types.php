<?php

declare(strict_types=1);

return [
    'fee_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Tipos de tarifa',
    ],
    'index' => [
        'title' => 'Tipos de tarifa',
        'description' => 'Gestiona los tipos de tarifa disponibles en el sistema.',
        'search_placeholder' => 'Buscar por slug o etiqueta...',
        'create_action' => 'Nuevo tipo de tarifa',
        'columns' => [
            'active' => 'Activo',
            'name' => 'Nombre',
            'slug' => 'Etiqueta',
            'order' => 'Orden',
            'created' => 'Creado',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar tipo de tarifa?',
            'message' => 'Está a punto de eliminar el tipo de tarifa :fee_type. Esta acción lo elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar tipo de tarifa',
        ],
        'deleted' => 'El tipo de tarifa :fee_type fue eliminado correctamente.',
        'activated' => 'El tipo de tarifa :fee_type fue activado correctamente.',
        'deactivated' => 'El tipo de tarifa :fee_type fue desactivado correctamente.',
    ],
    'create' => [
        'title' => 'Crear tipo de tarifa',
        'description' => 'Agrega un nuevo tipo de tarifa al sistema.',
        'submit' => 'Crear tipo de tarifa',
        'created' => 'El tipo de tarifa :fee_type fue creado correctamente.',
        'fields' => [
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'order' => 'Orden',
        ],
    ],
    'show' => [
        'title' => 'Detalle del tipo de tarifa',
        'description' => 'Revisa la información disponible de este tipo de tarifa.',
        'placeholder_title' => 'Perfil del tipo de tarifa',
        'sections' => [
            'details' => 'Datos del tipo de tarifa',
            'details_description' => 'Información base asociada a este tipo de tarifa.',
            'charge_bases' => 'Bases de cobro permitidas',
            'charge_bases_description' => 'Active o desactive qué bases de cobro están permitidas para este tipo de tarifa.',
        ],
        'fields' => [
            'slug' => 'Slug',
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'order' => 'Orden',
        ],
        'saved' => [
            'details' => 'Los datos del tipo de tarifa se actualizaron correctamente.',
            'charge_bases' => 'Las bases de cobro permitidas se actualizaron correctamente.',
        ],
        'charge_bases' => [
            'empty' => 'Aún no hay bases de cobro asignadas.',
            'save' => 'Guardar bases de cobro',
            'managed_in_catalog' => 'La metadata compartida de cada base de cobro se gestiona desde el catálogo de bases de cobro.',
            'inactive_badge' => 'Catálogo inactivo',
            'order_hint' => 'Arrastra para reordenar. El primer elemento es el predeterminado.',
            'default_badge' => 'Predeterminada',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar tipo de tarifa',
                'title' => '¿Eliminar tipo de tarifa?',
                'message' => 'Está a punto de eliminar el tipo de tarifa :fee_type. Esta acción lo elimina permanentemente del sistema.',
                'confirm_label' => 'Eliminar tipo de tarifa',
                'deleted' => 'El tipo de tarifa :fee_type fue eliminado correctamente.',
            ],
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'fee_type_id' => 'ID del tipo de tarifa',
            'order' => 'Orden',
            'updated' => 'Última actualización',
        ],
        'autosave' => [
            'details' => 'Los cambios de esta sección se guardan automáticamente al salir del campo.',
        ],
    ],
    'validation' => [
        'duplicate_charge_bases' => 'No se permiten bases de cobro duplicadas.',
    ],
];
