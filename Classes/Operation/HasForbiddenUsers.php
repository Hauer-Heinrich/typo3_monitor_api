<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 * Modified by www.hauer-heinrich.de
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use HauerHeinrich\Typo3MonitorApi\Exception\InvalidArgumentException;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 *
 */
class HasForbiddenUsers implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        if (!isset($parameter['usernames']) || empty($parameter['usernames'])) {
            // throw new InvalidArgumentException('no usernames set');
            return new OperationResult(false, [], 'Error no param usernames set!');
        }

        $usernames = explode(',', htmlspecialchars(strip_tags(trim($parameter['usernames'])), ENT_QUOTES, "UTF-8"));

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(ConnectionPool::class)->getQueryBuilderForTable('be_users');
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

        return new OperationResult(true, [
            [
                'bool' => $queryBuilder->execute()->rowCount() > 0,
                'users' => $queryBuilder->execute()->fetchAll()
            ]
        ]);
    }
}
