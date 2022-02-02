<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Middleware;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\Http\Response;
use Pecee\SimpleRouter\SimpleRouter;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\JsonResponse;

use HauerHeinrich\Typo3MonitorApi\Authentication\BasicAuthenticationProvider;

class SimpleRouterMiddleware implements IMiddleware {

    public function handle(Request $request): void
    {
        $apiUserName = $_SERVER['PHP_AUTH_USER'];
        $apiUserPassword = $_SERVER['PHP_AUTH_PW'];

        $user = new \HauerHeinrich\Typo3MonitorApi\Domain\Model\User($apiUserName, $apiUserPassword);

        // User Authentication
        $basicAuth = new \HauerHeinrich\Typo3MonitorApi\Authentication\BasicAuthenticationProvider($user);
        $isUserAuthenticated = $basicAuth->isValid();

        if($isUserAuthenticated) {

        }

        /** @var JsonResponse $response */
        $response = GeneralUtility::makeInstance(JsonResponse::class);

        SimpleRouter::redirect('/', '/', 401);
    }
}
