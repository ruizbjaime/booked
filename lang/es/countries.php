<?php

declare(strict_types=1);

return [
    'country_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Países',
    ],
    'index' => [
        'title' => 'Países',
        'description' => 'Gestiona los países disponibles en el sistema.',
        'search_placeholder' => 'Buscar por nombre o código telefónico...',
        'create_action' => 'Nuevo país',
        'columns' => [
            'active' => 'Activo',
            'name' => 'Nombre',
            'phone_code' => 'Código telefónico',
            'sort_order' => 'Orden',
            'created' => 'Creado',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar país?',
            'message' => 'Está a punto de eliminar el país :country. Esta acción lo elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar país',
        ],
        'confirm_deactivate' => [
            'title' => '¿Desactivar país en su lugar?',
            'message' => 'El país :country no puede eliminarse porque tiene usuarios o propiedades asociadas. Puede desactivarlo en su lugar, lo cual lo eliminará como opción seleccionable.',
            'confirm_label' => 'Desactivar país',
        ],
        'deleted' => 'El país :country fue eliminado correctamente.',
        'activated' => 'El país :country fue activado correctamente.',
        'deactivated' => 'El país :country fue desactivado correctamente.',
    ],
    'create' => [
        'title' => 'Crear país',
        'description' => 'Agrega un nuevo país al sistema.',
        'submit' => 'Crear país',
        'created' => 'El país :country fue creado correctamente.',
        'active_help' => 'Hacer este país disponible para selección de inmediato.',
        'active_enabled' => 'El país inicia activo.',
        'active_disabled' => 'El país inicia inactivo.',
        'fields' => [
            'en_name' => 'Nombre (EN)',
            'es_name' => 'Nombre (ES)',
            'iso_alpha2' => 'ISO Alfa-2',
            'iso_alpha3' => 'ISO Alfa-3',
            'phone_code' => 'Código telefónico',
            'sort_order' => 'Orden',
            'active' => 'Activo',
        ],
    ],
    'show' => [
        'title' => 'Detalle del país',
        'description' => 'Revisa la información disponible de este país.',
        'placeholder_title' => 'Perfil del país',
        'sections' => [
            'details' => 'Datos del país',
            'details_description' => 'Información base asociada a este país.',
        ],
        'fields' => [
            'en_name' => 'Nombre (EN)',
            'es_name' => 'Nombre (ES)',
            'iso_alpha2' => 'ISO Alfa-2',
            'iso_alpha3' => 'ISO Alfa-3',
            'phone_code' => 'Código telefónico',
            'sort_order' => 'Orden',
            'active' => 'Activo',
        ],
        'saved' => [
            'details' => 'Los datos del país se actualizaron correctamente.',
            'active' => 'El estado activo se actualizó correctamente.',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar país',
                'title' => '¿Eliminar país?',
                'message' => 'Está a punto de eliminar el país :country. Esta acción lo elimina permanentemente del sistema.',
                'confirm_label' => 'Eliminar país',
                'deleted' => 'El país :country fue eliminado correctamente.',
            ],
            'deactivate' => [
                'title' => '¿Desactivar país en su lugar?',
                'message' => 'El país :country no puede eliminarse porque tiene usuarios o propiedades asociadas. Puede desactivarlo en su lugar, lo cual lo eliminará como opción seleccionable.',
                'confirm_label' => 'Desactivar país',
                'deactivated' => 'El país :country fue desactivado correctamente.',
            ],
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'country_id' => 'ID del país',
            'associated_users' => 'Usuarios asociados',
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
