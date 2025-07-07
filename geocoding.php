<?php
/**
 * Geocoding API - Root directory version
 * Temporary workaround for server configuration issues
 */

// Clean output buffer to ensure only JSON is returned
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$query = $_GET['q'] ?? '';

// Debug logging
error_log("Geocoding API (root) called: query='$query', method=" . $_SERVER['REQUEST_METHOD']);

if (empty($query) || strlen(trim($query)) < 2) {
    error_log("Geocoding API (root): Query too short");
    ob_clean();
    echo json_encode(['error' => 'Query too short', 'results' => []]);
    exit;
}

try {
    // Use Nominatim (OpenStreetMap) geocoding service
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => trim($query),
        'format' => 'json',
        'limit' => 8,
        'addressdetails' => 1,
        'extratags' => 0,
        'namedetails' => 0
    ]);

    // Use cURL for better error handling
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'MorningNewsletter/1.0 (https://morningnewsletter.com; hello@morningnewsletter.com)',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en-US,en;q=0.9'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception("Network error: $error");
    }
    
    if ($httpCode === 429) {
        throw new Exception("Rate limit exceeded. Please wait a moment and try again.");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Service temporarily unavailable (HTTP $httpCode)");
    }

    $data = json_decode($response, true);
    
    if (!is_array($data)) {
        throw new Exception('Invalid response from geocoding service');
    }

    // Format the results
    $results = [];
    foreach ($data as $item) {
        $displayName = $item['display_name'] ?? '';
        $lat = $item['lat'] ?? '';
        $lon = $item['lon'] ?? '';
        
        if (empty($displayName) || empty($lat) || empty($lon)) {
            continue;
        }

        // Extract useful parts for a cleaner display name
        $address = $item['address'] ?? [];
        $nameComponents = [];
        
        if (!empty($address['city'])) {
            $nameComponents[] = $address['city'];
        } elseif (!empty($address['town'])) {
            $nameComponents[] = $address['town'];
        } elseif (!empty($address['village'])) {
            $nameComponents[] = $address['village'];
        }
        
        if (!empty($address['state'])) {
            $nameComponents[] = $address['state'];
        }
        
        if (!empty($address['country'])) {
            $nameComponents[] = $address['country'];
        }
        
        $cleanName = !empty($nameComponents) ? implode(', ', $nameComponents) : $displayName;
        
        // If clean name is too long, truncate smartly
        if (strlen($cleanName) > 60) {
            $parts = explode(', ', $cleanName);
            if (count($parts) > 2) {
                $cleanName = $parts[0] . ', ' . $parts[count($parts) - 1];
            }
        }

        $results[] = [
            'name' => $cleanName,
            'display_name' => $displayName,
            'latitude' => floatval($lat),
            'longitude' => floatval($lon),
            'type' => $item['type'] ?? 'location',
            'importance' => $item['importance'] ?? 0
        ];
    }

    // Sort by importance (higher is better)
    usort($results, function($a, $b) {
        return $b['importance'] <=> $a['importance'];
    });

    // Clear any output and send clean JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    error_log("Geocoding API (root) error: $errorMsg | Query: $query");
    
    // Return appropriate HTTP status code
    if (strpos($errorMsg, 'Rate limit') !== false) {
        http_response_code(429);
    } else {
        http_response_code(500);
    }
    
    // Clear any output and send clean JSON
    ob_clean();
    echo json_encode([
        'error' => 'Failed to search locations. Please try again.',
        'debug' => $errorMsg,
        'results' => []
    ]);
}
?>