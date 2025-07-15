<?php
interface SourceModule {
    public function getTitle(): string;
    public function getData(): array;
    public function getConfigFields(): array;
    public function validateConfig(array $config): bool;
}

abstract class BaseSourceModule implements SourceModule {
    protected $config;
    protected $timezone;
    
    public function __construct(array $config = [], $timezone = 'UTC') {
        $this->config = $config;
        $this->timezone = $timezone;
    }
    
    protected function formatTimestamp($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        try {
            $dateTime = new DateTime('@' . $timestamp);
            $dateTime->setTimezone(new DateTimeZone($this->timezone));
            return $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // Fallback to UTC if timezone is invalid
            return date('Y-m-d H:i:s', $timestamp);
        }
    }
    
    protected function makeHttpRequest($url, $headers = []) {
        error_log("CRYPTO DEBUG: Making HTTP request to: $url");
        error_log("CRYPTO DEBUG: Working directory: " . getcwd());
        error_log("CRYPTO DEBUG: PHP SAPI: " . php_sapi_name());
        
        // Try cURL first (more reliable for HTTPS)
        if (extension_loaded('curl')) {
            $result = $this->makeHttpRequestWithCurl($url, $headers);
            error_log("CRYPTO DEBUG: cURL request successful for: $url");
            return $result;
        }
        
        // Fallback to file_get_contents
        $result = $this->makeHttpRequestWithFileGetContents($url, $headers);
        error_log("CRYPTO DEBUG: file_get_contents request successful for: $url");
        return $result;
    }
    
    private function makeHttpRequestWithCurl($url, $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'MorningNewsletter/1.0',
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false) {
            error_log("CRYPTO DEBUG: cURL error: $error");
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode !== 200) {
            error_log("CRYPTO DEBUG: HTTP error code: $httpCode");
            throw new Exception("HTTP error: $httpCode");
        }
        
        return $response;
    }
    
    private function makeHttpRequestWithFileGetContents($url, $headers = []) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 10,
                'user_agent' => 'MorningNewsletter/1.0'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === FALSE) {
            $error = error_get_last();
            error_log("CRYPTO DEBUG: file_get_contents error: " . ($error ? $error['message'] : "Unknown error"));
            throw new Exception("Failed to fetch data from: $url" . ($error ? " - " . $error['message'] : ""));
        }
        
        return $response;
    }
    
    protected function formatDelta($current, $previous) {
        if ($previous == 0) return '';
        
        $delta = $current - $previous;
        $percentage = ($delta / $previous) * 100;
        
        $symbol = $delta >= 0 ? '↑' : '↓';
        $color = $delta >= 0 ? 'green' : 'red';
        
        return [
            'value' => $symbol . ' ' . abs($percentage),
            'color' => $color,
            'raw_delta' => $delta
        ];
    }
    
    protected function formatNumber($number, $decimals = 2) {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return number_format($number, $decimals);
    }
}