<?php

declare(strict_types=1);

return [
    'bed_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Bed types',
    ],
    'index' => [
        'title' => 'Bed types',
        'description' => 'Manage the bed types available in the system.',
        'search_placeholder' => 'Search by slug or label...',
        'create_action' => 'New bed type',
        'columns' => [
            'active' => 'Active',
            'name' => 'Label',
            'slug' => 'Slug',
            'bed_capacity' => 'Bed capacity',
            'sort_order' => 'Order',
            'created' => 'Created',
        ],
        'confirm_delete' => [
            'title' => 'Delete bed type?',
            'message' => 'You are about to delete the bed type :bed_type. This action permanently removes it from the system.',
            'confirm_label' => 'Delete bed type',
        ],
        'deleted' => 'The bed type :bed_type was deleted successfully.',
        'activated' => 'The bed type :bed_type was activated successfully.',
        'deactivated' => 'The bed type :bed_type was deactivated successfully.',
    ],
    'create' => [
        'title' => 'Create bed type',
        'description' => 'Add a new bed type to the system.',
        'submit' => 'Create bed type',
        'created' => 'The bed type :bed_type was created successfully.',
        'fields' => [
            'name' => 'Slug',
            'name_help' => 'Slug format: lowercase letters, numbers, hyphens and underscores.',
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'bed_capacity' => 'Bed capacity',
            'sort_order' => 'Sort order',
        ],
    ],
    'show' => [
        'title' => 'Bed type details',
        'description' => 'Review the available information for this bed type.',
        'placeholder_title' => 'Bed type profile',
        'sections' => [
            'details' => 'Bed type details',
            'details_description' => 'Core information associated with this bed type.',
        ],
        'fields' => [
            'name' => 'Slug',
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'bed_capacity' => 'Bed capacity',
            'sort_order' => 'Sort order',
        ],
        'saved' => [
            'details' => 'The bed type details were updated successfully.',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete bed type',
                'title' => 'Delete bed type?',
                'message' => 'You are about to delete the bed type :bed_type. This action permanently removes it from the system.',
                'confirm_label' => 'Delete bed type',
                'deleted' => 'The bed type :bed_type was deleted successfully.',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'bed_type_id' => 'Bed type ID',
            'bed_capacity' => 'Bed capacity',
            'updated' => 'Last updated',
        ],
        'autosave' => [
            'details' => 'Changes in this section are saved automatically when you leave a field.',
        ],
    ],
];
