<?php
require_once __DIR__ . '/../core/SourceModule.php';

class WeatherModule extends BaseSourceModule {
    public function getTitle(): string {
        $city = $this->config['city'] ?? 'Weather';
        return "Weather - $city";
    }
    
    public function getData(): array {
        try {
            $apiKey = $this->config['api_key'] ?? '';
            $city = $this->config['city'] ?? 'New York';
            
            if (empty($apiKey)) {
                throw new Exception('OpenWeatherMap API key required');
            }
            
            // Current weather
            $currentUrl = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&units=metric";
            $currentResponse = $this->makeHttpRequest($currentUrl);
            $currentData = json_decode($currentResponse, true);
            
            if (!$currentData || $currentData['cod'] !== 200) {
                throw new Exception('Weather API error: ' . ($currentData['message'] ?? 'Unknown error'));
            }
            
            // Forecast
            $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q=$city&appid=$apiKey&units=metric&cnt=8";
            $forecastResponse = $this->makeHttpRequest($forecastUrl);
            $forecastData = json_decode($forecastResponse, true);
            
            $temp = round($currentData['main']['temp']);
            $feelsLike = round($currentData['main']['feels_like']);
            $description = ucfirst($currentData['weather'][0]['description']);
            $humidity = $currentData['main']['humidity'];
            $windSpeed = round($currentData['wind']['speed'] * 3.6, 1); // Convert m/s to km/h
            $pressure = $currentData['main']['pressure'] ?? null;
            $visibility = isset($currentData['visibility']) ? round($currentData['visibility'] / 1000, 1) : null;
            $sunrise = isset($currentData['sys']['sunrise']) ? date('H:i', $currentData['sys']['sunrise']) : null;
            $sunset = isset($currentData['sys']['sunset']) ? date('H:i', $currentData['sys']['sunset']) : null;
            $weatherIcon = $this->getWeatherEmoji($currentData['weather'][0]['main'] ?? '');
            
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
                    'label' => 'Feels Like',
                    'value' => "{$feelsLike}°C",
                    'delta' => null
                ],
                [
                    'label' => 'Humidity',
                    'value' => "💧 {$humidity}%",
                    'delta' => null
                ],
                [
                    'label' => 'Wind Speed',
                    'value' => "💨 {$windSpeed} km/h",
                    'delta' => null
                ]
            ];
            
            // Add pressure if available
            if ($pressure) {
                $result[] = [
                    'label' => 'Pressure',
                    'value' => "🌡️ {$pressure} hPa",
                    'delta' => null
                ];
            }
            
            // Add visibility if available
            if ($visibility) {
                $result[] = [
                    'label' => 'Visibility',
                    'value' => "👁️ {$visibility} km",
                    'delta' => null
                ];
            }
            
            // Add sunrise/sunset if available
            if ($sunrise && $sunset) {
                $result[] = [
                    'label' => 'Sun Times',
                    'value' => "🌅 {$sunrise} | 🌇 {$sunset}",
                    'delta' => null
                ];
            }
            
            // Add today's high/low and tomorrow's forecast
            if ($forecastData && isset($forecastData['list'])) {
                $todayTemps = [];
                $tomorrowTemps = [];
                $today = date('Y-m-d');
                $tomorrow = date('Y-m-d', strtotime('+1 day'));
                $tomorrowCondition = null;
                
                foreach ($forecastData['list'] as $item) {
                    $itemDate = date('Y-m-d', $item['dt']);
                    if ($itemDate === $today) {
                        $todayTemps[] = $item['main']['temp'];
                    } elseif ($itemDate === $tomorrow) {
                        $tomorrowTemps[] = $item['main']['temp'];
                        if (!$tomorrowCondition && isset($item['weather'][0]['main'])) {
                            $tomorrowCondition = $item['weather'][0]['main'];
                        }
                    }
                }
                
                // Today's range
                if (!empty($todayTemps)) {
                    $high = round(max($todayTemps));
                    $low = round(min($todayTemps));
                    $result[] = [
                        'label' => 'Today\'s Range',
                        'value' => "📊 {$low}°C - {$high}°C",
                        'delta' => null
                    ];
                }
                
                // Tomorrow's preview
                if (!empty($tomorrowTemps) && $tomorrowCondition) {
                    $tomorrowHigh = round(max($tomorrowTemps));
                    $tomorrowLow = round(min($tomorrowTemps));
                    $tomorrowEmoji = $this->getWeatherEmoji($tomorrowCondition);
                    $result[] = [
                        'label' => 'Tomorrow',
                        'value' => "{$tomorrowEmoji} {$tomorrowLow}°C - {$tomorrowHigh}°C",
                        'delta' => null
                    ];
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Weather module error: ' . $e->getMessage());
            return [
                [
                    'label' => 'Weather',
                    'value' => 'Data unavailable',
                    'delta' => null
                ]
            ];
        }
    }
    
    public function getConfigFields(): array {
        return [
            [
                'name' => 'api_key',
                'type' => 'text',
                'label' => 'OpenWeatherMap API Key',
                'required' => true,
                'description' => 'Get your free API key from openweathermap.org'
            ],
            [
                'name' => 'city',
                'type' => 'text',
                'label' => 'City',
                'required' => true,
                'description' => 'City name (e.g., "New York", "London", "Tokyo")',
                'default' => 'New York'
            ]
        ];
    }
    
    public function validateConfig(array $config): bool {
        // Check if required fields are present and not empty
        $hasApiKey = isset($config['api_key']) && trim($config['api_key']) !== '';
        $hasCity = isset($config['city']) && trim($config['city']) !== '';
        
        return $hasApiKey && $hasCity;
    }
    
    private function getWeatherEmoji(string $condition): string {
        $condition = strtolower($condition);
        
        switch ($condition) {
            case 'clear':
                return '☀️';
            case 'clouds':
                return '☁️';
            case 'rain':
            case 'drizzle':
                return '🌧️';
            case 'thunderstorm':
                return '⛈️';
            case 'snow':
                return '❄️';
            case 'mist':
            case 'fog':
            case 'haze':
                return '🌫️';
            case 'dust':
            case 'sand':
                return '🌪️';
            default:
                return '🌤️';
        }
    }
}