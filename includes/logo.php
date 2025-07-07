<?php
/**
 * Logo component for MorningNewsletter
 * Usage: include this file and call renderLogo($size, $href, $classes)
 * 
 * @param string $size - Size variant: 'sm', 'md', 'lg', 'xl'
 * @param string $href - Link destination (default: '/')
 * @param string $classes - Additional CSS classes
 */

function renderLogo($size = 'md', $href = '/', $classes = '') {
    $sizeClasses = [
        'sm' => 'text-lg',
        'md' => 'text-2xl',
        'lg' => 'text-3xl',
        'xl' => 'text-4xl'
    ];
    
    $sunSizeClasses = [
        'sm' => 'h-3 w-2',
        'md' => 'h-4 w-3',
        'lg' => 'h-5 w-4',
        'xl' => 'h-6 w-5'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $sunSizeClass = $sunSizeClasses[$size] ?? $sunSizeClasses['md'];
    $baseClasses = "$sizeClass font-bold text-blue-600 hover:text-blue-700 transition-colors duration-200";
    $allClasses = trim("$baseClasses $classes");
    
    echo "<a href=\"$href\" class=\"$allClasses flex items-center justify-center\">";
    echo "<img src=\"/assets/logos/logo-sun.png\" alt=\"\" class=\"$sunSizeClass logo-sun-rotated self-center\">";
    echo "<span><span class=\"font-extrabold\">Morning</span><span class=\"font-medium\">Newsletter</span></span>";
    echo "</a>";
}
?>