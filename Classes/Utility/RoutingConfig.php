<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\User;
use HauerHeinrich\Typo3MonitorApi\Utility\Route;

class RoutingConfig {
    static function setRoutingConfigs(\TYPO3\CMS\Core\Http\ServerRequest $request, User $user): JsonResponse {
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
            'HasExtensionUpdate',
            'HasExtensionUpdateList',
            'HasDeprecationLogEnabled',
            'GetProgramVersion',
            'GetFeatureValue',
            'GetOpCacheStatus',
            'GetFileSpoolValue',
            'GetDatabaseAnalyzerSummary',
            'HasFailedSchedulerTask',
            'GetSystemInfos',
            'HasMissingDefaultMailSettings',
            'UpdateMinorTypo3',
        ];

        foreach ($methodsAllowed as $method) {
            Route::add('/typo3-monitor-api/v1/' . $method ."()", function() use ($method, &$response, $user) {
                $response = self::UserAuth($response, 'HauerHeinrich\\Typo3MonitorApi\\Operation\\' . $method, $user);
            }, 'post');
        }

        Route::add('/typo3-monitor-api/v1/([a-z-0-9-_=!?@]*)', function() use (&$response) {
            $response = $response->withStatus(404, 'Not found');
            $response->getBody()->write('Not found');
        }, 'post');

        Route::run('/');

        return $response;
    }

    protected static function UserAuth($response, string $classNameSpace, User $user)
    {
        // TODO: isUserAuthorized
        if(\HauerHeinrich\Typo3MonitorApi\Authorization\UserAuthorizationProvider::isUserAuthorized($response, $classNameSpace, $user)) {
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
