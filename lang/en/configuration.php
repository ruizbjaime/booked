<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Configuration',
    ],
    'index' => [
        'title' => 'System configuration',
        'description' => 'Adjust general settings that affect the entire system.',
        'sections' => [
            'images' => 'Image settings',
            'images_description' => 'Control avatar dimensions, quality, and file upload limits.',
            'tables' => 'Table settings',
            'tables_description' => 'Configure default display options for data tables.',
            'security' => 'Security',
            'security_description' => 'Password policies, login protection, and token expiration.',
            'password_policy' => 'Password policy',
            'password_policy_description' => 'Requirements enforced when users create or change their passwords in production.',
            'session' => 'Session',
            'session_description' => 'Control how long user sessions remain active.',
        ],
        'fields' => [
            'avatar_size' => 'Avatar size',
            'avatar_size_help' => 'Width and height in pixels for processed avatars.',
            'avatar_quality' => 'Optimization quality',
            'avatar_quality_help' => 'Image compression quality for avatars (1-100).',
            'avatar_format' => 'Default format',
            'avatar_format_help' => 'Image format used when processing avatars.',
            'max_upload_size_mb' => 'Max upload size',
            'max_upload_size_mb_help' => 'Maximum file size allowed for image uploads (MB).',
            'default_per_page' => 'Rows per page',
            'default_per_page_help' => 'Default number of rows displayed in data tables.',
            'password_min_length' => 'Minimum length',
            'password_min_length_help' => 'Minimum number of characters required.',
            'password_require_mixed_case' => 'Require uppercase and lowercase',
            'password_require_mixed_case_help' => 'Password must contain both uppercase and lowercase letters.',
            'password_require_numbers' => 'Require numbers',
            'password_require_numbers_help' => 'Password must contain at least one digit.',
            'password_require_symbols' => 'Require symbols',
            'password_require_symbols_help' => 'Password must contain at least one special character.',
            'password_require_uncompromised' => 'Check compromised passwords',
            'password_require_uncompromised_help' => 'Reject passwords found in known data breaches.',
            'login_rate_limit' => 'Login attempts',
            'login_rate_limit_help' => 'Maximum failed login attempts per minute before lockout.',
            'password_reset_expiry_minutes' => 'Reset token expiry',
            'password_reset_expiry_minutes_help' => 'Minutes before a password reset link expires.',
            'session_lifetime_minutes' => 'Session duration',
            'session_lifetime_minutes_help' => 'Minutes of inactivity before a session expires.',
        ],
        'server_limits' => [
            'title' => 'Server limits',
            'upload_max_filesize' => 'upload_max_filesize',
            'post_max_size' => 'post_max_size',
        ],
        'validation' => [
            'max_upload_exceeds_server' => 'The value cannot exceed the server limit of :limit MB.',
        ],
        'session_notice' => 'Changes to session duration apply to new sessions only. Existing sessions will keep their original expiry.',
        'saved' => [
            'images' => 'The image settings were updated successfully.',
            'tables' => 'The table settings were updated successfully.',
            'security' => 'The security settings were updated successfully.',
            'session' => 'The session settings were updated successfully.',
        ],
    ],
];
