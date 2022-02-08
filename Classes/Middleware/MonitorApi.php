<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Middleware;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use HauerHeinrich\Typo3MonitorApi\Authentication\IpAuthenticationProvider;
use HauerHeinrich\Typo3MonitorApi\Authentication\BasicAuthenticationProvider;
use HauerHeinrich\Typo3MonitorApi\Authentication\OperationAuthorizationProvider;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\User;

class MonitorApi implements MiddlewareInterface {

    /**
     * Calls the "unavailableAction" of the error controller if the system is in maintenance mode.
     * This only applies if the REMOTE_ADDR does not match the devIpMask
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Psr\Http\Message\UriInterface $requestedUri */
        $requestedUri = $request->getUri();
        $requestedPath = $requestedUri->getPath();

        if($this->startsWith($requestedPath, '/typo3-monitor-api')) {
            // check IP white
            if(IpAuthenticationProvider::checkIpAddress($request)) {
                // User initialization
                $apiUserName = array_key_exists('PHP_AUTH_USER', $request->getServerParams()) ? $request->getServerParams()['PHP_AUTH_USER'] : '';
                $apiUserPassword = array_key_exists('PHP_AUTH_PW', $request->getServerParams()) ? $request->getServerParams()['PHP_AUTH_PW'] : '';

                if(empty($apiUserName)) {
                    $auth_token = null;
                    if (array_key_exists('HTTP_AUTHORIZATION', $request->getServerParams())) {
                        $auth_token = $request->getServerParams()['HTTP_AUTHORIZATION'];
                    } elseif (array_key_exists('REDIRECT_HTTP_AUTHORIZATION', $request->getServerParams())) {
                        $auth_token = $request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'];
                    }

                    if ($auth_token != null) {
                        if (strpos(strtolower($auth_token), 'basic') === 0) {
                            list($apiUserName, $apiUserPassword) = explode(':', base64_decode(substr($auth_token, 6)));
                        }
                    }
                }

                if(!empty($apiUserName) && !empty($apiUserPassword)) {
                    $user = new User($apiUserName, $apiUserPassword);

                    // User Authentication
                    $basicAuth = new BasicAuthenticationProvider($request, $user);
                    $isUserAuthenticated = $basicAuth->isValid();

                    if($isUserAuthenticated) {
                        return \HauerHeinrich\Typo3MonitorApi\Utility\RoutingConfig::setRoutingConfigs($request, $user);
                    }
                }

                // TODO: log $basicAuth->getLogData();#
                // throw exception
                $response = GeneralUtility::makeInstance(JsonResponse::class);
                $response = $response->withStatus(401, 'not allowed');
                $response->getBody()->write('Name or pasword wrong or not set');

                return $response;
            }

            $response = GeneralUtility::makeInstance(JsonResponse::class);
            $response = $response->withStatus(401, 'not allowed');
            $response->getBody()->write(json_encode(['success' => false, 'exception' => 'IP not allowed']));

            return $response;
        }

        return $handler->handle($request);
    }

    /**
     * startsWith
     *
     * @param string $haystack
     * @param string $needle
     * @return void
     */
    public function startsWith(string $haystack, string $needle) {
        $length = strlen( $needle );
        return substr( $haystack, 0, $length ) === $needle;
    }
}
