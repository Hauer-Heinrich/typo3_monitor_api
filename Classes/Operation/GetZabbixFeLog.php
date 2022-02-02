<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

class GetZabbixFeLog implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current application context
     */
    public function execute(array $parameter = []): OperationResult
    {
        $limit = $parameter['limit'] ? intval($parameter['limit']) : 10;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(ConnectionPool::class)->getQueryBuilderForTable('tx_zabbixclient_domain_model_felog');
        $queryBuilder->select('uid', 'tstamp', 'error', 'details', 'log_data', 'log_message')
            ->from('tx_zabbixclient_domain_model_felog')
            ->setMaxResults($limit);

        return new OperationResult(true, $queryBuilder->execute()->fetchAll());
    }
}
