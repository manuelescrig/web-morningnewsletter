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
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $baseClasses = "$sizeClass font-bold text-blue-600 hover:text-blue-700 transition-colors duration-200";
    $allClasses = trim("$baseClasses $classes");
    
    echo "<a href=\"$href\" class=\"$allClasses\">";
    echo "<span class=\"font-extrabold\">Morning</span><span class=\"font-medium\">Newsletter</span>";
    echo "</a>";
}
?>