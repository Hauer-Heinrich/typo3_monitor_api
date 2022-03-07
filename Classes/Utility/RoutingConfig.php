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
            'HasMissingDefaultMailSettings' => [
                'httpMethod' => 'GET',
                'parameter' => [],
            ],
            'UpdateMinorTypo3' => [
                'httpMethod' => 'PATCH',
                'parameter' => [],
            ],
        ];

        $allowedHttpMethods = ['GET', 'POST', 'PATCH'];

        foreach ($methodsAllowed as $methodKey => $methodOptions) {
            $methodName = isset($methodKey[0]) ? $methodKey : $methodOptions;
            $httpMethod = 'GET';
            if(isset($methodOptions['httpMethod'])) {
                if(in_array($methodOptions['httpMethod'], $allowedHttpMethods)) {
                    $httpMethod = $methodOptions['httpMethod'];
                } else {
                    $response = $response->withStatus(405, 'Method Not Allowed');
                    $response->getBody()->write('Http method not allowed');
                }
            }

            Route::add('/typo3-monitor-api/v1/' . $methodName, function() use ($methodName, &$response, $user, $methodOptions, $request) {
                $response = self::UserAuth($response, $request, 'HauerHeinrich\\Typo3MonitorApi\\Operation\\' . $methodName, $user, $methodOptions);
            }, $httpMethod);
        }

        Route::add('/typo3-monitor-api/v1/([a-z-0-9-_=!?@]*)', function() use (&$response) {
            $response = $response->withStatus(404, 'Not found');
            $response->getBody()->write('Operation not found');
        }, 'get');

        Route::run('/');

        return $response;
    }

    /**
     * UserAuth
     *
     * @param JsonResponse $response
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @param string $classNameSpace
     * @param User $user
     * @param array $methodOptions
     * @return \TYPO3\CMS\Core\Http\JsonResponse
     */
    protected static function UserAuth($response, \TYPO3\CMS\Core\Http\ServerRequest $request,
        string $classNameSpace, User $user, array $methodOptions
    ): \TYPO3\CMS\Core\Http\JsonResponse
    {
        // TODO: isUserAuthorized
        if(\HauerHeinrich\Typo3MonitorApi\Authorization\UserAuthorizationProvider::isUserAuthorized($response, $classNameSpace, $user)) {
            // TODO: check content of body from request if given params are valid === allowed methodOptions
            // self::areMethodOptionsValid($methodOptions)
            $params = [];
            $params['request'] = $request;
            $class = GeneralUtility::makeInstance($classNameSpace);
            $resultJSON = json_encode([$class->execute($params)->toArray()]);

            $response = $response->withStatus(200, 'allowed');
            $response->getBody()->write($resultJSON);
        } else {
            $response = $response->withStatus(401, 'Not allowed');
        }

        return $response;
    }

    public static function areMethodOptionsValid(array $methodOptions) {

    }
}
