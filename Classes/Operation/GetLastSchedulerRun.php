<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use HauerHeinrich\Typo3MonitorApi\OperationResult;
use HauerHeinrich\Typo3MonitorApi\Utility\FormatUtility;


class GetLastSchedulerRun implements IOperation, SingletonInterface
{

    public function execute(array $parameter = []): OperationResult
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);

        $lastRun = $registry->get('tx_scheduler', 'lastRun', []);

        if (isset($lastRun['end'])) {
            if(empty($parameter['format'])) {
                return new OperationResult(true, [[ 'tstamp' => $lastRun['end'] ]]);
            } else {
                $returnValue = FormatUtility::formatDateTime($lastRun['end'], $parameter['format']);
                if(empty($returnValue)) {
                    return new OperationResult(false, [], 'Param \'format\' not valid! Valid values are: \'d M Y H:i:s, d M Y, H:i:s, c, r\'');
                }

                return new OperationResult(true, [[ 'formated' => $returnValue ]]);
            }
        }

        return new OperationResult(false, [], 'Can\'t detect last scheduler run!');
    }
}
