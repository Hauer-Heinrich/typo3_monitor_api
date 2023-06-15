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

use \TYPO3\CMS\Core\SingletonInterface;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * A Operation which returns the current TYPO3 version
 * @todo TYPO3 12 - remove unnecessary code if TYPO3 version 12 is available
 */
class GetTYPO3Version implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute(array $parameter = []): OperationResult
    {
        $typo3version = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        $version = $typo3version->getVersion();

        return new OperationResult(true, [[ 'version' => $version ]]);
    }
}
