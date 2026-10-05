<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Domain\Model;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * A successfully authenticated API user with its API settings from be_users (tab "Monitor API").
 * Stored in the request attribute OperationAuthorizationProvider::REQUEST_ATTRIBUTE_USER.
 */
final class AuthenticatedUser {
    /**
     * @param list<string> $allowedOperations
     */
    public function __construct(
        public readonly int $uid,
        public readonly string $username,
        public readonly string $allowedIps,
        public readonly array $allowedOperations,
    ) {}
}
