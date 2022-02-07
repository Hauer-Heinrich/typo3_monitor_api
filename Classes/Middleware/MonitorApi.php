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

use HauerHeinrich\Typo3MonitorApi\Authorization\IpAuthorizationProvider;
use HauerHeinrich\Typo3MonitorApi\Authorization\HttpMethodAuthorizationProvider;
use HauerHeinrich\Typo3MonitorApi\Authorization\OperationAuthorizationProvider;

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
            if(\HauerHeinrich\Typo3MonitorApi\Authentication\IpAuthenticationProvider::checkIpAddress($request)) {

            }

            // User initialization
            $apiUserName = $request->getServerParams()['PHP_AUTH_USER'];
            $apiUserPassword = $request->getServerParams()['PHP_AUTH_PW'];

            $user = new \HauerHeinrich\Typo3MonitorApi\Domain\Model\User($apiUserName, $apiUserPassword);

            // User Authentication
            $basicAuth = new \HauerHeinrich\Typo3MonitorApi\Authentication\BasicAuthenticationProvider($request, $user);
            $isUserAuthenticated = $basicAuth->isValid();

            if($isUserAuthenticated) {
                return \HauerHeinrich\Typo3MonitorApi\Utility\RoutingConfig::setRoutingConfigs($request, $user);
            }

            // TODO: log $basicAuth->getLogData();#
            // throw exception
            $response = GeneralUtility::makeInstance(JsonResponse::class);
            $response = $response->withStatus(401, 'not allowed');
            $response->getBody()->write('User not allowed');

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
