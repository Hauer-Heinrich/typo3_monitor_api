<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use \TYPO3\CMS\Core\SingletonInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;
use \HauerHeinrich\Typo3MonitorApi\Utility\RoutingConfig;

/**
 * A Operation which returns the current TYPO3 version
 * @TODO: naming of class maybe confusing
 */
class GetAllowedOperations implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute(array $parameter = []): OperationResult
    {
        $routingConfig = GeneralUtility::makeInstance(RoutingConfig::class);
        $allowedOperations = $routingConfig->getMethodsAllowed();

        return new OperationResult(true, [[ 'methods' => $allowedOperations ]]);
    }
}
