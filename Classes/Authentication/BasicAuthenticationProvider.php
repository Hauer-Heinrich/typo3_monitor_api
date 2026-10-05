<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authentication;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use HauerHeinrich\Typo3MonitorApi\Domain\Model\User;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Validates API credentials against be_users.
 *
 * - Deleted, disabled and expired users are rejected (default query restrictions).
 * - Admin users are rejected unless "allowAdminUsers" is enabled: the API bypasses
 *   MFA and the backend login, so an admin password must never be usable here.
 * - If "allowedUsers" is set, only these usernames are accepted.
 */
final class BasicAuthenticationProvider
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly PasswordHashFactory $passwordHashFactory,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function authenticate(User $user): bool
    {
        $username = (string)$user->getUserName();
        $password = (string)$user->getUserPassword();
        if ($username === '' || $password === '') {
            return false;
        }

        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);

        $allowedUsers = GeneralUtility::trimExplode(',', (string)($config['allowedUsers'] ?? ''), true);
        if ($allowedUsers !== [] && !in_array($username, $allowedUsers, true)) {
            return false;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $row = $queryBuilder
            ->select('username', 'password', 'admin')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return false;
        }

        if ((int)$row['admin'] === 1 && !(bool)($config['allowAdminUsers'] ?? false)) {
            return false;
        }

        $passwordHash = (string)$row['password'];
        try {
            $hashInstance = $this->passwordHashFactory->get($passwordHash, 'BE');
        } catch (InvalidPasswordHashException) {
            return false;
        }

        return $hashInstance->checkPassword($password, $passwordHash);
    }
}
