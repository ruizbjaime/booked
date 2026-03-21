<?php

declare(strict_types=1);

return [
    'fee_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Fee types',
    ],
    'index' => [
        'title' => 'Fee types',
        'description' => 'Manage the fee types available in the system.',
        'search_placeholder' => 'Search by slug or label...',
        'create_action' => 'New fee type',
        'columns' => [
            'active' => 'Active',
            'name' => 'Label',
            'slug' => 'Slug',
            'order' => 'Order',
            'created' => 'Created',
        ],
        'confirm_delete' => [
            'title' => 'Delete fee type?',
            'message' => 'You are about to delete the fee type :fee_type. This action permanently removes it from the system.',
            'confirm_label' => 'Delete fee type',
        ],
        'deleted' => 'The fee type :fee_type was deleted successfully.',
        'activated' => 'The fee type :fee_type was activated successfully.',
        'deactivated' => 'The fee type :fee_type was deactivated successfully.',
    ],
    'create' => [
        'title' => 'Create fee type',
        'description' => 'Add a new fee type to the system.',
        'submit' => 'Create fee type',
        'created' => 'The fee type :fee_type was created successfully.',
        'fields' => [
            'name' => 'Slug',
            'name_help' => 'Slug format: lowercase letters, numbers, hyphens and underscores.',
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'order' => 'Order',
        ],
    ],
    'show' => [
        'title' => 'Fee type details',
        'description' => 'Review the available information for this fee type.',
        'placeholder_title' => 'Fee type profile',
        'sections' => [
            'details' => 'Fee type details',
            'details_description' => 'Core information associated with this fee type.',
            'charge_bases' => 'Allowed charge bases',
            'charge_bases_description' => 'Activate or deactivate which charge bases are allowed for this fee type.',
        ],
        'fields' => [
            'name' => 'Slug',
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'order' => 'Order',
        ],
        'saved' => [
            'details' => 'The fee type details were updated successfully.',
            'charge_bases' => 'The allowed charge bases were updated successfully.',
        ],
        'charge_bases' => [
            'empty' => 'No charge bases have been assigned yet.',
            'save' => 'Save charge bases',
            'managed_in_catalog' => 'Shared metadata for each charge basis is managed from the charge bases catalog.',
            'inactive_badge' => 'Catalog inactive',
            'order_hint' => 'Drag to reorder. The first item is the default.',
            'default_badge' => 'Default',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete fee type',
                'title' => 'Delete fee type?',
                'message' => 'You are about to delete the fee type :fee_type. This action permanently removes it from the system.',
                'confirm_label' => 'Delete fee type',
                'deleted' => 'The fee type :fee_type was deleted successfully.',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'fee_type_id' => 'Fee type ID',
            'order' => 'Order',
            'updated' => 'Last updated',
        ],
        'autosave' => [
            'details' => 'Changes in this section are saved automatically when you leave a field.',
        ],
    ],
    'validation' => [
        'duplicate_charge_bases' => 'Duplicate charge bases are not allowed.',
    ],
];
