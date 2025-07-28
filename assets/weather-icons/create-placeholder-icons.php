<?php
// Create simple placeholder PNG icons for weather
// These are basic shapes that will work in all email clients

$icons = [
    'sun' => ['color' => [255, 183, 77], 'shape' => 'circle'],
    'cloud-sun' => ['color' => [156, 163, 175], 'shape' => 'cloud'],
    'cloud' => ['color' => [156, 163, 175], 'shape' => 'cloud'],
    'cloud-rain' => ['color' => [59, 130, 246], 'shape' => 'cloud'],
    'snowflake' => ['color' => [96, 165, 250], 'shape' => 'star'],
    'cloud-bolt' => ['color' => [251, 146, 60], 'shape' => 'cloud'],
    'smog' => ['color' => [156, 163, 175], 'shape' => 'lines'],
    'cloud-meatball' => ['color' => [96, 165, 250], 'shape' => 'cloud'],
    'droplet' => ['color' => [107, 114, 128], 'shape' => 'droplet'],
    'wind' => ['color' => [107, 114, 128], 'shape' => 'lines'],
    'gauge' => ['color' => [107, 114, 128], 'shape' => 'arc'],
    'temperature-half' => ['color' => [107, 114, 128], 'shape' => 'thermometer']
];

foreach ($icons as $name => $config) {
    // Large version (48x48)
    $img = imagecreatetruecolor(48, 48);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    
    $color = imagecolorallocate($img, $config['color'][0], $config['color'][1], $config['color'][2]);
    
    switch ($config['shape']) {
        case 'circle':
            // Sun - filled circle
            imagefilledellipse($img, 24, 24, 36, 36, $color);
            break;
            
        case 'cloud':
            // Cloud - simplified shape
            imagefilledellipse($img, 16, 28, 24, 18, $color);
            imagefilledellipse($img, 32, 28, 24, 18, $color);
            imagefilledellipse($img, 24, 20, 28, 20, $color);
            break;
            
        case 'star':
            // Snowflake - simple star
            imagesetthickness($img, 3);
            imageline($img, 24, 8, 24, 40, $color);
            imageline($img, 8, 24, 40, 24, $color);
            imageline($img, 12, 12, 36, 36, $color);
            imageline($img, 36, 12, 12, 36, $color);
            break;
            
        case 'lines':
            // Fog/Wind - horizontal lines
            imagesetthickness($img, 3);
            imageline($img, 8, 16, 40, 16, $color);
            imageline($img, 8, 24, 40, 24, $color);
            imageline($img, 8, 32, 40, 32, $color);
            break;
            
        case 'droplet':
            // Water droplet - teardrop shape
            imagefilledellipse($img, 24, 28, 20, 24, $color);
            $points = [24, 8, 18, 20, 30, 20];
            imagefilledpolygon($img, $points, 3, $color);
            break;
            
        case 'arc':
            // Gauge - arc
            imagesetthickness($img, 3);
            imagearc($img, 24, 24, 36, 36, 180, 0, $color);
            break;
            
        case 'thermometer':
            // Temperature - vertical bar
            imagefilledrectangle($img, 20, 8, 28, 36, $color);
            imagefilledellipse($img, 24, 36, 12, 12, $color);
            break;
    }
    
    imagepng($img, __DIR__ . '/' . $name . '.png');
    imagedestroy($img);
    
    // Small version (20x20)
    $img_small = imagecreatetruecolor(20, 20);
    imagesavealpha($img_small, true);
    $transparent = imagecolorallocatealpha($img_small, 0, 0, 0, 127);
    imagefill($img_small, 0, 0, $transparent);
    
    $color = imagecolorallocate($img_small, $config['color'][0], $config['color'][1], $config['color'][2]);
    
    switch ($config['shape']) {
        case 'circle':
            imagefilledellipse($img_small, 10, 10, 16, 16, $color);
            break;
            
        case 'cloud':
            imagefilledellipse($img_small, 7, 12, 10, 8, $color);
            imagefilledellipse($img_small, 13, 12, 10, 8, $color);
            imagefilledellipse($img_small, 10, 8, 12, 8, $color);
            break;
            
        case 'star':
            imagesetthickness($img_small, 2);
            imageline($img_small, 10, 2, 10, 18, $color);
            imageline($img_small, 2, 10, 18, 10, $color);
            imageline($img_small, 4, 4, 16, 16, $color);
            imageline($img_small, 16, 4, 4, 16, $color);
            break;
            
        case 'lines':
            imagesetthickness($img_small, 2);
            imageline($img_small, 4, 6, 16, 6, $color);
            imageline($img_small, 4, 10, 16, 10, $color);
            imageline($img_small, 4, 14, 16, 14, $color);
            break;
            
        case 'droplet':
            imagefilledellipse($img_small, 10, 12, 8, 10, $color);
            $points = [10, 4, 7, 8, 13, 8];
            imagefilledpolygon($img_small, $points, 3, $color);
            break;
            
        case 'arc':
            imagesetthickness($img_small, 2);
            imagearc($img_small, 10, 10, 16, 16, 180, 0, $color);
            break;
            
        case 'thermometer':
            imagefilledrectangle($img_small, 8, 4, 12, 14, $color);
            imagefilledellipse($img_small, 10, 14, 6, 6, $color);
            break;
    }
    
    imagepng($img_small, __DIR__ . '/' . $name . '-small.png');
    imagedestroy($img_small);
}

echo "Icons created successfully!\n";