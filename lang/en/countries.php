<?php

declare(strict_types=1);

return [
    'country_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Countries',
    ],
    'index' => [
        'title' => 'Countries',
        'description' => 'Manage the countries available in the system.',
        'search_placeholder' => 'Search by name or phone code...',
        'create_action' => 'New country',
        'columns' => [
            'active' => 'Active',
            'name' => 'Name',
            'phone_code' => 'Phone code',
            'sort_order' => 'Order',
            'created' => 'Created',
        ],
        'confirm_delete' => [
            'title' => 'Delete country?',
            'message' => 'You are about to delete the country :country. This action permanently removes it from the system.',
            'confirm_label' => 'Delete country',
        ],
        'confirm_deactivate' => [
            'title' => 'Deactivate country?',
            'message' => 'The country :country has associated users and cannot be deleted. It will be deactivated instead and will no longer appear as a selectable option.',
            'confirm_label' => 'Deactivate country',
        ],
        'deleted' => 'The country :country was deleted successfully.',
        'deactivated_instead' => 'The country :country was deactivated because it has associated users.',
        'activated' => 'The country :country was activated successfully.',
        'deactivated' => 'The country :country was deactivated successfully.',
    ],
    'create' => [
        'title' => 'Create country',
        'description' => 'Add a new country to the system.',
        'submit' => 'Create country',
        'created' => 'The country :country was created successfully.',
        'active_help' => 'Make this country available for selection immediately.',
        'active_enabled' => 'The country starts active.',
        'active_disabled' => 'The country starts inactive.',
        'fields' => [
            'en_name' => 'Name (EN)',
            'es_name' => 'Name (ES)',
            'iso_alpha2' => 'ISO Alpha-2',
            'iso_alpha3' => 'ISO Alpha-3',
            'phone_code' => 'Phone code',
            'sort_order' => 'Sort order',
            'active' => 'Active',
        ],
    ],
    'show' => [
        'title' => 'Country details',
        'description' => 'Review the available information for this country.',
        'placeholder_title' => 'Country profile',
        'sections' => [
            'details' => 'Country details',
            'details_description' => 'Core information associated with this country.',
        ],
        'fields' => [
            'en_name' => 'Name (EN)',
            'es_name' => 'Name (ES)',
            'iso_alpha2' => 'ISO Alpha-2',
            'iso_alpha3' => 'ISO Alpha-3',
            'phone_code' => 'Phone code',
            'sort_order' => 'Sort order',
            'active' => 'Active',
        ],
        'saved' => [
            'details' => 'The country details were updated successfully.',
            'active' => 'The active status was updated successfully.',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete country',
                'title' => 'Delete country?',
                'message' => 'You are about to delete the country :country. This action permanently removes it from the system.',
                'confirm_label' => 'Delete country',
                'deleted' => 'The country :country was deleted successfully.',
            ],
            'deactivate' => [
                'title' => 'Deactivate country?',
                'message' => 'The country :country has associated users and cannot be deleted. It will be deactivated instead and will no longer appear as a selectable option.',
                'confirm_label' => 'Deactivate country',
                'deactivated' => 'The country :country was deactivated because it has associated users.',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'country_id' => 'Country ID',
            'associated_users' => 'Associated users',
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
