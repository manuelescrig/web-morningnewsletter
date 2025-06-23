<?php
/**
 * Email Configuration
 * 
 * This file contains email provider settings.
 * To switch providers, just change the EMAIL_PROVIDER value.
 */

// Current email provider configuration
return [
    // Email provider: 'plunk', 'resend', 'smtp', 'sendgrid', etc.
    'provider' => 'plunk',
    
    // Plunk.com configuration
    'plunk' => [
        'api_key' => 'sk_446ac82b1605277904ca083f458daf08d85868108044d555',
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