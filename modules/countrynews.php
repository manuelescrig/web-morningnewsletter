<?php
require_once __DIR__ . '/../core/SourceModule.php';

class CountryNewsModule extends BaseSourceModule {
    public function getTitle(): string {
        return 'Country News';
    }

    public function getData(): array {
        try {
            $countryCode = $this->sanitizeCountryCode($this->config['country_code'] ?? 'US');
            $language = $this->sanitizeLanguage($this->config['language'] ?? 'en');
            $itemLimit = $this->sanitizeItemLimit($this->config['item_limit'] ?? 5);
            $topic = $this->sanitizeTopic($this->config['topic'] ?? 'top');

            $apiUrl = $this->buildGoogleNewsUrl($countryCode, $language, $topic);
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
                if ($sourceName !== '' && $title !== '' && substr($title, -strlen($sourceSuffix)) === $sourceSuffix) {
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
                    $value = $meta !== '' ? $meta : 'Latest headlines';
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
                    'label' => 'Country News',
                    'value' => 'No recent articles found',
                    'delta' => null
                ]];
            }

            return $result;
        } catch (Exception $e) {
            error_log('Country news module error: ' . $e->getMessage());

            return [[
                'label' => 'Country News',
                'value' => 'Country news is unavailable right now',
                'delta' => null
            ]];
        }
    }

    public function getConfigFields(): array {
        return [
            [
                'name' => 'country_code',
                'type' => 'select',
                'label' => 'Country',
                'required' => true,
                'default' => 'US',
                'options' => [
                    'US' => 'United States',
                    'ES' => 'Spain',
                    'GB' => 'United Kingdom',
                    'CA' => 'Canada',
                    'AU' => 'Australia',
                    'FR' => 'France',
                    'DE' => 'Germany',
                    'IT' => 'Italy',
                    'PT' => 'Portugal',
                    'MX' => 'Mexico'
                ]
            ],
            [
                'name' => 'language',
                'type' => 'select',
                'label' => 'Language',
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
                'name' => 'topic',
                'type' => 'select',
                'label' => 'Topic',
                'required' => true,
                'default' => 'top',
                'options' => [
                    'top' => 'Top Headlines',
                    'business' => 'Business',
                    'technology' => 'Technology',
                    'world' => 'World'
                ]
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
        $countryCode = trim((string)($config['country_code'] ?? ''));
        if (!preg_match('/^[a-zA-Z]{2}$/', $countryCode)) {
            return false;
        }

        $language = $this->sanitizeLanguage($config['language'] ?? 'en');
        if ($language === '') {
            return false;
        }

        $topic = $this->sanitizeTopic($config['topic'] ?? 'top');
        if ($topic === '') {
            return false;
        }

        $itemLimit = (int)($config['item_limit'] ?? 5);
        return in_array($itemLimit, [3, 5, 10], true);
    }

    private function buildGoogleNewsUrl(string $countryCode, string $language, string $topic): string {
        $gl = strtoupper($countryCode);
        $hl = strtolower($language);
        $ceid = $gl . ':' . $hl;

        if ($topic === 'top') {
            return "https://news.google.com/rss?hl={$hl}&gl={$gl}&ceid={$ceid}";
        }

        $topicMap = [
            'business' => 'BUSINESS',
            'technology' => 'TECHNOLOGY',
            'world' => 'WORLD'
        ];

        $topicKey = $topicMap[$topic] ?? 'WORLD';
        return "https://news.google.com/rss/headlines/section/topic/{$topicKey}?hl={$hl}&gl={$gl}&ceid={$ceid}";
    }

    private function sanitizeCountryCode($countryCode): string {
        $countryCode = strtoupper(trim((string)$countryCode));
        return preg_match('/^[A-Z]{2}$/', $countryCode) ? $countryCode : 'US';
    }

    private function sanitizeLanguage($language): string {
        $language = strtolower(trim((string)$language));
        $allowed = ['en', 'es', 'fr', 'de', 'it', 'pt'];
        return in_array($language, $allowed, true) ? $language : 'en';
    }

    private function sanitizeTopic($topic): string {
        $topic = strtolower(trim((string)$topic));
        $allowed = ['top', 'business', 'technology', 'world'];
        return in_array($topic, $allowed, true) ? $topic : 'top';
    }

    private function sanitizeItemLimit($itemLimit): int {
        $itemLimit = (int)$itemLimit;
        return in_array($itemLimit, [3, 5, 10], true) ? $itemLimit : 5;
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
