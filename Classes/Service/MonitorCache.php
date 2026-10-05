<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Service;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;

/**
 * Cache for expensive or external lookups (TYPO3 releases from get.typo3.org,
 * extension update information). The cache itself is registered in ext_localconf.php.
 *
 * Lifetime: extension setting "cacheLifetime" in minutes, 0 = no caching.
 */
final class MonitorCache {
    public const CACHE_IDENTIFIER = 'typo3_monitor_api';

    private const DEFAULT_LIFETIME_MINUTES = 60;

    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * Returns the cached value or calls $callback and caches its result.
     * Exceptions thrown by $callback are not cached, so a failed lookup is retried on the next call.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function remember(string $identifier, callable $callback): mixed {
        $lifetime = $this->getLifetimeInSeconds();
        if ($lifetime === 0) {
            return $callback();
        }

        $cache = $this->cacheManager->getCache(self::CACHE_IDENTIFIER);
        $entry = $cache->get($identifier);
        // Wrapped in an array, so false/null can be cached too (get() returns false on a miss)
        if (is_array($entry) && array_key_exists('value', $entry)) {
            return $entry['value'];
        }

        $value = $callback();
        $cache->set($identifier, ['value' => $value], [], $lifetime);

        return $value;
    }

    public function flush(): void {
        $this->cacheManager->getCache(self::CACHE_IDENTIFIER)->flush();
    }

    private function getLifetimeInSeconds(): int {
        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);
        $minutes = (int)($config['cacheLifetime'] ?? self::DEFAULT_LIFETIME_MINUTES);

        return max(0, $minutes) * 60;
    }
}
