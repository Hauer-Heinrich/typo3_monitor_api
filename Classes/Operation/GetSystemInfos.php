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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;
use HauerHeinrich\Typo3MonitorApi\Api\OperationRegistry;
use HauerHeinrich\Typo3MonitorApi\Authorization\OperationAuthorizationProvider;


/**
 *
 */
class GetSystemInfos implements IOperation {
    /**
     * @var array Available info scopes
     */
    protected array $scopes = ['all'];

    /**
     * Operations included in the summary, optionally with parameters
     */
    protected array $methodList = [
        'GetDiskSpace' => [],
        'GetPHPVersion' => [],
        'GetTYPO3Version' => [],
        'HasUpdate' => [],
        'HasSecurityUpdate' => [],
        'GetLastSchedulerRun' => [],
        'GetLastExtensionListUpdate' => [],
        'GetDatabaseVersion' => [],
        'GetApplicationContext' => [],
        'GetTotalLogFilesSize' => [],
        'GetOpCacheStatus' => [],
        'GetExtensionList' => [
            'scopes' => 'local',
            'withUpdateInfo' => true,
        ],
        'GetLogResults' => [
            'filter' => 'error',
            'max' => 5,
        ],
        'GetInsecureExtensionList' => [],
        'GetOutdatedExtensionList' => [],
        // previously 'HasExtensionUpdateList' => 'loaded', the string was silently ignored
        'HasExtensionUpdateList' => ['scope' => 'loaded'],
    ];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly OperationAuthorizationProvider $operationAuthorizationProvider,
    ) {}

    /**
     * Only includes operations the current user may call itself, otherwise GetSystemInfos
     * would bypass the per-operation permissions.
     *
     * @param array $parameter
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $scope = $parameter['scope'] ?? 'all';
        if(!in_array($scope, $this->scopes, true)) {
            return new OperationResult(false, [], 'Error parameter \'scope\' empty or not valid!');
        }

        $user = OperationAuthorizationProvider::getUserFromRequest($request);
        $resultArray = [];
        foreach ($this->methodList as $operationName => $operationParameters) {
            $definition = OperationRegistry::findDefinition($operationName);
            if ($definition === null || !$this->operationAuthorizationProvider->isOperationAllowed($operationName, $user)) {
                continue;
            }

            try {
                $operation = $this->container->get($definition->className);
                $resultArray[$operationName] = [$operation->execute($operationParameters, $request)->toArray()];
            } catch (\Throwable $e) {
                // One failing operation must not break the whole summary
                $resultArray[$operationName] = [(new OperationResult(false, [], $e->getMessage()))->toArray()];
            }
        }

        return new OperationResult(true, [$resultArray]);
    }
}
