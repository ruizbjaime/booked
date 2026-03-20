<?php

declare(strict_types=1);

return [
    'bath_room_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Bathroom types',
    ],
    'index' => [
        'title' => 'Bathroom types',
        'description' => 'Manage the bathroom types available in the system.',
        'search_placeholder' => 'Search by slug, label, or description...',
        'create_action' => 'New bathroom type',
        'columns' => [
            'name' => 'Label',
            'slug' => 'Slug',
            'description' => 'Description',
            'sort_order' => 'Order',
            'created' => 'Created',
        ],
        'confirm_delete' => [
            'title' => 'Delete bathroom type?',
            'message' => 'You are about to delete the bathroom type :bath_room_type. This action permanently removes it from the system.',
            'confirm_label' => 'Delete bathroom type',
        ],
        'deleted' => 'The bathroom type :bath_room_type was deleted successfully.',
    ],
    'create' => [
        'title' => 'Create bathroom type',
        'description' => 'Add a new bathroom type to the system.',
        'submit' => 'Create bathroom type',
        'created' => 'The bathroom type :bath_room_type was created successfully.',
        'fields' => [
            'name' => 'Slug',
            'name_help' => 'Slug format: lowercase letters, numbers, hyphens and underscores.',
            'name_en' => 'Label (EN)',
            'name_es' => 'Label (ES)',
            'description' => 'Description',
            'sort_order' => 'Sort order',
        ],
    ],
    'show' => [
        'title' => 'Bathroom type details',
        'description' => 'Review the available information for this bathroom type.',
        'placeholder_title' => 'Bathroom type profile',
        'sections' => [
            'details' => 'Bathroom type details',
            'details_description' => 'Core information associated with this bathroom type.',
        ],
        'fields' => [
            'name' => 'Slug',
            'name_en' => 'Label (EN)',
            'name_es' => 'Label (ES)',
            'description' => 'Description',
            'sort_order' => 'Sort order',
        ],
        'saved' => [
            'details' => 'The bathroom type details were updated successfully.',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete bathroom type',
                'title' => 'Delete bathroom type?',
                'message' => 'You are about to delete the bathroom type :bath_room_type. This action permanently removes it from the system.',
                'confirm_label' => 'Delete bathroom type',
                'deleted' => 'The bathroom type :bath_room_type was deleted successfully.',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'bath_room_type_id' => 'Bathroom type ID',
            'sort_order' => 'Sort order',
            'updated' => 'Last updated',
        ],
        'autosave' => [
            'details' => 'Changes in this section are saved automatically when you leave a field.',
        ],
    ],
];
