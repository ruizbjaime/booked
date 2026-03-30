<?php

declare(strict_types=1);

return [
    'property_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Properties',
    ],
    'index' => [
        'title' => 'Properties',
        'description' => 'Review the properties managed by hosts in the system.',
        'search_placeholder' => 'Search by property, city, or country...',
        'create_action' => 'New property',
        'columns' => [
            'name' => 'Property',
            'slug' => 'Slug',
            'city' => 'City',
            'address' => 'Address',
            'country' => 'Country',
            'active' => 'Status',
            'created' => 'Created',
        ],
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'confirm_delete' => [
            'title' => 'Delete property?',
            'message' => 'You are about to delete the property :property. This action permanently removes it from the system.',
            'confirm_label' => 'Delete property',
        ],
        'deleted' => 'The property :property was deleted successfully.',
    ],
    'create' => [
        'title' => 'Create property',
        'description' => 'Add a new property managed by a host.',
        'submit' => 'Create property',
        'created' => 'The property :property was created successfully.',
        'active_help' => 'Make this property available immediately for operational use.',
        'active_enabled' => 'The property starts active.',
        'active_disabled' => 'The property starts inactive.',
        'fields' => [
            'name' => 'Name',
            'name_help' => 'The slug is generated automatically from the name using underscores between words.',
            'city' => 'City',
            'address' => 'Address',
            'country' => 'Country',
            'active' => 'Active',
        ],
    ],
    'show' => [
        'title' => 'Property details',
        'description' => 'Review the available information for this property.',
        'placeholder_title' => 'Property profile',
        'sections' => [
            'details' => 'Property details',
            'details_description' => 'Core location and identification details for this property.',
            'capacity' => 'Capacity',
            'capacity_description' => 'Guest capacity settings for this property.',
            'accommodation' => 'Accommodation',
            'accommodation_description' => 'Bedrooms configured for this property.',
        ],
        'fields' => [
            'slug' => 'Slug',
            'name' => 'Name',
            'description' => 'Description',
            'city' => 'City',
            'address' => 'Address',
            'country' => 'Country',
            'active' => 'Status',
            'base_capacity' => 'Base capacity',
            'max_capacity' => 'Max capacity',
        ],
        'avatar_delete_label' => 'Remove property photo',
        'avatar_add_label' => 'Add property photo',
        'saved' => [
            'details' => 'The property details were updated successfully.',
            'active' => 'The active status was updated successfully.',
            'avatar' => 'The property photo was updated successfully.',
            'avatar_deleted' => 'The property photo was removed.',
            'capacity' => 'The capacity settings were updated successfully.',
            'accommodation' => 'The bedroom ":bedroom" was added successfully.',
        ],
        'accommodation' => [
            'empty' => 'No bedrooms have been added to this property yet.',
            'form' => [
                'title' => 'Add bedroom',
                'description' => 'Create a new bedroom linked to this property.',
                'submit' => 'Add bedroom',
            ],
            'fields' => [
                'en_name' => 'Name (EN)',
                'es_name' => 'Name (ES)',
                'en_description' => 'Description (EN)',
                'es_description' => 'Description (ES)',
            ],
            'bed_types' => [
                'title' => 'Bed types',
                'empty' => 'No bed types have been added to this bedroom yet.',
                'quantity_badge' => 'Qty: :quantity',
                'created' => 'The bed type ":bed_type" was added to ":bedroom" successfully.',
                'fields' => [
                    'bed_type' => 'Bed type',
                    'quantity' => 'Quantity',
                ],
                'delete' => [
                    'action' => 'Remove bed type',
                    'aria_label' => 'Remove bed type :bed_type',
                    'title' => 'Remove bed type?',
                    'message' => 'You are about to remove the bed type :bed_type from bedroom :bedroom. This action removes the association from this property accommodation setup.',
                    'confirm_label' => 'Remove bed type',
                    'deleted' => 'The bed type :bed_type was removed from bedroom :bedroom successfully.',
                ],
                'form' => [
                    'title' => 'Add bed type',
                    'description' => 'Associate a bed type with the bedroom ":bedroom".',
                    'submit' => 'Save bed type',
                    'trigger' => 'Add bed type',
                ],
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'property_id' => 'Property ID',
            'updated' => 'Last updated',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete property',
                'title' => 'Delete property?',
                'message' => 'You are about to delete the property :property. This action permanently removes it from the system.',
                'confirm_label' => 'Delete property',
                'deleted' => 'The property :property was deleted successfully.',
            ],
        ],
        'status' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'autosave' => [
            'details' => 'Changes in this section are saved automatically when you leave a field.',
            'capacity' => 'Changes in this section are saved automatically when you leave a field.',
            'accommodation' => 'Add bedrooms from this section and they will be linked to the current property immediately.',
        ],
    ],
    'validation' => [
        'base_capacity_exceeds_max' => 'The base capacity must not exceed the max capacity.',
        'max_capacity_below_base' => 'The max capacity must not be less than the base capacity.',
    ],
];
