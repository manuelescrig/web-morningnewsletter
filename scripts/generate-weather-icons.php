<?php
/**
 * Script to generate PNG weather icons from SVG definitions
 * Run this script to create the static PNG images for email compatibility
 */

// Weather icon SVG definitions
$icons = [
    'sun' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>',
        'size' => 48
    ],
    'cloud-sun' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 1.5v2m-6.364.636l1.414 1.414M1.5 10.5h2m.636 6.364l1.414-1.414"></path><circle cx="10.5" cy="10.5" r="3.5" stroke="#f59e0b"></circle><path d="M16 16.13A4 4 0 0014.11 8a6 6 0 10-6.09 9.89"></path><path d="M18 10h.01M22 10a4 4 0 01-4 4H8a4 4 0 110-8c.085 0 .17.003.254.009A6 6 0 1116 16.13"></path></svg>',
        'size' => 48
    ],
    'cloud' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"></path></svg>',
        'size' => 48
    ],
    'cloud-rain' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="13" x2="16" y2="21" stroke="#3b82f6"></line><line x1="8" y1="13" x2="8" y2="21" stroke="#3b82f6"></line><line x1="12" y1="15" x2="12" y2="23" stroke="#3b82f6"></line><path d="M20 16.58A5 5 0 0018 7h-1.26A8 8 0 104 15.25"></path></svg>',
        'size' => 48
    ],
    'snowflake' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"></line><line x1="22" y1="12" x2="2" y2="12"></line><path d="M17 7l-5 5-5-5m10 10l-5-5-5 5"></path></svg>',
        'size' => 48
    ],
    'cloud-bolt' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 16.9A5 5 0 0018 7h-1.26a8 8 0 10-11.62 9"></path><polyline points="13 11 9 17 15 17 11 23" stroke="#f59e0b"></polyline></svg>',
        'size' => 48
    ],
    'smog' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>',
        'size' => 48
    ],
    'cloud-meatball' => [
        'svg' => '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 17.58A5 5 0 0018 8h-1.26A8 8 0 104 16.25"></path><line x1="8" y1="16" x2="8.01" y2="16" stroke="#60a5fa" stroke-width="4"></line><line x1="8" y1="20" x2="8.01" y2="20" stroke="#60a5fa" stroke-width="4"></line><line x1="12" y1="18" x2="12.01" y2="18" stroke="#60a5fa" stroke-width="4"></line><line x1="12" y1="22" x2="12.01" y2="22" stroke="#60a5fa" stroke-width="4"></line><line x1="16" y1="16" x2="16.01" y2="16" stroke="#60a5fa" stroke-width="4"></line><line x1="16" y1="20" x2="16.01" y2="20" stroke="#60a5fa" stroke-width="4"></line></svg>',
        'size' => 48
    ],
    'droplet' => [
        'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"></path></svg>',
        'size' => 20
    ],
    'wind' => [
        'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.59 4.59A2 2 0 1111 8H2m10.59 11.41A2 2 0 1014 16H2m15.73-8.27A2.5 2.5 0 1119.5 12H2"></path></svg>',
        'size' => 20
    ],
    'gauge' => [
        'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>',
        'size' => 20
    ],
    'temperature-half' => [
        'svg' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 00-5 0v11.26a4.5 4.5 0 105 0z"></path></svg>',
        'size' => 20
    ]
];

$outputDir = __DIR__ . '/../assets/weather-icons/';

// Check if ImageMagick is available
if (!extension_loaded('imagick')) {
    echo "Error: ImageMagick PHP extension is not installed.\n";
    echo "To install on Mac: brew install imagemagick pkg-config\n";
    echo "Then: pecl install imagick\n";
    echo "For other systems, please install ImageMagick and the PHP imagick extension.\n";
    exit(1);
}

echo "Generating weather icon PNG images...\n\n";

foreach ($icons as $name => $iconData) {
    $outputFile = $outputDir . $name . '.png';
    
    try {
        // Add XML declaration and proper SVG namespace
        $svgContent = '<?xml version="1.0" encoding="UTF-8"?>' . $iconData['svg'];
        
        $imagick = new Imagick();
        $imagick->setBackgroundColor(new ImagickPixel('transparent'));
        $imagick->readImageBlob($svgContent);
        $imagick->setImageFormat('png32');
        
        // Scale to desired size with antialiasing
        $size = $iconData['size'];
        $imagick->resizeImage($size * 2, $size * 2, Imagick::FILTER_LANCZOS, 1);
        $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
        
        // Set high quality
        $imagick->setImageCompressionQuality(100);
        
        // Save the PNG
        $imagick->writeImage($outputFile);
        $imagick->destroy();
        
        echo "✓ Generated: $name.png ($size x $size px)\n";
    } catch (Exception $e) {
        echo "✗ Failed to generate $name.png: " . $e->getMessage() . "\n";
    }
}

echo "\nDone! Icons saved to: $outputDir\n";

// Also create high-resolution versions for retina displays
echo "\nGenerating @2x versions for retina displays...\n";

foreach ($icons as $name => $iconData) {
    $outputFile = $outputDir . $name . '@2x.png';
    
    try {
        $svgContent = '<?xml version="1.0" encoding="UTF-8"?>' . $iconData['svg'];
        
        $imagick = new Imagick();
        $imagick->setBackgroundColor(new ImagickPixel('transparent'));
        $imagick->readImageBlob($svgContent);
        $imagick->setImageFormat('png32');
        
        // Scale to 2x size
        $size = $iconData['size'] * 2;
        $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
        
        // Set high quality
        $imagick->setImageCompressionQuality(100);
        
        // Save the PNG
        $imagick->writeImage($outputFile);
        $imagick->destroy();
        
        echo "✓ Generated: $name@2x.png ($size x $size px)\n";
    } catch (Exception $e) {
        echo "✗ Failed to generate $name@2x.png: " . $e->getMessage() . "\n";
    }
}

echo "\nAll icons generated successfully!\n";