<?php

class SvgToImageConverter {
    private $cacheDir;
    private $webPath;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../assets/weather-icons-cache/';
        $this->webPath = '/assets/weather-icons-cache/';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Convert SVG string to PNG image and return the URL
     * 
     * @param string $svgContent The SVG content
     * @param string $fileName Base name for the output file
     * @param int $width Desired width (will maintain aspect ratio)
     * @param int $height Desired height (will maintain aspect ratio)
     * @return string URL to the generated PNG image
     */
    public function svgToPng($svgContent, $fileName, $width = 48, $height = 48) {
        // Generate a hash of the SVG content for caching
        $hash = md5($svgContent . $width . $height);
        $outputFile = $this->cacheDir . $fileName . '_' . $hash . '.png';
        $webUrl = $this->webPath . $fileName . '_' . $hash . '.png';
        
        // Check if cached version exists
        if (file_exists($outputFile)) {
            return $webUrl;
        }
        
        // Check if ImageMagick is available
        if (extension_loaded('imagick')) {
            return $this->convertWithImagick($svgContent, $outputFile, $webUrl, $width, $height);
        }
        
        // Fallback to GD library if available
        if (extension_loaded('gd')) {
            return $this->convertWithGD($svgContent, $outputFile, $webUrl, $width, $height);
        }
        
        // If no image libraries available, save SVG as fallback
        return $this->saveSvgAsFallback($svgContent, $fileName);
    }
    
    /**
     * Convert SVG using ImageMagick
     */
    private function convertWithImagick($svgContent, $outputFile, $webUrl, $width, $height) {
        try {
            $imagick = new Imagick();
            $imagick->setBackgroundColor(new ImagickPixel('transparent'));
            $imagick->readImageBlob($svgContent);
            $imagick->setImageFormat('png');
            
            // Calculate dimensions maintaining aspect ratio
            $svgWidth = $imagick->getImageWidth();
            $svgHeight = $imagick->getImageHeight();
            
            if ($svgWidth > 0 && $svgHeight > 0) {
                $ratio = min($width / $svgWidth, $height / $svgHeight);
                $newWidth = (int)($svgWidth * $ratio);
                $newHeight = (int)($svgHeight * $ratio);
                $imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            }
            
            // Set higher quality for better rendering
            $imagick->setImageCompressionQuality(95);
            $imagick->writeImage($outputFile);
            $imagick->destroy();
            
            return $webUrl;
        } catch (Exception $e) {
            error_log('ImageMagick SVG conversion failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Convert SVG using GD library (basic implementation)
     * Note: GD has limited SVG support, so this is a fallback
     */
    private function convertWithGD($svgContent, $outputFile, $webUrl, $width, $height) {
        // For GD, we'll need to use a workaround since it doesn't directly support SVG
        // One option is to save as SVG and use it directly, or implement a basic renderer
        // For now, we'll save the SVG as a fallback
        return $this->saveSvgAsFallback($svgContent, basename($outputFile, '.png'));
    }
    
    /**
     * Save SVG file as fallback when no conversion library is available
     */
    private function saveSvgAsFallback($svgContent, $fileName) {
        $svgFile = $this->cacheDir . $fileName . '.svg';
        $webUrl = $this->webPath . $fileName . '.svg';
        
        if (!file_exists($svgFile)) {
            file_put_contents($svgFile, $svgContent);
        }
        
        return $webUrl;
    }
    
    /**
     * Get pre-rendered weather icon images
     * These are static PNG images that we'll include in the assets folder
     */
    public function getWeatherIconUrl($iconClass) {
        // Map icon classes to pre-made PNG images
        $iconMap = [
            'fa-sun' => 'sun.png',
            'fa-cloud-sun' => 'cloud-sun.png',
            'fa-cloud' => 'cloud.png',
            'fa-cloud-rain' => 'cloud-rain.png',
            'fa-snowflake' => 'snowflake.png',
            'fa-cloud-bolt' => 'cloud-bolt.png',
            'fa-smog' => 'smog.png',
            'fa-cloud-meatball' => 'cloud-meatball.png',
            'fa-droplet' => 'droplet.png',
            'fa-wind' => 'wind.png',
            'fa-gauge' => 'gauge.png',
            'fa-temperature-half' => 'temperature-half.png'
        ];
        
        $fileName = $iconMap[$iconClass] ?? 'cloud.png';
        return '/assets/weather-icons/' . $fileName;
    }
    
    /**
     * Clean up old cached files
     */
    public function cleanCache($maxAgeInDays = 7) {
        $files = glob($this->cacheDir . '*.png');
        $cutoffTime = time() - ($maxAgeInDays * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}