<?php

declare(strict_types=1);

return [
    'navigation' => [
        'label' => 'Calendar',
        'settings' => 'Calendar settings',
    ],

    'index' => [
        'title' => 'Pricing calendar',
        'description' => 'Yearly calendar showing demand temperature and pricing categories for each night.',
        'year_label' => 'Year',
        'previous_year' => 'Previous year',
        'next_year' => 'Next year',
        'no_data' => 'No calendar data generated for this year.',
        'generate_prompt' => 'Run the calendar generator to populate this year.',

        'legend' => [
            'title' => 'Pricing categories',
        ],

        'stats' => [
            'title' => 'Year summary',
            'total_holidays' => 'Public holidays',
            'bridge_weekends' => 'Bridge weekends',
            'cat_1' => 'Premium nights',
            'cat_2' => 'High nights',
            'cat_3' => 'Standard weekend nights',
            'cat_4' => 'Economy nights',
        ],

        'day_detail' => [
            'holiday' => 'Holiday',
            'season' => 'Season',
            'impact' => 'Impact score',
            'pricing' => 'Pricing category',
            'bridge' => 'Bridge day',
            'quincena' => 'Near payday',
            'original_date' => 'Original date',
            'observed_date' => 'Observed date',
            'moved' => 'Moved from :date',
        ],

        'months' => [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ],

        'weekdays' => [
            'mon' => 'Mo',
            'tue' => 'Tu',
            'wed' => 'We',
            'thu' => 'Th',
            'fri' => 'Fr',
            'sat' => 'Sa',
            'sun' => 'Su',
        ],
    ],

    'settings' => [
        'title' => 'Calendar settings',
        'description' => 'Configure holidays, seasons, pricing categories and rules used to generate the calendar.',

        'sections' => [
            'holidays' => 'Holiday definitions',
            'holidays_description' => 'Colombian public holidays and their impact weights.',
            'seasons' => 'Season blocks',
            'seasons_description' => 'High-demand season periods and their calculation strategies.',
            'categories' => 'Pricing categories',
            'categories_description' => 'Pricing tiers with color coding and multipliers.',
            'rules' => 'Pricing rules',
            'rules_description' => 'Rules that map days to pricing categories based on conditions.',
        ],

        'regenerate' => [
            'button' => 'Regenerate calendar',
            'title' => 'Regenerate calendar?',
            'message' => 'This will recalculate all calendar days for the current and next year using the current configuration. Existing calendar data will be updated.',
            'confirm_label' => 'Regenerate',
            'success' => ':count calendar days were regenerated successfully.',
        ],

        'saved' => 'Settings saved successfully.',

        'fields' => [
            'name' => 'Slug',
            'en_name' => 'Name (EN)',
            'es_name' => 'Name (ES)',
            'group' => 'Group',
            'month' => 'Month',
            'day' => 'Day',
            'easter_offset' => 'Easter offset',
            'moves_to_monday' => 'Moves to Monday',
            'base_impact_weights' => 'Impact weights',
            'special_overrides' => 'Special overrides',
            'calculation_strategy' => 'Calculation strategy',
            'priority' => 'Priority',
            'level' => 'Level',
            'color' => 'Color',
            'multiplier' => 'Multiplier',
            'rule_type' => 'Rule type',
            'conditions' => 'Conditions',
            'en_description' => 'Description (EN)',
            'es_description' => 'Description (ES)',
            'sort_order' => 'Sort order',
            'is_active' => 'Active',
        ],
    ],

    'show' => [
        'title' => 'Day details',
        'description' => 'Full analysis for :date.',
        'back' => 'Back to calendar',

        'sections' => [
            'general' => 'General',
            'holiday' => 'Holiday information',
            'season' => 'Season information',
            'pricing' => 'Pricing information',
        ],

        'fields' => [
            'date' => 'Date',
            'day_of_week' => 'Day of week',
            'is_holiday' => 'Public holiday',
            'holiday_name' => 'Holiday name',
            'holiday_group' => 'Holiday group',
            'holiday_impact' => 'Impact score',
            'original_date' => 'Original date',
            'observed_date' => 'Observed date',
            'is_bridge_day' => 'Bridge day',
            'season' => 'Season',
            'pricing_category' => 'Pricing category',
            'pricing_level' => 'Pricing level',
            'is_quincena' => 'Near payday',
            'notes' => 'Notes',
        ],
    ],

    'command' => [
        'generating' => 'Generating calendar days from :from to :to...',
        'generated' => 'Generated :count calendar days.',
        'error_from_after_to' => '--from must be before --to.',
        'error_invalid_year' => 'Year must be between 2000 and 2100.',
    ],

    'holiday_groups' => [
        'fixed' => 'Fixed',
        'emiliani' => 'Emiliani (movable)',
        'easter_based' => 'Easter-based',
    ],

    'season_strategies' => [
        'holy_week' => 'Holy Week',
        'year_end' => 'Year-End',
        'october_recess' => 'October Recess',
        'foreign_tourist' => 'Foreign Tourist',
        'fixed_range' => 'Fixed Range',
    ],

    'rule_types' => [
        'season_days' => 'Season days',
        'holiday_bridge' => 'Holiday bridge',
        'normal_weekend' => 'Normal weekend',
        'economy_default' => 'Economy default',
    ],
];
