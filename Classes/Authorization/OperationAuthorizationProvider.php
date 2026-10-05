<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authorization;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\AuthenticatedUser;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;

/**
 * An operation is callable only if BOTH apply (fail closed):
 *   1. it is enabled globally in the extension configuration ("Allowed operations"), and
 *   2. it is ticked in the backend user record (tab "Monitor API" -> "allowed operations").
 *
 * The global setting is the upper limit: e.g. UpdateMinorTypo3 can stay disabled
 * no matter what is ticked in a user record.
 */
final class OperationAuthorizationProvider {
    /**
     * be_users field with the operations granted to the user (comma separated list)
     */
    public const USER_FIELD = 'tx_typo3monitorapi_operations';

    /**
     * Request attribute holding the AuthenticatedUser (set by the middleware)
     */
    public const REQUEST_ATTRIBUTE_USER = 'typo3MonitorApiUser';

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function isOperationAllowed(string $operationName, ?AuthenticatedUser $user): bool {
        return $user !== null
            && $this->isGloballyEnabled($operationName)
            && in_array($operationName, $user->allowedOperations, true);
    }

    /**
     * Users without any usable operation are treated like failed logins by the middleware,
     * so the API can't be used to verify passwords of regular editors.
     */
    public function hasAnyAllowedOperation(AuthenticatedUser $user): bool {
        foreach ($user->allowedOperations as $operationName) {
            if ($this->isOperationAllowed($operationName, $user)) {
                return true;
            }
        }

        return false;
    }

    public function isGloballyEnabled(string $operationName): bool {
        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);
        $operations = $config['operations'] ?? [];

        return is_array($operations) && (string)($operations[$operationName] ?? '0') === '1';
    }

    public static function getUserFromRequest(?ServerRequestInterface $request): ?AuthenticatedUser {
        $user = $request?->getAttribute(self::REQUEST_ATTRIBUTE_USER);

        return $user instanceof AuthenticatedUser ? $user : null;
    }
}
