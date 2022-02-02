<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\JsonResponse;

use HauerHeinrich\Typo3MonitorApi\Utility\Route;

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

        // TODO: isUserAuthorized
        // Route::add('/typo3-monitor-api/([a-z-0-9-]*)', function($operationUrl) use (&$request, &$response, &$returnValue) {
        //     \HauerHeinrich\Typo3MonitorApi\Authorization\UserAuthorizationProvider::isUserAuthorized($request, '');
        // }, 'post');

        foreach ($methodsAllowed as $method) {
            // TODO: add closure::bind() to Route::add() method
            Route::add('/typo3-monitor-api/' . $method ."()", function() use ($method, &$response) {
                $response = self::UserAuth($response, 'HauerHeinrich\\Typo3MonitorApi\\Operation\\' . $method);
            }, 'post');
        }

        Route::run('/');

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
