<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authentication;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use HauerHeinrich\Typo3MonitorApi\Authorization\OperationAuthorizationProvider;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\AuthenticatedUser;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\User;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;

/**
 * Validates API credentials against be_users and returns the user with its API settings
 * (allowed IPs and operations from the tab "Monitor API").
 *
 * - Deleted, disabled and expired users are rejected (default query restrictions).
 * - Admin users are rejected: the API bypasses
 *   MFA and the backend login, so an admin password must never be usable here.
 * - If "allowedUsers" is set, only these usernames are accepted.
 *
 * No backend session is started and no backend groups are involved.
 */
final class BasicAuthenticationProvider {
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly PasswordHashFactory $passwordHashFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function authenticate(User $user): ?AuthenticatedUser {
        $username = (string)$user->getUserName();
        $password = (string)$user->getUserPassword();
        if ($username === '' || $password === '') {
            return null;
        }

        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);

        $allowedUsers = GeneralUtility::trimExplode(',', (string)($config['allowedUsers'] ?? ''), true);
        if ($allowedUsers !== [] && !in_array($username, $allowedUsers, true)) {
            return null;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $row = $queryBuilder
            ->select('uid', 'username', 'password', 'admin', IpAuthenticationProvider::USER_FIELD, OperationAuthorizationProvider::USER_FIELD)
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        if ((int)$row['admin'] === 1) {
            return null;
        }

        $passwordHash = (string)$row['password'];
        try {
            $hashInstance = $this->passwordHashFactory->get($passwordHash, 'BE');
        } catch (InvalidPasswordHashException) {
            return null;
        }

        if (!$hashInstance->checkPassword($password, $passwordHash)) {
            return null;
        }

        return new AuthenticatedUser(
            (int)$row['uid'],
            (string)$row['username'],
            (string)($row[IpAuthenticationProvider::USER_FIELD] ?? ''),
            GeneralUtility::trimExplode(',', (string)($row[OperationAuthorizationProvider::USER_FIELD] ?? ''), true),
        );
    }
}
