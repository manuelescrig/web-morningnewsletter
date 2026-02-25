<?php
require_once __DIR__ . '/../core/SourceModule.php';
require_once __DIR__ . '/rss.php';

class NewspaperModule extends BaseSourceModule {
    private const PRESETS = [
        'bbc_world' => [
            'label' => 'BBC News (World)',
            'url' => 'https://feeds.bbci.co.uk/news/world/rss.xml'
        ],
        'guardian_world' => [
            'label' => 'The Guardian (World)',
            'url' => 'https://www.theguardian.com/world/rss'
        ],
        'nyt_home' => [
            'label' => 'The New York Times (Home Page)',
            'url' => 'https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml'
        ],
        'elmundo_portada' => [
            'label' => 'El Mundo (Portada)',
            'url' => 'https://e00-elmundo.uecdn.es/elmundo/rss/portada.xml'
        ]
    ];

    public function getTitle(): string {
        return 'Newspaper RSS';
    }

    public function getData(): array {
        try {
            $presetKey = trim((string)($this->config['preset'] ?? ''));
            $itemLimit = (int)($this->config['item_limit'] ?? 5);

            if (!isset(self::PRESETS[$presetKey])) {
                throw new Exception('Invalid newspaper preset');
            }

            if (!in_array($itemLimit, [3, 5, 10], true)) {
                $itemLimit = 5;
            }

            $preset = self::PRESETS[$presetKey];
            $rssModule = new RSSModule([
                'feed_url' => $preset['url'],
                'item_limit' => (string)$itemLimit
            ], $this->timezone);

            return $rssModule->getData();
        } catch (Exception $e) {
            error_log('Newspaper module error: ' . $e->getMessage());

            return [[
                'label' => 'Newspaper RSS',
                'value' => 'Newspaper feed is unavailable right now',
                'delta' => null
            ]];
        }
    }

    public function getConfigFields(): array {
        $options = [];
        foreach (self::PRESETS as $key => $preset) {
            $options[$key] = $preset['label'];
        }

        return [
            [
                'name' => 'preset',
                'type' => 'select',
                'label' => 'Newspaper',
                'required' => true,
                'default' => 'bbc_world',
                'options' => $options
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
        $preset = $config['preset'] ?? '';
        $itemLimit = (int)($config['item_limit'] ?? 5);

        return isset(self::PRESETS[$preset]) && in_array($itemLimit, [3, 5, 10], true);
    }
}
