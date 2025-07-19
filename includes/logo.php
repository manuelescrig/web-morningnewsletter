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
        'sm' => 'logo-icon',
        'md' => 'logo-icon',
        'lg' => 'logo-icon',
        'xl' => 'logo-icon'
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $sunSizeClass = $sunSizeClasses[$size] ?? $sunSizeClasses['md'];
    $baseClasses = "$sizeClass font-bold text-primary hover:text-primary-dark transition-colors duration-200";
    $allClasses = trim("$baseClasses $classes");
    
    echo "<a href=\"$href\" class=\"$allClasses flex items-center justify-center\">";
    echo "<img src=\"/assets/logos/logo-sun.png\" alt=\"\" class=\"$sunSizeClass logo-sun-rotated self-center\" style=\"height: 1.05rem !important; width: auto; margin-bottom: -1px;\">";
    echo "<span><span class=\"font-extrabold\">Morning</span><span class=\"font-medium\">Newsletter</span></span>";
    echo "</a>";
}
?>