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
    // Inline SVG with currentColor for tinting
    echo "<svg width=\"15\" height=\"25\" viewBox=\"0 0 15 25\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\" class=\"$sunSizeClass logo-sun-rotated self-center\" style=\"height: 1.05rem !important; width: auto; margin-bottom: -1px;\">";
    echo "<path d=\"M6.75775 12.5C6.75775 9.29376 9.49411 6.62913 12.7622 6.62913C13.5599 6.62913 14.3242 6.80244 15 7.0949L15 17.916C14.3242 18.1976 13.5599 18.3709 12.7622 18.3709C9.49411 18.3709 6.75775 15.7062 6.75775 12.5Z\" fill=\"currentColor\"/>";
    echo "<path d=\"M13.3937 21.3713L13.3937 24.036C13.3937 24.5992 12.9948 25 12.4963 25C11.9978 25 11.5989 24.5992 11.5989 24.036L11.5989 21.3713C11.5989 20.8081 12.0088 20.4073 12.4963 20.3964C12.9948 20.3856 13.3937 20.8081 13.3937 21.3713Z\" fill=\"currentColor\"/>";
    echo "<path d=\"M5.72747 18.1543C6.13737 17.7535 6.71343 17.7535 7.06794 18.0893C7.42244 18.425 7.41136 18.9991 7.00147 19.3999L5.07385 21.2847C4.66395 21.6854 4.08789 21.6854 3.74446 21.3388C3.40103 20.9922 3.38996 20.4398 3.79985 20.039L5.72747 18.1543Z\" fill=\"currentColor\"/>";
    echo "<path d=\"M3.71122 11.6226C4.27622 11.6226 4.69719 12.0126 4.70827 12.5C4.71935 12.9874 4.27622 13.3774 3.71122 13.3774L0.985967 13.3774C0.409896 13.3774 -5.25086e-07 12.9874 -5.4639e-07 12.5C-5.67699e-07 12.0126 0.409896 11.6226 0.985967 11.6226L3.71122 11.6226Z\" fill=\"currentColor\"/>";
    echo "<path d=\"M7.00147 5.60009C7.41136 6.00087 7.41136 6.57497 7.06794 6.91077C6.72451 7.25739 6.13737 7.24658 5.72747 6.85661L3.79985 4.97182C3.38995 4.56022 3.38995 3.99694 3.74446 3.67204C4.09896 3.32542 4.66395 3.31461 5.07385 3.7154L7.00147 5.60009Z\" fill=\"currentColor\"/>";
    echo "<path d=\"M13.3937 0.964065L13.3937 3.6287C13.3937 4.19198 12.9948 4.59276 12.4963 4.60358C11.9978 4.61439 11.5989 4.19198 11.5989 3.6287L11.5989 0.964065C11.5989 0.400783 12.0088 -5.24923e-07 12.4963 -5.46233e-07C12.9948 -5.68022e-07 13.4047 0.400783 13.3937 0.964065Z\" fill=\"currentColor\"/>";
    echo "</svg>";
    echo "<span><span class=\"font-extrabold\">Morning</span><span class=\"font-medium\">Newsletter</span></span>";
    echo "</a>";
}
?>