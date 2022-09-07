<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Utility;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Http\JsonResponse;
use \HauerHeinrich\Typo3MonitorApi\Domain\Model\User;
use \HauerHeinrich\Typo3MonitorApi\Utility\Route;

class RoutingConfig {
    use \HauerHeinrich\Typo3MonitorApi\Utility\CheckBodyContent;

    protected $allowedHttpMethods = [];

    protected $methodsAllowed = [];

    public function __construct() {
        $this->setAllowedHttpMethods();
        $this->setMethodsAllowed();
    }

    public function setAllowedHttpMethods(): void
    {
        $this->allowedHttpMethods = [
            'GET', 'POST', 'PATCH'
        ];
    }

    public function getAllowedHttpMethods(): array
    {
        return $this->allowedHttpMethods;
    }

    public function setMethodsAllowed(): void {
        $this->methodsAllowed = [
            'GetAllowedOperations' => [],
            'CheckPathExists' => [
                'parameters' => [
                    'path' => 'string' // (required)
                ]
            ],
            'GetDiskSpace' => [
                'parameters' => [
                    'format' => 'boolean' // (optional)
                ]
            ],
            'GetExtensionList' => [
                'parameters' => [
                    'scopes' => '', // (optional) possibilities: system, local
                    'withUpdateInfo' => 'boolean' // (optional)
                ]
            ],
            'GetExtensionVersion' => [
                'parameters' => [
                    'extensionKey' => 'string' // (required)
                ],
            ],
            'GetFilesystemChecksum' => [ // TODO: don't know exactly what this is for
                'parameters' => [
                    'path' => 'string',
                    'getSingleChecksums' => 'string'
                ]
            ],
            'GetPHPVersion' => [],
            'GetTYPO3Version' => [],
            'GetLogResults' => [
                'parameters' => [
                    'filter' => 'string', // (required) possibilities: serviceunavailableexception, pagenotfoundexception, otherexceptions, failedlogins, error
                    'max' => 'integer' // maxResults (optional) default: 50 - if you set to "0" only count() is returend
                ]
            ],
            'HasForbiddenUsers' => [
                'parameters' => [
                    'usernames' => 'string' // comma seperated (required)
                ]
            ],
            'HasUpdate' => [],
            'HasSecurityUpdate' => [],
            'GetLastSchedulerRun' => [],
            'GetLastExtensionListUpdate' => [],
            'GetDatabaseVersion' => [],
            'GetApplicationContext' => [],
            'GetInsecureExtensionList' => [],
            'GetOutdatedExtensionList' => [],
            'GetTotalLogFilesSize' => [],
            'HasRemainingUpdates' => [],
            'HasExtensionUpdate' => [],
            'HasExtensionUpdateList' => [],
            'HasDeprecationLogEnabled' => [],
            'GetProgramVersion' => [
                'parameters' => [
                    'program' => 'string' // (required) possibilities: openssl, gm, im, optipng, jpegoptim, webp
                ]
            ],
            'GetFeatureValue' => [
                'parameters' => [
                    'feature' => 'string' // (required) possibilities: cache, context, image, mail, passwordhashing
                ]
            ],
            'GetOpCacheStatus' => [],
            'GetFileSpoolValue' => [
                'parameters' => [
                    'value' => 'string' // (optional) possibilities: pending, sending, lag
                ]
            ],
            'GetDatabaseAnalyzerSummary' => [],
            'HasFailedSchedulerTask' => [],
            'GetSystemInfos' => [],
            'HasMissingDefaultMailSettings' => [
                'httpMethod' => 'GET',
                'parameters' => [],
            ],
            'UpdateMinorTypo3' => [
                'httpMethod' => 'PATCH',
                'parameters' => [],
            ],
        ];
    }

    public function getMethodsAllowed(): array {
        return $this->methodsAllowed;
    }

    public function setRoutingConfigs(\TYPO3\CMS\Core\Http\ServerRequest $request, User $user): JsonResponse {
        /** @var JsonResponse $response */
        $response = GeneralUtility::makeInstance(JsonResponse::class);

        foreach ($this->getMethodsAllowed() as $methodKey => $methodOptions) {
            $methodName = isset($methodKey[0]) ? $methodKey : $methodOptions;
            $httpMethod = 'GET';

            if(gettype($methodOptions) === 'string') {
                $methodOptions = [];
            }

            if(isset($methodOptions['httpMethod'])) {
                if(in_array($methodOptions['httpMethod'], $this->getAllowedHttpMethods())) {
                    $httpMethod = $methodOptions['httpMethod'];
                } else {
                    $response = $response->withStatus(405, 'Method Not Allowed');
                    $response->getBody()->write('Http method not allowed');
                }
            }

            Route::add('/typo3-monitor-api/v1/' . $methodName, function() use ($methodName, &$response, $user, $request) {
                $response = $this->UserAuth($response, $request, 'HauerHeinrich\\Typo3MonitorApi\\Operation\\' . $methodName, $user, $methodName);
            }, $httpMethod);
        }

        Route::add('/typo3-monitor-api/v1/([a-z-0-9-_=!?@]*)', function() use (&$response) {
            $response = $response->withStatus(404, 'Not found');
            $response->getBody()->write('Operation not found');
        }, 'GET');

        Route::run('/');

        return $response;
    }

    /**
     * UserAuth
     *
     * @param JsonResponse $response
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @param string $classNameSpace
     * @param \HauerHeinrich\Typo3MonitorApi\Domain\Model\User $user
     * @return \TYPO3\CMS\Core\Http\JsonResponse
     */
    protected function UserAuth($response, \TYPO3\CMS\Core\Http\ServerRequest $request,
        string $classNameSpace, User $user, string $methodName
    ): \TYPO3\CMS\Core\Http\JsonResponse
    {
        // TODO: code isUserAuthorized
        if(\HauerHeinrich\Typo3MonitorApi\Authorization\UserAuthorizationProvider::isUserAuthorized($response, $classNameSpace, $user)) {
            $params = [];
            $params['request'] = $request;

            if(!empty($params['request'])) {
                $params['queryParams'] = $params['request']->getQueryParams();
                $bodyArray = $this->getArrayFromBodyJson($params['request']);

                if(\is_array($bodyArray)) {
                    $params['body'] = $bodyArray;

                    // check if given parameters are allowed
                    if($this->areMethodOptionsValid($methodName, $bodyArray)) {
                        $class = GeneralUtility::makeInstance($classNameSpace);
                        $resultJSON = json_encode([$class->execute($bodyArray)->toArray()]);

                        $response = $response->withStatus(200, 'allowed');
                        $response->getBody()->write($resultJSON);
                        return $response;
                    } else {
                        $response = $response->withStatus(401, 'Given params not allowed');
                        return $response;
                    }
                }

                $response = $response->withStatus(401, 'No valid JSON');
                return $response;
            }
        }

        $response = $response->withStatus(401, 'Not allowed');

        return $response;
    }

    /**
     * areMethodOptionsValid
     * Checks given paramethers against the method->parameters defiend at $this->methodsAllowed.
     * Fails if one or more given parameter isn't set at $this->methodsAllowed.
     * Fails if type of given parameter value isn't allowed at $this->methodsAllowed.
     *
     * @param string $method - Operation e. g. "GetExtensionVersion"
     * @param array $methodParameters - request parameters e. g. [ 'extensionKey' => 'tt_address' ]
     * @return boolean
     */
    public function areMethodOptionsValid(string $method, array $methodParameters = []): bool {
        $allowedMethodParameters = array_key_exists($method, $this->methodsAllowed) ? $this->methodsAllowed[$method]['parameters'] : [];
        if(empty($allowedMethodParameters) && !empty($methodParameters)) {
            return false;
        }

        // only parameters/options are allowed that occur in $this->methodsAllowed
        foreach ($methodParameters as $key => $value) {
            if(array_key_exists($key, $allowedMethodParameters)) {
                // only if the value type is allowed (specified at $this->methodsAllowed[{$method}]['parameters'])
                $allowedParamType = $allowedMethodParameters[$key];
                if(gettype($value) === $allowedParamType) {
                    continue;
                }
                return false;
                break;
            }
            return false;
            break;
        }

        return true;
    }
}
