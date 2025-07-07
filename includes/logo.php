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
        'sm' => 'h-6',      // ~24px height
        'md' => 'h-8',      // ~32px height
        'lg' => 'h-10',     // ~40px height
        'xl' => 'h-12'      // ~48px height
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $baseClasses = "hover:opacity-80 transition-opacity duration-200";
    $allClasses = trim("$baseClasses $classes");
    
    echo "<a href=\"$href\" class=\"$allClasses inline-block\">";
    echo "<img src=\"/assets/logos/logo-main.png\" alt=\"MorningNewsletter\" class=\"$sizeClass w-auto\">";
    echo "</a>";
}
?>