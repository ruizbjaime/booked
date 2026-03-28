<?php

declare(strict_types=1);

return [
    'property_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Propiedades',
    ],
    'index' => [
        'title' => 'Propiedades',
        'description' => 'Revisa las propiedades administradas por anfitriones en el sistema.',
        'search_placeholder' => 'Buscar por propiedad, ciudad o país...',
        'create_action' => 'Nueva propiedad',
        'columns' => [
            'name' => 'Propiedad',
            'slug' => 'Slug',
            'city' => 'Ciudad',
            'address' => 'Dirección',
            'country' => 'País',
            'active' => 'Estado',
            'created' => 'Creado',
        ],
        'status' => [
            'active' => 'Activo',
            'inactive' => 'Inactivo',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar propiedad?',
            'message' => 'Está a punto de eliminar la propiedad :property. Esta acción la elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar propiedad',
        ],
        'deleted' => 'La propiedad :property fue eliminada correctamente.',
    ],
    'create' => [
        'title' => 'Crear propiedad',
        'description' => 'Agrega una nueva propiedad administrada por un anfitrión.',
        'submit' => 'Crear propiedad',
        'created' => 'La propiedad :property fue creada correctamente.',
        'active_help' => 'Hacer esta propiedad disponible de inmediato para uso operativo.',
        'active_enabled' => 'La propiedad inicia activa.',
        'active_disabled' => 'La propiedad inicia inactiva.',
        'fields' => [
            'name' => 'Nombre',
            'name_help' => 'El slug se genera automáticamente desde el nombre usando guiones bajos entre palabras.',
            'city' => 'Ciudad',
            'address' => 'Dirección',
            'country' => 'País',
            'active' => 'Activo',
        ],
    ],
    'show' => [
        'title' => 'Detalle de la propiedad',
        'description' => 'Revisa la información disponible de esta propiedad.',
        'placeholder_title' => 'Perfil de la propiedad',
        'sections' => [
            'details' => 'Datos de la propiedad',
            'details_description' => 'Información base de ubicación e identificación de esta propiedad.',
        ],
        'fields' => [
            'slug' => 'Slug',
            'name' => 'Nombre',
            'city' => 'Ciudad',
            'address' => 'Dirección',
            'country' => 'País',
            'active' => 'Estado',
        ],
        'saved' => [
            'details' => 'Los datos de la propiedad se actualizaron correctamente.',
            'active' => 'El estado activo se actualizó correctamente.',
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'property_id' => 'ID de la propiedad',
            'updated' => 'Última actualización',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar propiedad',
                'title' => '¿Eliminar propiedad?',
                'message' => 'Está a punto de eliminar la propiedad :property. Esta acción la elimina permanentemente del sistema.',
                'confirm_label' => 'Eliminar propiedad',
                'deleted' => 'La propiedad :property fue eliminada correctamente.',
            ],
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
