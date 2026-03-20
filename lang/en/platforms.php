<?php

declare(strict_types=1);

return [
    'platform_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Platforms',
    ],
    'index' => [
        'title' => 'Platforms',
        'description' => 'Manage the booking platforms available in the system.',
        'search_placeholder' => 'Search by name...',
        'create_action' => 'New platform',
        'columns' => [
            'active' => 'Active',
            'localized_name' => 'Name',
            'name' => 'Identifier',
            'color' => 'Color',
            'commission' => 'Commission %',
            'commission_tax' => 'Commission tax %',
            'sort_order' => 'Order',
            'created' => 'Created',
        ],
        'confirm_delete' => [
            'title' => 'Delete platform?',
            'message' => 'You are about to delete the platform :platform. This action permanently removes it from the system.',
            'confirm_label' => 'Delete platform',
        ],
        'deleted' => 'The platform :platform was deleted successfully.',
        'activated' => 'The platform :platform was activated successfully.',
        'deactivated' => 'The platform :platform was deactivated successfully.',
    ],
    'create' => [
        'title' => 'Create platform',
        'description' => 'Add a new booking platform to the system.',
        'submit' => 'Create platform',
        'created' => 'The platform :platform was created successfully.',
        'active_help' => 'Make this platform available for selection immediately.',
        'active_enabled' => 'The platform starts active.',
        'active_disabled' => 'The platform starts inactive.',
        'fields' => [
            'name' => 'Name',
            'name_help' => 'Slug format: lowercase letters, numbers, hyphens and underscores.',
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'color' => 'Color',
            'color_custom' => 'Custom color (hex)',
            'color_custom_option' => 'Custom...',
            'sort_order' => 'Sort order',
            'commission' => 'Commission %',
            'commission_tax' => 'Commission tax %',
            'active' => 'Active',
        ],
    ],
    'show' => [
        'title' => 'Platform details',
        'description' => 'Review the available information for this platform.',
        'placeholder_title' => 'Platform profile',
        'sections' => [
            'details' => 'Platform details',
            'details_description' => 'Core information associated with this platform.',
        ],
        'fields' => [
            'name' => 'Name',
            'name_help' => 'Slug format: lowercase letters, numbers, hyphens and underscores.',
            'en_name' => 'Label (EN)',
            'es_name' => 'Label (ES)',
            'color' => 'Color',
            'color_custom' => 'Custom color (hex)',
            'color_custom_option' => 'Custom...',
            'sort_order' => 'Sort order',
            'commission' => 'Commission %',
            'commission_tax' => 'Commission tax %',
            'active' => 'Active',
        ],
        'saved' => [
            'details' => 'The platform details were updated successfully.',
            'active' => 'The active status was updated successfully.',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete platform',
                'title' => 'Delete platform?',
                'message' => 'You are about to delete the platform :platform. This action permanently removes it from the system.',
                'confirm_label' => 'Delete platform',
                'deleted' => 'The platform :platform was deleted successfully.',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'platform_id' => 'Platform ID',
            'updated' => 'Last updated',
        ],
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'autosave' => [
            'details' => 'Changes in this section are saved automatically when you leave a field.',
        ],
    ],
];
