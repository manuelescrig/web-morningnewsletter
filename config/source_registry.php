<?php

class SourceRegistry {
    public static function getDefinitions(): array {
        return [
            'bitcoin' => [
                'name' => 'Bitcoin Price',
                'display_label' => 'Bitcoin',
                'description' => 'Track Bitcoin price and 24-hour changes',
                'category' => 'crypto',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => [],
                'module_class' => 'BitcoinModule',
                'dashboard_icon_class' => 'fab fa-bitcoin',
                'admin_icon_suffix' => 'bitcoin'
            ],
            'ethereum' => [
                'name' => 'Ethereum Price',
                'display_label' => 'Ethereum',
                'description' => 'Track Ethereum price and market performance',
                'category' => 'crypto',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => [],
                'module_class' => 'EthereumModule',
                'dashboard_icon_class' => 'fab fa-ethereum',
                'admin_icon_suffix' => 'ethereum'
            ],
            'xrp' => [
                'name' => 'XRP Price',
                'display_label' => 'XRP',
                'description' => 'Track XRP (Ripple) price and market trends',
                'category' => 'crypto',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => [],
                'module_class' => 'XrpModule',
                'dashboard_icon_class' => 'fas fa-coins',
                'admin_icon_suffix' => 'coins'
            ],
            'binancecoin' => [
                'name' => 'Binance Coin Price',
                'display_label' => 'Binance Coin',
                'description' => 'Track BNB (Binance Coin) price and performance',
                'category' => 'crypto',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => [],
                'module_class' => 'BinancecoinModule',
                'dashboard_icon_class' => 'fas fa-coins',
                'admin_icon_suffix' => 'coins'
            ],
            'sp500' => [
                'name' => 'S&P 500 Index',
                'display_label' => 'S&P 500',
                'description' => 'Monitor S&P 500 index performance and trends',
                'category' => 'finance',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => [],
                'module_class' => 'SP500Module',
                'dashboard_icon_class' => 'fas fa-chart-line',
                'admin_icon_suffix' => 'chart-line'
            ],
            'stock' => [
                'name' => 'Stock Price',
                'display_label' => 'Stock',
                'description' => 'Track individual stock prices with real-time updates',
                'category' => 'finance',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => ['symbol' => '', 'display_name' => ''],
                'module_class' => 'StockModule',
                'dashboard_icon_class' => 'fas fa-chart-line',
                'admin_icon_suffix' => 'chart-line'
            ],
            'weather' => [
                'name' => 'Weather',
                'display_label' => 'Weather',
                'description' => 'Weather forecast using Norwegian Meteorological Institute',
                'category' => 'lifestyle',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => ['city' => 'New York'],
                'module_class' => 'WeatherModule',
                'dashboard_icon_class' => 'fas fa-cloud-sun',
                'admin_icon_suffix' => 'cloud-sun'
            ],
            'news' => [
                'name' => 'News Headlines (NewsAPI)',
                'display_label' => 'News',
                'description' => 'Top headlines from trusted news sources (NewsAPI, API key required)',
                'category' => 'news',
                'is_enabled' => 0,
                'api_required' => 1,
                'default_config' => [],
                'module_class' => 'NewsModule',
                'dashboard_icon_class' => 'fas fa-newspaper',
                'admin_icon_suffix' => 'newspaper'
            ],
            'localnews' => [
                'name' => 'City News',
                'display_label' => 'City News',
                'description' => 'Local news for a city using Google News search',
                'category' => 'news',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => ['city' => '', 'country' => '', 'language' => 'en', 'country_code' => '', 'item_limit' => '5'],
                'module_class' => 'LocalNewsModule',
                'dashboard_icon_class' => 'fas fa-city',
                'admin_icon_suffix' => 'city'
            ],
            'countrynews' => [
                'name' => 'Country News',
                'display_label' => 'Country News',
                'description' => 'Top country headlines using Google News RSS (no API key)',
                'category' => 'news',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => ['country_code' => 'US', 'language' => 'en', 'topic' => 'top', 'item_limit' => '5'],
                'module_class' => 'CountryNewsModule',
                'dashboard_icon_class' => 'fas fa-flag',
                'admin_icon_suffix' => 'flag'
            ],
            'newspaper' => [
                'name' => 'Newspaper RSS',
                'display_label' => 'Newspaper RSS',
                'description' => 'Curated newspaper RSS presets (no API key)',
                'category' => 'news',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => ['preset' => 'bbc_world', 'item_limit' => '5'],
                'module_class' => 'NewspaperModule',
                'dashboard_icon_class' => 'fas fa-rss',
                'admin_icon_suffix' => 'rss'
            ],
            'rss' => [
                'name' => 'RSS Feed',
                'display_label' => 'RSS',
                'description' => 'Subscribe to any RSS or Atom feed',
                'category' => 'news',
                'is_enabled' => 1,
                'api_required' => 0,
                'default_config' => ['feed_url' => '', 'item_limit' => '3', 'display_name' => ''],
                'module_class' => 'RSSModule',
                'dashboard_icon_class' => 'fas fa-rss',
                'admin_icon_suffix' => 'rss'
            ],
            'appstore' => [
                'name' => 'App Store Sales',
                'display_label' => 'App Store',
                'description' => 'App Store Connect revenue and sales tracking (coming soon)',
                'category' => 'business',
                'is_enabled' => 0,
                'api_required' => 1,
                'default_config' => ['key_id' => '', 'issuer_id' => '', 'private_key' => '', 'app_id' => ''],
                'module_class' => 'AppStoreModule',
                'dashboard_icon_class' => 'fab fa-app-store',
                'admin_icon_suffix' => 'mobile-alt'
            ],
            'stripe' => [
                'name' => 'Stripe Revenue',
                'display_label' => 'Stripe',
                'description' => 'Track your Stripe payments and revenue',
                'category' => 'business',
                'is_enabled' => 1,
                'api_required' => 1,
                'default_config' => ['api_key' => ''],
                'module_class' => 'StripeModule',
                'dashboard_icon_class' => 'fab fa-stripe',
                'admin_icon_suffix' => 'credit-card'
            ]
        ];
    }

    public static function getDatabaseSourceConfigs(): array {
        $rows = [];

        foreach (self::getDefinitions() as $type => $definition) {
            $rows[] = [
                'type' => $type,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'category' => $definition['category'],
                'is_enabled' => $definition['is_enabled'],
                'api_required' => $definition['api_required'],
                'default_config' => json_encode($definition['default_config'])
            ];
        }

        return $rows;
    }

    public static function getModuleClassMap(): array {
        $map = [];
        foreach (self::getDefinitions() as $type => $definition) {
            if (!empty($definition['module_class'])) {
                $map[$type] = $definition['module_class'];
            }
        }
        return $map;
    }

    public static function getModuleClass(string $type): ?string {
        $definition = self::getDefinitions()[$type] ?? null;
        return $definition['module_class'] ?? null;
    }

    public static function getDisplayLabel(string $type): string {
        $definition = self::getDefinitions()[$type] ?? null;
        if ($definition && !empty($definition['display_label'])) {
            return $definition['display_label'];
        }

        return ucwords(str_replace('_', ' ', $type));
    }

    public static function getDashboardIconClass(string $type): string {
        $definition = self::getDefinitions()[$type] ?? null;
        return $definition['dashboard_icon_class'] ?? 'fas fa-cube';
    }

    public static function getAdminIconSuffix(string $type): string {
        $definition = self::getDefinitions()[$type] ?? null;
        return $definition['admin_icon_suffix'] ?? 'plug';
    }
}
