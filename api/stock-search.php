<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Search query required']);
    exit;
}

try {
    // Using Yahoo Finance autocomplete API
    $apiUrl = "https://query1.finance.yahoo.com/v1/finance/search?q=" . urlencode($query) . "&lang=en-US&region=US&quotesCount=10&newsCount=0";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch stock data');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['quotes'])) {
        throw new Exception('Invalid response from API');
    }
    
    $results = [];
    
    // Filter and format results
    foreach ($data['quotes'] as $quote) {
        // Only include stocks (not futures, currencies, etc.)
        if (isset($quote['quoteType']) && in_array($quote['quoteType'], ['EQUITY', 'ETF'])) {
            $results[] = [
                'symbol' => $quote['symbol'],
                'name' => $quote['shortname'] ?? $quote['longname'] ?? $quote['symbol'],
                'exchange' => $quote['exchange'] ?? '',
                'type' => $quote['quoteType'] ?? 'EQUITY'
            ];
        }
    }
    
    // Limit results
    $results = array_slice($results, 0, 10);
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log('Stock search error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to search stocks',
        'debug' => $e->getMessage()
    ]);
}