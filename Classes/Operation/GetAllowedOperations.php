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
use HauerHeinrich\Typo3MonitorApi\Api\OperationRegistry;
use HauerHeinrich\Typo3MonitorApi\Authorization\OperationAuthorizationProvider;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * Returns the operations the current user can call, with HTTP method and parameters
 */
class GetAllowedOperations implements IOperation {
    public function __construct(
        private readonly OperationAuthorizationProvider $operationAuthorizationProvider,
    ) {}

    /**
     * @param array $parameter None
     * @return OperationResult the operations the current user may call
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $user = OperationAuthorizationProvider::getUserFromRequest($request);

        $allowedOperations = [];
        foreach (OperationRegistry::getDefinitions() as $operationName => $definition) {
            if ($this->operationAuthorizationProvider->isOperationAllowed($operationName, $user)) {
                $allowedOperations[$operationName] = $definition->toArray();
            }
        }

        return new OperationResult(true, [[ 'methods' => $allowedOperations ]]);
    }
}
