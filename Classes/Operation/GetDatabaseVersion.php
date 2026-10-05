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

use Doctrine\DBAL\Exception as DBALException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


class GetDatabaseVersion implements IOperation {
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Get the current database version
     *
     * @param array $parameter None
     * @return OperationResult the current database version
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $db = [];
        foreach ($this->connectionPool->getConnectionNames() as $connectionName) {
            try {
                $db[$connectionName] = $this->connectionPool
                    ->getConnectionByName($connectionName)
                    ->getServerVersion();
            } catch (DBALException $e) {
                return new OperationResult(false, [], 'Can\'t connect to DB (ConnectionPool)!');
            }
        }

        return new OperationResult(true, [[ 'connection' => [$db] ]]);
    }
}
