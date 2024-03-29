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

use \Doctrine\DBAL\DBALException;
use \TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Core\SingletonInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;


class GetDatabaseVersion implements IOperation, SingletonInterface
{
    /**
     * Get the current database version
     *
     * @param array $parameter None
     * @return OperationResult the current database version
     */
    public function execute(array $parameter = []): OperationResult
    {
        $db = [];
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {
            try {
                $db[$connectionName] = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getServerVersion();
            } catch (DBALException $e) {
                return new OperationResult(false, [], 'Can\'t connect to DB (ConnectionPool)!');
            }
        }

        return new OperationResult(true, [[ 'connection' => [$db] ]]);
    }
}
