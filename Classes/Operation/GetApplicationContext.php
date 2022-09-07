<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

use \TYPO3\CMS\Core\Core\Environment;
use \TYPO3\CMS\Core\SingletonInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;

class GetApplicationContext implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current application context
     */
    public function execute(array $parameter = []): OperationResult
    {
        if(!empty(TYPO3_version)) {
            $typo3Version = TYPO3_version;
        } else {
            $typo3VersionClass = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
            $typo3Version = $typo3VersionClass->getVersion();
        }

        if (version_compare($typo3Version, '9.5.0', '>=')) {
            $applicationContext = Environment::getContext();
            return new OperationResult(true, [[ 'data' => $applicationContext->__toString() ]]);
        }

        $applicationContext = GeneralUtility::getApplicationContext();
        return new OperationResult(true, [[ 'data' => $applicationContext->__toString() ]]);
    }
}
