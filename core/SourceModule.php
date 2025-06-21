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
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 10
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === FALSE) {
            throw new Exception("Failed to fetch data from: $url");
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