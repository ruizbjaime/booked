<?php

declare(strict_types=1);

return [
    'doc_type_label' => '":name" (#:id)',
    'navigation' => [
        'label' => 'Document types',
    ],
    'index' => [
        'title' => 'Document types',
        'description' => 'Manage the identification document types available in the system.',
        'search_placeholder' => 'Search by code or name...',
        'create_action' => 'New document type',
        'columns' => [
            'active' => 'Active',
            'name' => 'Name',
            'code' => 'Code',
            'sort_order' => 'Order',
            'created' => 'Created',
        ],
        'confirm_delete' => [
            'title' => 'Delete document type?',
            'message' => 'You are about to delete the document type :doc_type. This action permanently removes it from the system.',
            'confirm_label' => 'Delete document type',
        ],
        'confirm_deactivate' => [
            'title' => 'Deactivate document type instead?',
            'message' => 'The document type :doc_type cannot be deleted because it has associated users. You can deactivate it instead, which will remove it as a selectable option.',
            'confirm_label' => 'Deactivate document type',
        ],
        'deleted' => 'The document type :doc_type was deleted successfully.',
        'activated' => 'The document type :doc_type was activated successfully.',
        'deactivated' => 'The document type :doc_type was deactivated successfully.',
    ],
    'create' => [
        'title' => 'Create document type',
        'description' => 'Add a new identification document type to the system.',
        'submit' => 'Create document type',
        'created' => 'The document type :doc_type was created successfully.',
        'active_help' => 'Make this document type available for selection immediately.',
        'active_enabled' => 'The document type starts active.',
        'active_disabled' => 'The document type starts inactive.',
        'fields' => [
            'code' => 'Code',
            'en_name' => 'Name (EN)',
            'es_name' => 'Name (ES)',
            'sort_order' => 'Sort order',
            'active' => 'Active',
        ],
    ],
    'show' => [
        'title' => 'Document type details',
        'description' => 'Review the available information for this document type.',
        'placeholder_title' => 'Document type profile',
        'sections' => [
            'details' => 'Document type details',
            'details_description' => 'Core information associated with this document type.',
        ],
        'fields' => [
            'code' => 'Code',
            'en_name' => 'Name (EN)',
            'es_name' => 'Name (ES)',
            'sort_order' => 'Sort order',
            'active' => 'Active',
        ],
        'saved' => [
            'details' => 'The document type details were updated successfully.',
            'active' => 'The active status was updated successfully.',
        ],
        'quick_actions' => [
            'title' => 'Quick actions',
            'delete' => [
                'action' => 'Delete document type',
                'title' => 'Delete document type?',
                'message' => 'You are about to delete the document type :doc_type. This action permanently removes it from the system.',
                'confirm_label' => 'Delete document type',
                'deleted' => 'The document type :doc_type was deleted successfully.',
            ],
            'deactivate' => [
                'title' => 'Deactivate document type instead?',
                'message' => 'The document type :doc_type cannot be deleted because it has associated users. You can deactivate it instead, which will remove it as a selectable option.',
                'confirm_label' => 'Deactivate document type',
                'deactivated' => 'The document type :doc_type was deactivated successfully.',
            ],
        ],
        'stats' => [
            'title' => 'Statistics',
            'doc_type_id' => 'Document type ID',
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
