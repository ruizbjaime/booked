<?php

declare(strict_types=1);

return [
    'charge_basis_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Bases de cobro',
    ],
    'index' => [
        'title' => 'Bases de cobro',
        'description' => 'Gestiona las bases de cobro disponibles y su metadata compartida.',
        'search_placeholder' => 'Buscar por slug o etiqueta...',
        'create_action' => 'Nueva base de cobro',
        'columns' => [
            'active' => 'Activa',
            'name' => 'Etiqueta',
            'slug' => 'Slug',
            'order' => 'Orden',
            'created' => 'Creada',
        ],
        'confirm_delete' => [
            'title' => '¿Eliminar base de cobro?',
            'message' => 'Está a punto de eliminar la base de cobro :charge_basis. Esta acción la elimina permanentemente del sistema.',
            'confirm_label' => 'Eliminar base de cobro',
        ],
        'deleted' => 'La base de cobro :charge_basis fue eliminada correctamente.',
        'activated' => 'La base de cobro :charge_basis fue activada correctamente.',
        'deactivated' => 'La base de cobro :charge_basis fue desactivada correctamente.',
    ],
    'create' => [
        'title' => 'Crear base de cobro',
        'description' => 'Agrega una nueva base de cobro al catálogo.',
        'submit' => 'Crear base de cobro',
        'created' => 'La base de cobro :charge_basis fue creada correctamente.',
        'active_enabled' => 'Esta base de cobro inicia activa.',
        'active_disabled' => 'Esta base de cobro inicia inactiva.',
        'fields' => [
            'name' => 'Slug',
            'name_help' => 'Formato slug: letras minúsculas, números y guiones bajos.',
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'description' => 'Descripción',
            'order' => 'Orden',
            'is_active' => 'Activa',
            'requires_quantity' => 'Requiere cantidad',
            'quantity_subject' => 'Sujeto de cantidad',
        ],
    ],
    'show' => [
        'title' => 'Detalle de la base de cobro',
        'description' => 'Revisa la información disponible de esta base de cobro.',
        'placeholder_title' => 'Perfil de la base de cobro',
        'sections' => [
            'details' => 'Datos de la base de cobro',
            'details_description' => 'Metadata compartida usada por los tipos de tarifa que permiten esta base de cobro.',
        ],
        'fields' => [
            'name' => 'Slug',
            'en_name' => 'Etiqueta (EN)',
            'es_name' => 'Etiqueta (ES)',
            'description' => 'Descripción',
            'order' => 'Orden',
            'is_active' => 'Activa',
            'requires_quantity' => 'Requiere cantidad',
            'quantity_subject' => 'Sujeto de cantidad',
        ],
        'saved' => [
            'details' => 'Los datos de la base de cobro se actualizaron correctamente.',
        ],
        'quick_actions' => [
            'title' => 'Acciones rápidas',
            'delete' => [
                'action' => 'Eliminar base de cobro',
                'title' => '¿Eliminar base de cobro?',
                'message' => 'Está a punto de eliminar la base de cobro :charge_basis. Esta acción la elimina permanentemente del sistema.',
                'confirm_label' => 'Eliminar base de cobro',
                'deleted' => 'La base de cobro :charge_basis fue eliminada correctamente.',
            ],
        ],
        'stats' => [
            'title' => 'Estadísticas',
            'charge_basis_id' => 'ID de la base de cobro',
            'order' => 'Orden',
            'updated' => 'Última actualización',
        ],
        'status' => [
            'active' => 'Activa',
            'inactive' => 'Inactiva',
            'quantity_required' => 'Cantidad requerida',
            'quantity_not_required' => 'Cantidad no requerida',
            'not_applicable' => 'No aplica',
        ],
        'autosave' => [
            'details' => 'Los cambios de esta sección se guardan automáticamente al salir del campo.',
        ],
    ],
    'fields' => [
        'is_active' => 'Activa',
        'is_default' => 'Predeterminada',
        'sort_order' => 'Orden',
        'requires_quantity' => 'Requiere cantidad',
        'quantity_subject' => 'Sujeto de cantidad',
    ],
    'quantity_subjects' => [
        'guest' => 'Huésped',
        'pet' => 'Mascota',
        'vehicle' => 'Vehículo',
        'use' => 'Uso',
    ],
    'validation' => [
        'quantity_subject_required' => 'Se requiere un sujeto de cantidad cuando la cantidad es obligatoria.',
    ],
];
