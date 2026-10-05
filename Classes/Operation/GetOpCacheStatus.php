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
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


class GetOpCacheStatus implements IOperation {
    public function __construct(
        private readonly OpcodeCacheService $opcodeCacheService,
    ) {}

    /**
     * Get the current database version
     *
     * @param array $parameter None
     * @return OperationResult the current database version
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $allActive = $this->opcodeCacheService->getAllActive();

        if(array_key_exists('OPcache', $allActive)) {
            return new OperationResult(true, [[ 'OPcache' => [$allActive['OPcache']] ]]);
        }

        return new OperationResult(true, [[ 'OPcache' => [false] ]]);
    }
}
