<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Http\Message\ServerRequestInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * Every operation is a service from the DI container (see Configuration/Services.yaml)
 * and gets its dependencies via constructor.
 *
 * Only inject TYPO3 core services in the constructor. Classes from optional extensions
 * (install, extensionmanager, scheduler, ...) must be fetched lazily inside execute()
 * after checking ExtensionManagementUtility::isLoaded(), otherwise the container can't be
 * built if such an extension is missing.
 */
interface IOperation {
    /**
     * @param array<string, string|bool|int> $parameter Validated parameters (see Configuration/MonitorOperations.php)
     * @param ServerRequestInterface $request The current API request
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult;
}
