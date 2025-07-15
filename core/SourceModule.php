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
        error_log("Making HTTP request to: $url");
        
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Try cURL first (more reliable for HTTPS)
                if (extension_loaded('curl')) {
                    return $this->makeHttpRequestWithCurl($url, $headers);
                }
                
                // Fallback to file_get_contents
                return $this->makeHttpRequestWithFileGetContents($url, $headers);
                
            } catch (Exception $e) {
                error_log("HTTP request attempt $attempt failed for $url: " . $e->getMessage());
                
                if ($attempt === $maxRetries) {
                    throw $e; // Re-throw the exception if all retries failed
                }
                
                // Wait before retrying
                sleep($retryDelay);
                $retryDelay *= 2; // Exponential backoff
            }
        }
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
            CURLOPT_USERAGENT => 'MorningNewsletter/1.0 (https://morningnewsletter.com)',
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode !== 200) {
            if ($httpCode === 429) {
                throw new Exception("Rate limit exceeded (HTTP 429). Please wait before making more requests.");
            }
            throw new Exception("HTTP error: $httpCode");
        }
        
        error_log("HTTP request successful for: $url");
        return $response;
    }
    
    private function makeHttpRequestWithFileGetContents($url, $headers = []) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 10,
                'user_agent' => 'MorningNewsletter/1.0 (https://morningnewsletter.com)'
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
            throw new Exception("Failed to fetch data from: $url" . ($error ? " - " . $error['message'] : ""));
        }
        
        error_log("HTTP request successful for: $url");
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