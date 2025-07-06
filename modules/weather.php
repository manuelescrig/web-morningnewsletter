<?php
require_once __DIR__ . '/../core/SourceModule.php';

class WeatherModule extends BaseSourceModule {
    public function getTitle(): string {
        $location = $this->config['location'] ?? 'Weather';
        return "Weather - $location";
    }
    
    public function getData(): array {
        try {
            $latitude = $this->config['latitude'] ?? '';
            $longitude = $this->config['longitude'] ?? '';
            $location = $this->config['location'] ?? 'Unknown Location';
            
            if (empty($latitude) || empty($longitude)) {
                throw new Exception('Location coordinates required');
            }
            
            // Validate coordinates
            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                throw new Exception('Invalid coordinates format');
            }
            
            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                throw new Exception('Coordinates out of valid range');
            }
            
            // Get current weather from MET Norway API
            $weatherUrl = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=$latitude&lon=$longitude";
            $headers = [
                'User-Agent: MorningNewsletter/1.0 (github.com/your-repo/morning-newsletter)'
            ];
            
            $weatherResponse = $this->makeHttpRequest($weatherUrl, $headers);
            $weatherData = json_decode($weatherResponse, true);
            
            if (!$weatherData || !isset($weatherData['properties']['timeseries'])) {
                throw new Exception('Invalid response from weather API');
            }
            
            $timeseries = $weatherData['properties']['timeseries'];
            if (empty($timeseries)) {
                throw new Exception('No weather data available');
            }
            
            // Get current conditions (first entry)
            $current = $timeseries[0]['data']['instant']['details'];
            $currentSymbol = $timeseries[0]['data']['next_1_hours']['summary']['symbol_code'] ?? 'unknown';
            
            $temp = round($current['air_temperature']);
            $humidity = round($current['relative_humidity']);
            $windSpeed = round($current['wind_speed'] * 3.6, 1); // Convert m/s to km/h
            $pressure = round($current['air_pressure_at_sea_level'] ?? 0);
            $windDirection = $this->getWindDirection($current['wind_from_direction'] ?? 0);
            $weatherIcon = $this->getWeatherEmoji($currentSymbol);
            $description = $this->getWeatherDescription($currentSymbol);
            
            $result = [
                [
                    'label' => 'Current Temperature',
                    'value' => "{$weatherIcon} {$temp}°C",
                    'delta' => null
                ],
                [
                    'label' => 'Conditions',
                    'value' => $description,
                    'delta' => null
                ],
                [
                    'label' => 'Humidity',
                    'value' => "💧 {$humidity}%",
                    'delta' => null
                ],
                [
                    'label' => 'Wind',
                    'value' => "💨 {$windSpeed} km/h {$windDirection}",
                    'delta' => null
                ]
            ];
            
            // Add pressure if available
            if ($pressure > 0) {
                $result[] = [
                    'label' => 'Pressure',
                    'value' => "🌡️ {$pressure} hPa",
                    'delta' => null
                ];
            }
            
            // Get today's high/low from next 24 hours
            $todayTemps = [];
            $next24Hours = array_slice($timeseries, 0, 24);
            
            foreach ($next24Hours as $entry) {
                if (isset($entry['data']['instant']['details']['air_temperature'])) {
                    $todayTemps[] = $entry['data']['instant']['details']['air_temperature'];
                }
            }
            
            if (!empty($todayTemps)) {
                $high = round(max($todayTemps));
                $low = round(min($todayTemps));
                $result[] = [
                    'label' => 'Today\'s Range',
                    'value' => "📊 {$low}°C - {$high}°C",
                    'delta' => null
                ];
            }
            
            // Tomorrow's forecast (24-48 hours ahead)
            $tomorrowEntries = array_slice($timeseries, 24, 24);
            if (!empty($tomorrowEntries)) {
                $tomorrowTemps = [];
                $tomorrowSymbol = null;
                
                foreach ($tomorrowEntries as $entry) {
                    if (isset($entry['data']['instant']['details']['air_temperature'])) {
                        $tomorrowTemps[] = $entry['data']['instant']['details']['air_temperature'];
                    }
                    if (!$tomorrowSymbol && isset($entry['data']['next_1_hours']['summary']['symbol_code'])) {
                        $tomorrowSymbol = $entry['data']['next_1_hours']['summary']['symbol_code'];
                    }
                }
                
                if (!empty($tomorrowTemps) && $tomorrowSymbol) {
                    $tomorrowHigh = round(max($tomorrowTemps));
                    $tomorrowLow = round(min($tomorrowTemps));
                    $tomorrowEmoji = $this->getWeatherEmoji($tomorrowSymbol);
                    $result[] = [
                        'label' => 'Tomorrow',
                        'value' => "{$tomorrowEmoji} {$tomorrowLow}°C - {$tomorrowHigh}°C",
                        'delta' => null
                    ];
                }
            }
            
            // Add location info
            $result[] = [
                'label' => 'Location',
                'value' => "📍 $location",
                'delta' => null
            ];
            
            return $result;
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            error_log('Weather module error: ' . $errorMessage);
            error_log('Weather module config: ' . print_r($this->config, true));
            
            // Provide more specific error messages
            $displayMessage = 'Data unavailable';
            if (strpos($errorMessage, 'coordinates required') !== false) {
                $displayMessage = 'Location coordinates required';
            } elseif (strpos($errorMessage, 'Invalid coordinates') !== false) {
                $displayMessage = 'Invalid location coordinates';
            } elseif (strpos($errorMessage, 'out of valid range') !== false) {
                $displayMessage = 'Coordinates out of range';
            } elseif (strpos($errorMessage, 'HTTP error: 429') !== false) {
                $displayMessage = 'Rate limit exceeded';
            } elseif (strpos($errorMessage, 'cURL error') !== false || strpos($errorMessage, 'Failed to fetch') !== false) {
                $displayMessage = 'Network error';
            }
            
            return [
                [
                    'label' => 'Weather',
                    'value' => $displayMessage,
                    'delta' => null
                ],
                [
                    'label' => 'Error Details',
                    'value' => $errorMessage,
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return [
            [
                'name' => 'location',
                'type' => 'text',
                'label' => 'Location Name',
                'required' => true,
                'description' => 'Display name for your location (e.g., "New York", "London", "Tokyo")',
                'default' => 'New York'
            ],
            [
                'name' => 'latitude',
                'type' => 'number',
                'label' => 'Latitude',
                'required' => true,
                'description' => 'Latitude coordinate (e.g., 40.7128 for New York)',
                'default' => '40.7128'
            ],
            [
                'name' => 'longitude',
                'type' => 'number',
                'label' => 'Longitude',
                'required' => true,
                'description' => 'Longitude coordinate (e.g., -74.0060 for New York)',
                'default' => '-74.0060'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        // Check if required fields are present and not empty
        $hasLocation = isset($config['location']) && trim($config['location']) !== '';
        $hasLatitude = isset($config['latitude']) && trim($config['latitude']) !== '';
        $hasLongitude = isset($config['longitude']) && trim($config['longitude']) !== '';
        
        if (!$hasLocation || !$hasLatitude || !$hasLongitude) {
            return false;
        }
        
        // Validate coordinates are numeric and in valid range
        $lat = floatval($config['latitude']);
        $lon = floatval($config['longitude']);
        
        return ($lat >= -90 && $lat <= 90) && ($lon >= -180 && $lon <= 180);
    }
    
    private function getWeatherEmoji(string $symbolCode): string {
        // MET Norway symbol codes to emoji mapping
        if (strpos($symbolCode, 'clearsky') !== false) {
            return '☀️';
        } elseif (strpos($symbolCode, 'fair') !== false || strpos($symbolCode, 'partlycloudy') !== false) {
            return '⛅';
        } elseif (strpos($symbolCode, 'cloudy') !== false) {
            return '☁️';
        } elseif (strpos($symbolCode, 'rain') !== false) {
            return '🌧️';
        } elseif (strpos($symbolCode, 'sleet') !== false) {
            return '🌨️';
        } elseif (strpos($symbolCode, 'snow') !== false) {
            return '❄️';
        } elseif (strpos($symbolCode, 'fog') !== false) {
            return '🌫️';
        } elseif (strpos($symbolCode, 'thunderstorm') !== false) {
            return '⛈️';
        } else {
            return '🌤️';
        }
    }
    
    private function getWeatherDescription(string $symbolCode): string {
        // Convert symbol code to human readable description
        $symbolCode = strtolower($symbolCode);
        
        if (strpos($symbolCode, 'clearsky') !== false) {
            return 'Clear sky';
        } elseif (strpos($symbolCode, 'fair') !== false) {
            return 'Fair weather';
        } elseif (strpos($symbolCode, 'partlycloudy') !== false) {
            return 'Partly cloudy';
        } elseif (strpos($symbolCode, 'cloudy') !== false) {
            return 'Cloudy';
        } elseif (strpos($symbolCode, 'lightrain') !== false) {
            return 'Light rain';
        } elseif (strpos($symbolCode, 'rain') !== false) {
            return 'Rain';
        } elseif (strpos($symbolCode, 'heavyrain') !== false) {
            return 'Heavy rain';
        } elseif (strpos($symbolCode, 'lightsleet') !== false) {
            return 'Light sleet';
        } elseif (strpos($symbolCode, 'sleet') !== false) {
            return 'Sleet';
        } elseif (strpos($symbolCode, 'heavysleet') !== false) {
            return 'Heavy sleet';
        } elseif (strpos($symbolCode, 'lightsnow') !== false) {
            return 'Light snow';
        } elseif (strpos($symbolCode, 'snow') !== false) {
            return 'Snow';
        } elseif (strpos($symbolCode, 'heavysnow') !== false) {
            return 'Heavy snow';
        } elseif (strpos($symbolCode, 'fog') !== false) {
            return 'Fog';
        } elseif (strpos($symbolCode, 'thunderstorm') !== false) {
            return 'Thunderstorm';
        } else {
            return 'Variable conditions';
        }
    }
    
    private function getWindDirection(float $degrees): string {
        $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        $index = round($degrees / 22.5) % 16;
        return $directions[$index];
    }
}