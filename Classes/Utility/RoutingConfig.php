<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\JsonResponse;

use HauerHeinrich\Typo3MonitorApi\Utility\Route;

use Pecee\SimpleRouter\SimpleRouter;

class RoutingConfig {
    static function addRouting($request): JsonResponse {
        $returnValue = [];

        /** @var JsonResponse $response */
        $response = GeneralUtility::makeInstance(JsonResponse::class);

        $methodsAllowed = [
            'CheckPathExists',
            'GetDiskSpace',
            'GetExtensionList',
            'GetExtensionVersion',
            'GetFilesystemChecksum',
            'GetPHPVersion',
            'GetTYPO3Version',
            'GetLogResults',
            'HasForbiddenUsers',
            'HasUpdate',
            'HasSecurityUpdate',
            'GetLastSchedulerRun',
            'GetLastExtensionListUpdate',
            'GetDatabaseVersion',
            'GetApplicationContext',
            'GetInsecureExtensionList',
            'GetOutdatedExtensionList',
            'GetTotalLogFilesSize',
            'HasRemainingUpdates',
            'GetZabbixLogFileSize',
            'HasExtensionUpdate',
            'HasExtensionUpdateList',
            'HasDeprecationLogEnabled',
            'GetProgramVersion',
            'GetFeatureValue',
            'GetOpCacheStatus',
            'GetFileSpoolValue',
            'GetZabbixClientLock',
            'GetDatabaseAnalyzerSummary',
            'HasFailedSchedulerTask',
            'GetSystemInfos',
            'GetZabbixFeLog',
            'HasMissingDefaultMailSettings',
            'UpdateMinorTypo3',
        ];

        SimpleRouter::group(['middleware' => \HauerHeinrich\Typo3MonitorApi\Middleware\SimpleRouterMiddleware::class], function () {
            SimpleRouter::post('/typo3-monitor-api/v1/test', function () {
                DebuggerUtility::var_dump($className);
                // Uses Auth Middleware
            });

            SimpleRouter::post('/typo3-monitor-api/v1/user/profile', function () {
                DebuggerUtility::var_dump($className);
                // Uses Auth Middleware
            });
        });

        // SimpleRouter::post('/typo3-monitor-api/v1/{method}/{params?}', function($className, $params = null) {
        //     DebuggerUtility::var_dump($className);
        //     DebuggerUtility::var_dump($params);
        //     die();
        // })->where([ 'method' => '[A-Za-z]+' ]);

        // Start the routing
        SimpleRouter::start();

        return $response;
    }

    protected static function UserAuth($response, string $classNameSpace)
    {

        // TODO: isUserAuthorized
        if(\HauerHeinrich\Typo3MonitorApi\Authorization\UserAuthorizationProvider::isUserAuthorized($request, '')) {
            $class = GeneralUtility::makeInstance($classNameSpace);
            $resultJSON = json_encode([$class->execute()->toArray()]);

            $response = $response->withStatus(200, 'allowed');
            $response->getBody()->write($resultJSON);
        } else {
            $response = $response->withStatus(401, 'Not allowed');
        }

        return $response;
    }
}
