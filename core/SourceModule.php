<?php
interface SourceModule {
    public function getTitle(): string;
    public function getData(): array;
    public function getConfigFields(): array;
    public function validateConfig(array $config): bool;
}

abstract class BaseSourceModule implements SourceModule {
    protected $config;
    
    public function __construct(array $config = []) {
        $this->config = $config;
    }
    
    protected function makeHttpRequest($url, $headers = []) {
        // Try cURL first (more reliable for HTTPS)
        if (extension_loaded('curl')) {
            return $this->makeHttpRequestWithCurl($url, $headers);
        }
        
        // Fallback to file_get_contents
        return $this->makeHttpRequestWithFileGetContents($url, $headers);
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
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode !== 200) {
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