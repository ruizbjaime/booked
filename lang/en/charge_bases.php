<?php

declare(strict_types=1);

return [
    'charge_basis_label' => '":name" (#:id)',
    'tabs' => [
        'en' => 'English',
        'es' => 'Spanish',
    ],
    'navigation' => [
        'label' => 'Charge bases',
    ],
    'index' => [
        'title' => 'Charge bases',
        'description' => 'Manage the available charge bases and their shared metadata.',
        'search_placeholder' => 'Search by slug or label...',
        'create_action' => 'New charge basis',
        'columns' => [
            'active' => 'Active',
            'slug' => 'Label',
            'order' => 'Order',
            'created' => 'Created',
        ],
        'confirm_delete' => [
            'title' => 'Delete charge basis?',
            'message' => 'You are about to delete the charge basis :charge_basis. This action permanently removes it from the system.',
            'confirm_label' => 'Delete charge basis',
        ],
        'deleted' => 'The charge basis :charge_basis was deleted successfully.',
        'activated' => 'The charge basis :charge_basis was activated successfully.',
        'deactivated' => 'The charge basis :charge_basis was deactivated successfully.',
    ],
    'create' => [
        'title' => 'Create charge basis',
        'description' => 'Add a new charge basis to the catalog.',
        'submit' => 'Create charge basis',
        'created' => 'The charge basis :charge_basis was created successfully.',
        'active_enabled' => 'This charge basis starts active.',
        'active_disabled' => 'This charge basis starts inactive.',
        'fields' => [
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'description' => 'Description',
            'en_description' => 'Description (EN)',
            'es_description' => 'Description (ES)',
            'order' => 'Order',
            'is_active' => 'Active',
            'requires_quantity' => 'Requires quantity',
            'quantity_subject' => 'Quantity subject',
        ],
    ],
    'show' => [
        'title' => 'Charge basis details',
        'description' => 'Review the available information for this charge basis.',
        'placeholder_title' => 'Charge basis profile',
        'sections' => [
            'details' => 'Charge basis details',
            'details_description' => 'Shared metadata used by fee types that allow this charge basis.',
            'configuration' => 'Configuration',
            'configuration_description' => 'Status and quantity behavior for this charge basis.',
        ],
        'fields' => [
            'slug' => 'Slug',
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'description' => 'Description',
            'en_description' => 'Description (EN)',
            'es_description' => 'Description (ES)',
            'order' => 'Order',
            'is_active' => 'Active',
            'requires_quantity' => 'Requires quantity',
            'quantity_subject' => 'Quantity subject',
        ],
        'saved' => [
            'details' => 'The charge basis details were updated successfully.',
            'configuration' => 'The charge basis configuration was updated successfully.',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete charge basis',
                'title' => 'Delete charge basis?',
                'message' => 'You are about to delete the charge basis :charge_basis. This action permanently removes it from the system.',
                'confirm_label' => 'Delete charge basis',
                'deleted' => 'The charge basis :charge_basis was deleted successfully.',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'charge_basis_id' => 'Charge basis ID',
            'order' => 'Order',
            'updated' => 'Last updated',
        ],
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'quantity_required' => 'Quantity required',
            'quantity_not_required' => 'Quantity not required',
            'not_applicable' => 'Not applicable',
        ],
        'autosave' => [
            'details' => 'Changes in this section are saved automatically when you leave a field.',
            'configuration' => 'Changes in this section are saved automatically when you toggle a control.',
        ],
    ],
    'fields' => [
        'is_active' => 'Active',
        'is_default' => 'Default',
        'sort_order' => 'Sort order',
        'requires_quantity' => 'Requires quantity',
        'quantity_subject' => 'Quantity subject',
    ],
    'quantity_subjects' => [
        'guest' => 'Guest',
        'pet' => 'Pet',
        'vehicle' => 'Vehicle',
        'use' => 'Use',
    ],
    'validation' => [
        'quantity_subject_required' => 'A quantity subject is required when quantity is required.',
    ],
];
