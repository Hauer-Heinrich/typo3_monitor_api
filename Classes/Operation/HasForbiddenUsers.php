<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 * Modified by www.hauer-heinrich.de
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
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

class HasForbiddenUsers implements IOperation {

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        if (!isset($parameter['usernames']) || empty($parameter['usernames'])) {
            // throw new InvalidArgumentException('no usernames set');
            return new OperationResult(false, [], 'Error no param usernames set!');
        }

        $usernames = explode(',', htmlspecialchars(strip_tags(trim($parameter['usernames'])), ENT_QUOTES, "UTF-8"));

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder
            ->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $queryBuilder->select('uid', 'username')->from('be_users');

        foreach ($usernames as $username) {
            $queryBuilder->orWhere($queryBuilder->expr()->eq(
                'username',
                $queryBuilder->createNamedParameter($username)
            ));
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq('be_users.disable', 1)
        );

        // Query once (previously executed twice; rowCount() is not reliable for SELECT)
        $users = $queryBuilder->executeQuery()->fetchAllAssociative();

        return new OperationResult(true, [
            [
                'bool' => $users !== [],
                'users' => $users,
            ]
        ]);
    }
}
