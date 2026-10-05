<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Authorization;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Enforces the "Allowed operations" checkboxes of the extension configuration.
 * Fails closed: an operation is only callable if it was explicitly enabled.
 */
final class OperationAuthorizationProvider
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function isOperationAllowed(string $operationName): bool
    {
        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);
        $operations = $config['operations'] ?? [];

        return is_array($operations) && (string)($operations[$operationName] ?? '0') === '1';
    }
}
