<?php

return [
    'free' => [
        'name' => 'Free',
        'max_contacts' => 100,
        'max_campaigns' => 5,
        'max_messages_per_month' => 500,
        'features' => [
            'WhatsApp Integration',
            'Telegram Integration',
            'Basic Analytics',
        ],
    ],
    'pro' => [
        'name' => 'Pro',
        'price' => 2900, // INR
        'max_contacts' => 5000,
        'max_campaigns' => 50,
        'max_messages_per_month' => 50000,
        'features' => [
            'Priority Support',
            'Advanced Analytics',
            'Collaborative Inbox',
            'Canned Responses',
        ],
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 9900, // INR
        'max_contacts' => -1, // Unlimited
        'max_campaigns' => -1, // Unlimited
        'max_messages_per_month' => -1, // Unlimited
        'features' => [
            'Dedicated Support',
            'Custom integrations',
            'White-label options',
            'API Access',
        ],
    ],
];
