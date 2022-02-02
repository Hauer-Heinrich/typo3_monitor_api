<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use HauerHeinrich\Typo3MonitorApi\OperationResult;


interface IOperation
{
    /**
     * @param array $parameter Parameters for the operation
     * @return OperationResult The operation result
     */
    public function execute(array $parameter = []): OperationResult;
}
