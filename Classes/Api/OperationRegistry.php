<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Api;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Reads Configuration/MonitorOperations.php.
 *
 * Intentionally static and without dependencies: it is also used while TCA is loaded
 * (checkbox list in be_users), where the DI container is not available yet.
 */
final class OperationRegistry {

    private const CONFIGURATION_FILE = __DIR__ . '/../../Configuration/MonitorOperations.php';

    /**
     * @var array<string, OperationDefinition>|null
     */
    private static ?array $definitions = null;

    /**
     * @return array<string, OperationDefinition>
     */
    public static function getDefinitions(): array {
        if (self::$definitions === null) {
            $definitions = [];
            foreach (require self::CONFIGURATION_FILE as $name => $configuration) {
                $definitions[$name] = OperationDefinition::fromArray($name, $configuration);
            }
            self::$definitions = $definitions;
        }

        return self::$definitions;
    }

    /**
     * @return list<string>
     */
    public static function getOperationNames(): array {
        return array_keys(self::getDefinitions());
    }

    /**
     * Case-insensitive, like the previous router (/v1/getphpversion works too)
     */
    public static function findDefinition(string $operationName): ?OperationDefinition {
        foreach (self::getDefinitions() as $name => $definition) {
            if (strcasecmp($name, $operationName) === 0) {
                return $definition;
            }
        }

        return null;
    }
}
