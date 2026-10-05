<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Service;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Latest TYPO3 (security) release of a major version from get.typo3.org, cached.
 * Used by HasUpdate and HasSecurityUpdate.
 */
final class Typo3ReleaseInformation {

    private const API_URL = 'https://get.typo3.org/v1/api/major/%d/release/latest%s';

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly MonitorCache $cache,
    ) {}

    /**
     * @throws \RuntimeException if get.typo3.org can't be reached or answers unexpectedly (not cached)
     */
    public function getLatestVersion(int $majorVersion, bool $securityOnly = false): string {
        return $this->cache->remember(
            sprintf('latest_%s_%d', $securityOnly ? 'security_release' : 'release', $majorVersion),
            fn(): string => $this->fetchLatestVersion($majorVersion, $securityOnly)
        );
    }

    private function fetchLatestVersion(int $majorVersion, bool $securityOnly): string {
        $url = sprintf(self::API_URL, $majorVersion, $securityOnly ? '/security' : '');
        $response = $this->requestFactory->request($url, 'GET', [
            'headers' => ['Cache-Control' => 'no-cache'],
            'allow_redirects' => false,
            'cookies' => false,
        ]);

        if ($response->getStatusCode() !== 200 || !str_starts_with($response->getHeaderLine('Content-Type'), 'application/json')) {
            throw new \RuntimeException(sprintf('Unexpected response from %s (status %d)', $url, $response->getStatusCode()), 1759661001);
        }

        $content = json_decode((string)$response->getBody(), true);
        if (!is_array($content) || !is_string($content['version'] ?? null)) {
            throw new \RuntimeException(sprintf('No version in response from %s', $url), 1759661002);
        }

        return $content['version'];
    }
}
