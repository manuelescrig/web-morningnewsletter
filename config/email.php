<?php
/**
 * Email Configuration
 * 
 * This file contains email provider settings.
 * To switch providers, just change the EMAIL_PROVIDER value.
 */

// Current email provider configuration
return [
    // Email provider: 'resend', 'smtp', 'sendgrid', etc.
    'provider' => 'resend',
    
    // Resend.com configuration
    'resend' => [
        'api_key' => 're_Hwa9Ryf4_KnbL2HKhE8ZcDkgnrs7RycZa',
        'from_email' => 'onboarding@resend.dev',
        'from_name' => 'MorningNewsletter'
    ],
    
    // SMTP fallback configuration (for local development or backup)
    'smtp' => [
        'host' => 'localhost',
        'port' => 587,
        'username' => '',
        'password' => '',
        'from_email' => 'noreply@morningnewsletter.com',
        'from_name' => 'MorningNewsletter'
    ],
    
    // Placeholder for other providers
    'sendgrid' => [
        'api_key' => '',
        'from_email' => 'noreply@morningnewsletter.com',
        'from_name' => 'MorningNewsletter'
    ]
];
?>