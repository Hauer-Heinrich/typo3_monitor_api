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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\AuthenticatedUser;

/**
 * IP restrictions on two levels:
 *   - global ("allowedIps" extension setting), checked BEFORE authentication.
 *     Fails closed: empty = nobody, "*" = every IP.
 *   - per user (be_users.tx_typo3monitorapi_allowed_ips), checked AFTER authentication,
 *     in addition to the global list. Empty = no additional restriction.
 *
 * Supported notations (see GeneralUtility::cmpIP()):
 *   single IPv4/IPv6 addresses, IPv4 wildcards (203.0.113.*), CIDR ranges (203.0.113.0/24, 2001:db8::/32)
 *   and the legacy prefix notation (203.0.113.), which is converted to a wildcard.
 */
final class IpAuthenticationProvider {
    public const USER_FIELD = 'tx_typo3monitorapi_allowed_ips';

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function isAllowed(ServerRequestInterface $request): bool {
        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);

        return $this->matches($request, (string)($config['allowedIps'] ?? ''));
    }

    public function isAllowedForUser(ServerRequestInterface $request, AuthenticatedUser $user): bool {
        $allowedIps = trim($user->allowedIps);
        if ($allowedIps === '') {
            return true;
        }

        return $this->matches($request, $allowedIps);
    }

    private function matches(ServerRequestInterface $request, string $allowedIps): bool {
        $allowedIps = trim($allowedIps);
        if ($allowedIps === '') {
            return false;
        }

        $normalizedParams = $request->getAttribute('normalizedParams');
        // normalizedParams respects TYPO3's reverseProxyIP / reverseProxyHeaderMultiValue settings
        $remoteAddress = $normalizedParams instanceof NormalizedParams ? $normalizedParams->getRemoteAddress() : '';
        if ($remoteAddress === '') {
            return false;
        }

        return GeneralUtility::cmpIP($remoteAddress, $this->normalizeIpList($allowedIps));
    }

    private function normalizeIpList(string $allowedIps): string {
        $entries = [];
        // Newlines are allowed too, the per-user field is a textarea
        foreach (GeneralUtility::trimExplode(',', str_replace(["\r\n", "\n", "\r"], ',', $allowedIps), true) as $entry) {
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
