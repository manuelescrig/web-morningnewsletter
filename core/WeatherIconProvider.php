<?php

class WeatherIconProvider {
    /**
     * Get absolute URL for weather icon image
     * This ensures compatibility with all email clients
     */
    public static function getIconUrl($iconClass, $size = 'large') {
        // Get the base URL from server variables or use a fallback
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'morningnewsletter.com';
        $baseUrl = $protocol . '://' . $host;
        
        // For email sending via cron, we need to use a fixed URL
        if (empty($_SERVER['HTTP_HOST'])) {
            $baseUrl = 'https://morningnewsletter.com'; // Replace with your actual domain
        }
        
        // Map icon classes to image files
        $iconMap = [
            'fa-sun' => 'sun',
            'fa-cloud-sun' => 'cloud-sun',
            'fa-cloud' => 'cloud',
            'fa-cloud-rain' => 'cloud-rain',
            'fa-snowflake' => 'snowflake',
            'fa-cloud-bolt' => 'cloud-bolt',
            'fa-smog' => 'smog',
            'fa-cloud-meatball' => 'cloud-meatball',
            'fa-droplet' => 'droplet',
            'fa-wind' => 'wind',
            'fa-gauge' => 'gauge',
            'fa-temperature-half' => 'temperature-half'
        ];
        
        $iconName = $iconMap[$iconClass] ?? 'cloud';
        
        // For small icons, append -small to filename
        if ($size === 'small') {
            $iconName .= '-small';
        }
        
        // Return absolute URL to icon
        return $baseUrl . '/assets/weather-icons/' . $iconName . '.png';
    }
    
    /**
     * Get emoji fallback for weather conditions
     * Use this as alt text or when images fail to load
     */
    public static function getEmojiIcon($iconClass) {
        $emojiMap = [
            'fa-sun' => '☀️',
            'fa-cloud-sun' => '⛅',
            'fa-cloud' => '☁️',
            'fa-cloud-rain' => '🌧️',
            'fa-snowflake' => '❄️',
            'fa-cloud-bolt' => '⛈️',
            'fa-smog' => '🌫️',
            'fa-cloud-meatball' => '🌨️',
            'fa-droplet' => '💧',
            'fa-wind' => '💨',
            'fa-gauge' => '📊',
            'fa-temperature-half' => '🌡️'
        ];
        
        return $emojiMap[$iconClass] ?? '🌤️';
    }
    
    /**
     * Get simple HTML-only weather icon
     * This uses HTML entities for maximum email client compatibility
     */
    public static function getHtmlIcon($iconClass, $size = 'large') {
        // Use HTML entities and simple styling for maximum compatibility
        
        if ($size === 'small') {
            // Small icons for columns - slightly bigger at 24px
            $htmlIcons = [
                'fa-sun' => '<span style="color: #f59e0b; font-size: 24px; display: inline-block;">&#9728;</span>', // ☀
                'fa-cloud-sun' => '<span style="color: #9ca3af; font-size: 24px; display: inline-block;">&#9925;</span>', // ⛅
                'fa-cloud' => '<span style="color: #9ca3af; font-size: 24px; display: inline-block;">&#9729;</span>', // ☁
                'fa-cloud-rain' => '<span style="color: #3b82f6; font-size: 24px; display: inline-block;">&#127783;</span>', // 🌧
                'fa-snowflake' => '<span style="color: #60a5fa; font-size: 24px; display: inline-block;">&#10052;</span>', // ❄
                'fa-cloud-bolt' => '<span style="color: #f59e0b; font-size: 24px; display: inline-block;">&#9889;</span>', // ⚡
                'fa-smog' => '<span style="color: #9ca3af; font-size: 24px; display: inline-block;">&#127787;</span>', // 🌫
                'fa-cloud-meatball' => '<span style="color: #60a5fa; font-size: 24px; display: inline-block;">&#127784;</span>', // 🌨
                'fa-droplet' => '<span style="color: #6b7280; font-size: 24px; display: inline-block;">&#128167;</span>', // 💧
                'fa-wind' => '<span style="color: #6b7280; font-size: 24px; display: inline-block;">&#128168;</span>', // 💨
                'fa-gauge' => '<span style="color: #6b7280; font-size: 24px; display: inline-block;">&#9673;</span>', // ◉
                'fa-temperature-half' => '<span style="color: #6b7280; font-size: 24px; display: inline-block;">&#127777;</span>' // 🌡
            ];
            
            return $htmlIcons[$iconClass] ?? '<span style="color: #9ca3af; font-size: 24px; display: inline-block;">&#9729;</span>';
        } else {
            // Large icons for main weather display
            $htmlIcons = [
                'fa-sun' => '<span style="color: #f59e0b; font-size: 36px;">&#9728;</span>', // ☀
                'fa-cloud-sun' => '<span style="color: #9ca3af; font-size: 36px;">&#9925;</span>', // ⛅
                'fa-cloud' => '<span style="color: #9ca3af; font-size: 36px;">&#9729;</span>', // ☁
                'fa-cloud-rain' => '<span style="color: #3b82f6; font-size: 36px;">&#127783;</span>', // 🌧
                'fa-snowflake' => '<span style="color: #60a5fa; font-size: 36px;">&#10052;</span>', // ❄
                'fa-cloud-bolt' => '<span style="color: #f59e0b; font-size: 36px;">&#9889;</span>', // ⚡
                'fa-smog' => '<span style="color: #9ca3af; font-size: 36px;">&#127787;</span>', // 🌫
                'fa-cloud-meatball' => '<span style="color: #60a5fa; font-size: 36px;">&#127784;</span>' // 🌨
            ];
            
            return $htmlIcons[$iconClass] ?? '<span style="color: #9ca3af; font-size: 36px;">&#9729;</span>';
        }
    }
}