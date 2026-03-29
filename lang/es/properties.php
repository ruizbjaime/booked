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
            'capacity' => 'Capacidad',
            'capacity_description' => 'Configuración de capacidad de huéspedes para esta propiedad.',
        ],
        'fields' => [
            'slug' => 'Slug',
            'name' => 'Nombre',
            'city' => 'Ciudad',
            'address' => 'Dirección',
            'country' => 'País',
            'active' => 'Estado',
            'base_capacity' => 'Capacidad base',
            'max_capacity' => 'Capacidad máxima',
        ],
        'avatar_delete_label' => 'Eliminar foto de la propiedad',
        'avatar_add_label' => 'Agregar foto de la propiedad',
        'saved' => [
            'details' => 'Los datos de la propiedad se actualizaron correctamente.',
            'active' => 'El estado activo se actualizó correctamente.',
            'avatar' => 'La foto de la propiedad fue actualizada correctamente.',
            'avatar_deleted' => 'La foto de la propiedad fue eliminada.',
            'capacity' => 'La configuración de capacidad se actualizó correctamente.',
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
            'capacity' => 'Los cambios de esta sección se guardan automáticamente al salir del campo.',
        ],
    ],
    'validation' => [
        'base_capacity_exceeds_max' => 'La capacidad base no debe exceder la capacidad máxima.',
        'max_capacity_below_base' => 'La capacidad máxima no debe ser menor que la capacidad base.',
    ],
];
