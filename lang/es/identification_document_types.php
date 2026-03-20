<?php

declare(strict_types=1);

return [
    'doc_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Tipos de documento',
    ],
    'index' => [
        'title' => 'Tipos de documento',
        'description' => 'Gestiona los tipos de documento de identificación disponibles en el sistema.',
        'search_placeholder' => 'Buscar por código o nombre...',
        'create_action' => 'Nuevo tipo de documento',
        'columns' => [
            'active' => 'Activo',
            'name' => 'Nombre',
            'code' => 'Código',
            'sort_order' => 'Orden',
            'created' => 'Creado',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar tipo de documento?',
            'message' => 'Está a punto de eliminar el tipo de documento :doc_type. Esta acción lo elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar tipo de documento',
        ],
        'confirm_deactivate' => [
            'title' => '¿Desactivar tipo de documento?',
            'message' => 'El tipo de documento :doc_type tiene usuarios asociados y no puede ser eliminado. Se desactivará y dejará de aparecer como opción seleccionable.',
            'confirm_label' => 'Desactivar tipo de documento',
        ],
        'deleted' => 'El tipo de documento :doc_type fue eliminado correctamente.',
        'deactivated_instead' => 'El tipo de documento :doc_type fue desactivado porque tiene usuarios asociados.',
        'activated' => 'El tipo de documento :doc_type fue activado correctamente.',
        'deactivated' => 'El tipo de documento :doc_type fue desactivado correctamente.',
    ],
    'create' => [
        'title' => 'Crear tipo de documento',
        'description' => 'Agrega un nuevo tipo de documento de identificación al sistema.',
        'submit' => 'Crear tipo de documento',
        'created' => 'El tipo de documento :doc_type fue creado correctamente.',
        'active_help' => 'Hacer este tipo de documento disponible para selección de inmediato.',
        'active_enabled' => 'El tipo de documento inicia activo.',
        'active_disabled' => 'El tipo de documento inicia inactivo.',
        'fields' => [
            'code' => 'Código',
            'en_name' => 'Nombre (EN)',
            'es_name' => 'Nombre (ES)',
            'sort_order' => 'Orden',
            'active' => 'Activo',
        ],
    ],
    'show' => [
        'title' => 'Detalle del tipo de documento',
        'description' => 'Revisa la información disponible de este tipo de documento.',
        'placeholder_title' => 'Perfil del tipo de documento',
        'sections' => [
            'details' => 'Datos del tipo de documento',
            'details_description' => 'Información base asociada a este tipo de documento.',
        ],
        'fields' => [
            'code' => 'Código',
            'en_name' => 'Nombre (EN)',
            'es_name' => 'Nombre (ES)',
            'sort_order' => 'Orden',
            'active' => 'Activo',
        ],
        'saved' => [
            'details' => 'Los datos del tipo de documento se actualizaron correctamente.',
            'active' => 'El estado activo se actualizó correctamente.',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar tipo de documento',
                'title' => '¿Eliminar tipo de documento?',
                'message' => 'Estás a punto de eliminar el tipo de documento :doc_type. Esta acción lo elimina del sistema de forma permanente.',
                'confirm_label' => 'Eliminar tipo de documento',
                'deleted' => 'El tipo de documento :doc_type fue eliminado correctamente.',
            ],
            'deactivate' => [
                'title' => '¿Desactivar tipo de documento?',
                'message' => 'El tipo de documento :doc_type tiene usuarios asociados y no puede ser eliminado. Se desactivará y dejará de aparecer como opción seleccionable.',
                'confirm_label' => 'Desactivar tipo de documento',
                'deactivated' => 'El tipo de documento :doc_type fue desactivado porque tiene usuarios asociados.',
            ],
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'doc_type_id' => 'ID del tipo de documento',
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
