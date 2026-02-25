<?php
require_once __DIR__ . '/../core/SourceModule.php';

class LocalNewsModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'City News';
    }

    public function getData(): array {
        try {
            $city = trim((string)($this->config['city'] ?? ''));
            $country = trim((string)($this->config['country'] ?? ''));
            $language = $this->sanitizeLanguage($this->config['language'] ?? 'en');
            $countryCode = $this->resolveCountryCode($this->config['country_code'] ?? '', $country);
            $itemLimit = $this->sanitizeItemLimit($this->config['item_limit'] ?? 5);

            if ($city === '') {
                throw new Exception('City is required');
            }

            $query = trim($city . ($country !== '' ? ' ' . $country : ''));
            $apiUrl = $this->buildGoogleNewsSearchUrl($query, $language, $countryCode);

            $response = $this->makeHttpRequest($apiUrl, [
                'User-Agent: MorningNewsletter/1.0 (https://morningnewsletter.com; hello@morningnewsletter.com)'
            ]);

            $xml = @simplexml_load_string($response);
            if ($xml === false || !isset($xml->channel->item)) {
                throw new Exception('Failed to parse Google News feed');
            }

            $result = [];
            $count = 0;

            foreach ($xml->channel->item as $item) {
                if ($count >= $itemLimit) {
                    break;
                }

                $title = $this->cleanText((string)($item->title ?? ''));
                $description = $this->cleanText((string)($item->description ?? ''));
                $link = trim((string)($item->link ?? ''));
                $sourceName = $this->cleanText((string)($item->source ?? ''));
                $published = $this->formatArticleTimestamp((string)($item->pubDate ?? ''));

                $sourceSuffix = ' - ' . $sourceName;
                if ($sourceName !== '' && substr($title, -strlen($sourceSuffix)) === $sourceSuffix) {
                    $title = substr($title, 0, -strlen($sourceSuffix));
                }

                if ($title === '') {
                    continue;
                }

                $metaParts = array_filter([$sourceName, $published]);
                $meta = !empty($metaParts) ? implode(' • ', $metaParts) : '';

                $summary = $description !== '' ? $this->truncateText($description, 160) : '';
                $value = trim($meta . ($meta !== '' && $summary !== '' ? ' - ' : '') . $summary);
                if ($value === '') {
                    $value = $meta !== '' ? $meta : 'Latest local coverage';
                }

                $result[] = [
                    'label' => $this->truncateText($title, 120),
                    'value' => $value,
                    'delta' => [
                        'value' => 'Read article →',
                        'color' => 'blue',
                        'link' => $link
                    ]
                ];

                $count++;
            }

            if (empty($result)) {
                return [[
                    'label' => 'City News',
                    'value' => 'No recent articles found for this city',
                    'delta' => null
                ]];
            }

            return $result;
        } catch (Exception $e) {
            error_log('Local news module error: ' . $e->getMessage());

            return [[
                'label' => 'City News',
                'value' => 'City news is unavailable right now',
                'delta' => null
            ]];
        }
    }

    public function getConfigFields(): array {
        return [
            [
                'name' => 'city',
                'type' => 'text',
                'label' => 'City',
                'required' => true,
                'placeholder' => 'Castellon'
            ],
            [
                'name' => 'country',
                'type' => 'text',
                'label' => 'Country (optional)',
                'required' => false,
                'placeholder' => 'Spain',
                'description' => 'Helps disambiguate the city in search results'
            ],
            [
                'name' => 'language',
                'type' => 'select',
                'label' => 'Feed language',
                'required' => true,
                'default' => 'en',
                'options' => [
                    'en' => 'English',
                    'es' => 'Spanish',
                    'fr' => 'French',
                    'de' => 'German',
                    'it' => 'Italian',
                    'pt' => 'Portuguese'
                ]
            ],
            [
                'name' => 'country_code',
                'type' => 'text',
                'label' => 'Region code (optional)',
                'required' => false,
                'placeholder' => 'ES',
                'description' => '2-letter country code for more local results (e.g. ES, US, GB)'
            ],
            [
                'name' => 'item_limit',
                'type' => 'select',
                'label' => 'Number of articles',
                'required' => true,
                'default' => '5',
                'options' => [
                    '3' => 'Top 3',
                    '5' => 'Top 5',
                    '10' => 'Top 10'
                ]
            ]
        ];
    }

    public function validateConfig(array $config): bool {
        $city = trim((string)($config['city'] ?? ''));
        if ($city === '') {
            return false;
        }

        $language = $this->sanitizeLanguage($config['language'] ?? 'en');
        if ($language === '') {
            return false;
        }

        $countryCode = trim((string)($config['country_code'] ?? ''));
        if ($countryCode !== '' && !preg_match('/^[a-zA-Z]{2}$/', $countryCode)) {
            return false;
        }

        $itemLimit = (int)($config['item_limit'] ?? 5);
        return in_array($itemLimit, [3, 5, 10], true);
    }

    private function buildGoogleNewsSearchUrl(string $query, string $language, string $countryCode): string {
        $encodedQuery = rawurlencode($query);
        $gl = strtoupper($countryCode);
        $hl = strtolower($language);
        $ceid = $gl . ':' . $hl;

        return "https://news.google.com/rss/search?q={$encodedQuery}&hl={$hl}&gl={$gl}&ceid={$ceid}";
    }

    private function sanitizeLanguage($language): string {
        $language = strtolower(trim((string)$language));
        $allowed = ['en', 'es', 'fr', 'de', 'it', 'pt'];

        return in_array($language, $allowed, true) ? $language : 'en';
    }

    private function sanitizeItemLimit($itemLimit): int {
        $itemLimit = (int)$itemLimit;
        if (!in_array($itemLimit, [3, 5, 10], true)) {
            return 5;
        }

        return $itemLimit;
    }

    private function resolveCountryCode($countryCodeInput, $countryNameInput): string {
        $countryCode = strtoupper(trim((string)$countryCodeInput));
        if (preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return $countryCode;
        }

        $countryName = strtolower(trim((string)$countryNameInput));
        $countryMap = [
            'spain' => 'ES',
            'espana' => 'ES',
            'españa' => 'ES',
            'united states' => 'US',
            'usa' => 'US',
            'us' => 'US',
            'united kingdom' => 'GB',
            'uk' => 'GB',
            'great britain' => 'GB',
            'england' => 'GB',
            'france' => 'FR',
            'germany' => 'DE',
            'italy' => 'IT',
            'portugal' => 'PT',
            'mexico' => 'MX',
            'canada' => 'CA',
            'australia' => 'AU'
        ];

        if ($countryName !== '' && isset($countryMap[$countryName])) {
            return $countryMap[$countryName];
        }

        return 'US';
    }

    private function formatArticleTimestamp(string $pubDate): string {
        if ($pubDate === '') {
            return '';
        }

        try {
            $date = new DateTime($pubDate);
            $date->setTimezone(new DateTimeZone($this->timezone ?: 'UTC'));
            return $date->format('M j, H:i');
        } catch (Exception $e) {
            return '';
        }
    }

    private function cleanText(string $text): string {
        if ($text === '') {
            return '';
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string)$text);
    }

    private function truncateText(string $text, int $maxLength): string {
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $maxLength) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, $maxLength - 1, 'UTF-8')) . '…';
        }

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(substr($text, 0, $maxLength - 3)) . '...';
    }
}
