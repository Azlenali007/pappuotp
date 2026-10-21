<?php
/**
 * Provider API Configuration
 */

return [
    'default_provider' => 'global-otp-hub',
    'http_timeout_seconds' => 15,
    'retry_attempts' => 2,
    'supported_actions' => [
        'get_services',
        'get_countries',
        'get_prices',
        'buy_number',
        'check_status',
        'cancel_number',
        'get_balance'
    ],
    // Built-in adapter mappings
    'adapters' => [
        'global-otp-hub' => 'CustomProviderAdapter',
        'fastsms-direct' => 'CustomProviderAdapter'
    ]
];
