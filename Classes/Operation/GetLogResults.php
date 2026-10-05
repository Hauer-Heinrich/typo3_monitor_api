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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


class GetLogResults implements IOperation {
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    protected array $actions = [
        255 => 'actionLogin',
        -1 => 'actionErrors'
    ];

    protected array $timeFrames = [
        0 => 'thisWeek',
        1 => 'lastWeek',
        2 => 'last7Days',
        10 => 'thisMonth',
        11 => 'lastMonth',
        12 => 'last31Days',
        20 => 'noLimit',
        30 => 'userDefined',
    ];

    /**
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        if(!isset($parameter['filter'])) {
            return new OperationResult(false, [], 'Error no param filter set!');
        }
        $filter = $parameter['filter'];

        // how many entries should be returned.
        $maxResults = 50;
        if(isset($parameter['max'])) {
            $maxResults = intval($parameter['max']);
        }

        $type = -1;
        $error = -1;
        $detailsFilter = null;
        $detailsExcludeFilter = null;
        switch (strtolower($filter)) {
            case 'serviceunavailableexception':
                $type = 5;
                $error = 2;
                $detailsFilter = 'ServiceUnavailableException';
                break;
            case 'pagenotfoundexception':
                $type = 5;
                $error = 2;
                $detailsFilter = 'PageNotFoundException';
                break;
            case 'otherexceptions':
                $type = 5;
                $error = 2;
                $detailsExcludeFilter = ['ServiceUnavailableException', 'PageNotFoundException'];
                break;
            case 'failedlogins':
                $type = 255;
                $error = 3;
                break;
            case 'error':
                $error = 2;
                break;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_log');
        $queryBuilder->resetRestrictions();

        if($maxResults > 0) {
            $queryBuilder
                ->select('uid', 'tstamp', 'details', 'IP')
                ->setMaxResults($maxResults);
        } else {
            $queryBuilder->count('uid');
        }

        $queryBuilder
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq(
                    'error',
                    $error
                )
            );
        if ($type !== -1) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                'type',
                $type
            ));
        }
        if ($detailsFilter !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->like(
                'details',
                $queryBuilder->quote('%' . $detailsFilter . '%')
            ));
        }
        if ($detailsExcludeFilter !== null) {
            foreach ($detailsExcludeFilter as $ex) {
                $queryBuilder->andWhere($queryBuilder->expr()->notLike(
                    'details',
                    $queryBuilder->quote('%' . $ex . '%')
                ));
            }
        }

        if($maxResults > 0) {
            $log = $queryBuilder->executeQuery()->fetchAllAssociative();
        } else {
            // rowCount() is not reliable for SELECT queries, COUNT() is
            $log = (int)$queryBuilder->executeQuery()->fetchOne();
        }
        if(empty($log)) {
            return new OperationResult(true);
        }

        return new OperationResult(true, [[ 'logs' => $log ]]);
    }
}
