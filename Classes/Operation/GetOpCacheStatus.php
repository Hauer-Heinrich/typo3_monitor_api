<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


class GetOpCacheStatus implements IOperation, SingletonInterface
{
    /**
     * Get the current database version
     *
     * @param array $parameter None
     * @return OperationResult the current database version
     */
    public function execute(array $parameter = []): OperationResult
    {
        /** @var OpcodeCacheService $opCacheService */
        $opCacheService = GeneralUtility::makeInstance(OpcodeCacheService::class);

        $allActive = $opCacheService->getAllActive();
        return new OperationResult(true, [[ 'OPcache' => [$allActive['OPcache']] ]]);
    }
}
