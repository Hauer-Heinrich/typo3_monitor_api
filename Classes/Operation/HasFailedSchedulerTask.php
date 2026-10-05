<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

class HasFailedSchedulerTask implements IOperation {
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        if (!ExtensionManagementUtility::isLoaded('scheduler')) {
            return new OperationResult(false, [], 'EXT:scheduler not loaded!');
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder
            ->count('uid')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('lastexecution_failure', $queryBuilder->createNamedParameter(''))
            );

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
        );

        $count = $queryBuilder->executeQuery()->fetchOne();

        return new OperationResult(true, [[ 'data' => $count > 0 ]]);
    }
}
