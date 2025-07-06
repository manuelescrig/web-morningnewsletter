<?php
// Handle both direct access and URL rewriting
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isDirectAccess = strpos($requestUri, 'geocoding.php') !== false;

// If this is a direct access, set the query parameter
if ($isDirectAccess && !isset($_GET['q'])) {
    // Extract query from URL if present
    $path = parse_url($requestUri, PHP_URL_PATH);
    if (preg_match('/geocoding\.php\?q=([^&]+)/', $requestUri, $matches)) {
        $_GET['q'] = urldecode($matches[1]);
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$query = $_GET['q'] ?? '';

if (empty($query) || strlen(trim($query)) < 2) {
    echo json_encode(['error' => 'Query too short', 'results' => []]);
    exit;
}

// Simple caching mechanism
function getCachePath($query) {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        if (!mkdir($cacheDir, 0755, true)) {
            error_log('Failed to create cache directory: ' . $cacheDir);
            return null;
        }
    }
    
    if (!is_writable($cacheDir)) {
        error_log('Cache directory not writable: ' . $cacheDir);
        return null;
    }
    
    return $cacheDir . '/geocoding_' . md5(strtolower(trim($query))) . '.json';
}

function getCachedResult($query) {
    $cacheFile = getCachePath($query);
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data && isset($data['timestamp']) && (time() - $data['timestamp']) < 86400) { // 24 hours
            return $data['results'];
        }
    }
    return null;
}

function cacheResult($query, $results) {
    $cacheFile = getCachePath($query);
    if ($cacheFile === null) {
        error_log('Cannot cache result - cache path is null');
        return;
    }
    
    $data = [
        'timestamp' => time(),
        'results' => $results
    ];
    
    if (file_put_contents($cacheFile, json_encode($data)) === false) {
        error_log('Failed to write cache file: ' . $cacheFile);
    }
}

try {
    error_log('Geocoding API called with query: ' . $query);
    
    // Check cache first
    $cachedResults = getCachedResult($query);
    if ($cachedResults !== null) {
        error_log('Returning cached results for: ' . $query);
        echo json_encode([
            'success' => true,
            'results' => $cachedResults,
            'count' => count($cachedResults),
            'cached' => true
        ]);
        exit;
    }

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
        // Use a more compliant User-Agent for Nominatim
        CURLOPT_USERAGENT => 'MorningNewsletter/1.0 (https://morningnewsletter.com; contact@morningnewsletter.com)',
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

    // Cache the results
    cacheResult($query, $results);

    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);

} catch (Exception $e) {
    error_log('Geocoding API error: ' . $e->getMessage());
    
    // Return appropriate HTTP status code
    if (strpos($e->getMessage(), 'Rate limit') !== false) {
        http_response_code(429);
    } else {
        http_response_code(500);
    }
    
    echo json_encode([
        'error' => 'Failed to search locations. Please try again.',
        'results' => []
    ]);
}
?>