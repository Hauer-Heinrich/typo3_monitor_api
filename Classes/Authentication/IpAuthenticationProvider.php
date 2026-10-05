<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authentication;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Checks the client IP against the "allowedIps" extension setting.
 *
 * Fails closed: an empty setting allows nobody, "*" allows every IP explicitly.
 * Supported notations (see GeneralUtility::cmpIP()):
 *   - single IPv4/IPv6 addresses:  203.0.113.10, 2001:db8::1
 *   - IPv4 wildcards:              203.0.113.*
 *   - CIDR ranges:                 203.0.113.0/24, 2001:db8::/32
 *   - legacy prefix notation:      203.0.113.   (converted to 203.0.113.*)
 */
final class IpAuthenticationProvider
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function isAllowed(ServerRequestInterface $request): bool
    {
        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);
        $allowedIps = trim((string)($config['allowedIps'] ?? ''));
        if ($allowedIps === '') {
            return false;
        }

        $remoteAddress = $this->getRemoteAddress($request);
        if ($remoteAddress === '') {
            return false;
        }

        return GeneralUtility::cmpIP($remoteAddress, $this->normalizeIpList($allowedIps));
    }

    /**
     * Uses normalizedParams, so TYPO3's reverseProxyIP / reverseProxyHeaderMultiValue settings are respected.
     */
    private function getRemoteAddress(ServerRequestInterface $request): string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');

        return $normalizedParams instanceof NormalizedParams ? $normalizedParams->getRemoteAddress() : '';
    }

    private function normalizeIpList(string $allowedIps): string
    {
        $entries = [];
        foreach (GeneralUtility::trimExplode(',', $allowedIps, true) as $entry) {
            // Legacy notation "77.6.178." -> "77.6.178.*" (cmpIP expects four IPv4 parts)
            if (!str_contains($entry, ':') && str_ends_with($entry, '.')) {
                $parts = explode('.', rtrim($entry, '.'));
                $entry = implode('.', array_pad($parts, 4, '*'));
            }
            $entries[] = $entry;
        }

        return implode(',', $entries);
    }
}
