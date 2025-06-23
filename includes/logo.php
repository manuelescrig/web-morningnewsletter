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
        'sm' => 'h-4 w-4',
        'md' => 'h-6 w-6',
        'lg' => 'h-8 w-8',
        'xl' => 'h-10 w-10'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $sunSizeClass = $sunSizeClasses[$size] ?? $sunSizeClasses['md'];
    $baseClasses = "$sizeClass font-bold text-blue-600 hover:text-blue-700 transition-colors duration-200";
    $allClasses = trim("$baseClasses $classes");
    
    echo "<a href=\"$href\" class=\"$allClasses flex items-center gap-2\">";
    echo "<img src=\"/assets/logos/logo-sun.svg\" alt=\"\" class=\"$sunSizeClass\" style=\"transform: rotate(90deg);\">";
    echo "<span><span class=\"font-extrabold\">Morning</span><span class=\"font-medium\">Newsletter</span></span>";
    echo "</a>";
}
?>