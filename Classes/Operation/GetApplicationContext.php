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

use TYPO3\CMS\Core\Core\Environment;
use Psr\Http\Message\ServerRequestInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

class GetApplicationContext implements IOperation {
    /**
     * @param array $parameter None
     * @return OperationResult the current application context
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $applicationContext = Environment::getContext();

        return new OperationResult(true, [[ 'data' => $applicationContext->__toString() ]]);
    }
}
