<?php
/**
 * Email Configuration
 * 
 * This file contains email provider settings.
 * To switch providers, just change the EMAIL_PROVIDER value.
 */

// Current email provider configuration
return [
    // Email provider: 'maileroo', 'resend', 'smtp', 'sendgrid', etc.
    'provider' => 'maileroo',
    
    // Maileroo.com configuration
    'maileroo' => [
        'api_key' => 'c3bcabc939c5c043963c8530a373f848d522ef20f97b2a364ec5095b2a422857',
        'from_email' => 'noreply@morningnewsletter.com',
        'from_name' => 'MorningNewsletter'
    ],
    
    // Resend.com configuration (backup)
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