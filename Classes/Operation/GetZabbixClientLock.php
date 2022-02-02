<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\SingletonInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 * A Operation which returns the current TYPO3 version
 */
class GetZabbixClientLock implements IOperation, SingletonInterface
{
    /**
     * @var LockRepository
     */
    public $lockRepository = null;

    /**
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute(array $parameter = []): OperationResult
    {
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->lockRepository = $objectManager->get('WapplerSystems\\ZabbixClient\\Domain\\Repository\\LockRepository');
        $lockRecord = $this->lockRepository->findLast();

        return new OperationResult(true, [[ 'lock' => $lockRecord ]]);
    }
}
