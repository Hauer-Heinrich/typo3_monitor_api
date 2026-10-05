<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authentication;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\RateLimiter\Storage\CachingFrameworkStorage;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;

/**
 * Blocks an IP after "maxCount" failed logins within "blockTime" minutes (sliding window).
 * Without this, the API would be an unthrottled brute-force channel for every backend password,
 * because it bypasses the rate limiting of the regular TYPO3 backend login.
 *
 * Setting maxCount or blockTime to 0 disables the limiter.
 */
final class LoginRateLimiter {
    private ?RateLimiterFactory $factory = null;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        // @internal in TYPO3 core, but the same storage the core uses for its own login rate limiting
        private readonly CachingFrameworkStorage $storage,
    ) {}

    public function isBlocked(ServerRequestInterface $request): bool {
        $limiter = $this->getLimiter($request);

        // consume(0) only reads the current state without using up a token
        return $limiter !== null && $limiter->consume(0)->getRemainingTokens() === 0;
    }

    public function registerFailedAttempt(ServerRequestInterface $request): void {
        $this->getLimiter($request)?->consume();
    }

    private function getLimiter(ServerRequestInterface $request): ?LimiterInterface {
        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);
        $maxCount = (int)($config['maxCount'] ?? 3);
        $blockTime = (int)($config['blockTime'] ?? 5);
        if ($maxCount <= 0 || $blockTime <= 0) {
            return null;
        }

        $this->factory ??= new RateLimiterFactory([
            'id' => 'typo3-monitor-api-login',
            'policy' => 'sliding_window',
            'limit' => $maxCount,
            'interval' => $blockTime . ' minutes',
        ], $this->storage);

        $normalizedParams = $request->getAttribute('normalizedParams');
        $remoteAddress = $normalizedParams instanceof NormalizedParams ? $normalizedParams->getRemoteAddress() : '';

        return $this->factory->create($remoteAddress !== '' ? $remoteAddress : 'unknown');
    }
}
