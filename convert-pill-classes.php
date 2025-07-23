<?php
// Script to help convert pill button classes

$replacements = [
    // Primary buttons
    'btn-pill inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium shadow-sm text-white bg-primary hover-bg-primary-dark' => 'pill-primary inline-flex items-center',
    
    // Secondary buttons  
    'btn-pill inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium shadow-sm text-gray-700 bg-white hover:bg-gray-50' => 'pill-secondary inline-flex items-center',
    'btn-pill relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50' => 'pill-secondary relative inline-flex items-center',
    'btn-pill relative ml-3 inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50' => 'pill-secondary relative ml-3 inline-flex items-center',
    
    // Danger buttons
    'btn-pill inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50' => 'pill-danger inline-flex items-center',
    'btn-pill inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium text-red-700 bg-red-50 hover:bg-red-100' => 'pill-danger inline-flex items-center',
    
    // Disabled buttons
    'btn-pill relative inline-flex items-center border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400' => 'pill-secondary relative inline-flex items-center opacity-50 cursor-not-allowed',
    'btn-pill relative ml-3 inline-flex items-center border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400' => 'pill-secondary relative ml-3 inline-flex items-center opacity-50 cursor-not-allowed',
    
    // Pagination buttons
    'btn-pill relative inline-flex items-center px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' => 'pill-secondary relative inline-flex items-center px-2 py-2',
    'btn-pill relative inline-flex items-center px-2 py-2 text-gray-300 ring-1 ring-inset ring-gray-300' => 'pill-secondary relative inline-flex items-center px-2 py-2 opacity-50 cursor-not-allowed',
    
    // Status badges
    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800' => 'pill-badge pill-badge-success inline-flex items-center',
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800' => 'pill-badge pill-badge-success inline-flex items-center',
    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800' => 'pill-badge pill-badge-warning inline-flex items-center',
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800' => 'pill-badge pill-badge-warning inline-flex items-center',
    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800' => 'pill-badge pill-badge-danger inline-flex items-center',
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800' => 'pill-badge pill-badge-danger inline-flex items-center',
    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800' => 'pill-badge pill-badge-gray inline-flex items-center',
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800' => 'pill-badge pill-badge-gray inline-flex items-center',
    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-lightest text-primary-dark' => 'pill-badge pill-badge-info inline-flex items-center',
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-lightest text-primary-dark' => 'pill-badge pill-badge-info inline-flex items-center',
    
    // Plan badges with colors
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800' => 'pill-badge pill-badge-info inline-flex items-center',
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800' => 'pill-badge pill-badge-danger inline-flex items-center',
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-lightest text-primary' => 'pill-badge pill-badge-info inline-flex items-center',
];

// Process all dashboard PHP files
$dashboardDir = __DIR__ . '/dashboard/';
$files = glob($dashboardDir . '*.php');

foreach ($files as $file) {
    echo "Processing: " . basename($file) . "\n";
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Apply replacements
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Save if changed
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "  - Updated\n";
    } else {
        echo "  - No changes\n";
    }
}

echo "\nConversion complete!\n";
?>