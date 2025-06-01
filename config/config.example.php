<?php

return [
    'app' => [
        'name' => 'MorningNewsletter.com',
        'url' => 'http://localhost',
        'env' => 'development',
        'debug' => true,
    ],
    
    'database' => [
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../database/newsletter.db',
    ],
    
    'mail' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'your-email@example.com',
        'password' => 'your-password',
        'encryption' => 'tls',
        'from_address' => 'newsletter@morningnewsletter.com',
        'from_name' => 'MorningNewsletter',
    ],
    
    'integrations' => [
        'weather' => [
            'api_key' => 'your-openweathermap-api-key',
        ],
        'news' => [
            'api_key' => 'your-newsapi-key',
        ],
        'stripe' => [
            'secret_key' => 'your-stripe-secret-key',
            'publishable_key' => 'your-stripe-publishable-key',
        ],
        'github' => [
            'client_id' => 'your-github-client-id',
            'client_secret' => 'your-github-client-secret',
        ],
    ],
    
    'security' => [
        'session_lifetime' => 7200, // 2 hours
        'password_min_length' => 8,
        'csrf_token_lifetime' => 3600, // 1 hour
    ],
]; 