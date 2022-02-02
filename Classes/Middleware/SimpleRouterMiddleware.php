<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Middleware;

use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\Http\Response;
use Pecee\SimpleRouter\SimpleRouter;
use Pecee\SimpleRouter\Exceptions\HttpException;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\JsonResponse;

use HauerHeinrich\Typo3MonitorApi\Authentication\BasicAuthenticationProvider;

class SimpleRouterMiddleware implements IMiddleware {

    public function handle(Request $request): void
    {
        $apiUserName = $request->getUser();
        $apiUserPassword = $request->getPassword();

        $user = new \HauerHeinrich\Typo3MonitorApi\Domain\Model\User($apiUserName, $apiUserPassword);

        // User Authentication
        $basicAuth = new BasicAuthenticationProvider($user);
        $isUserAuthenticated = $basicAuth->isValid();

        DebuggerUtility::var_dump($request, "request");

        if($isUserAuthenticated) {
            DebuggerUtility::var_dump("User Logged In");
        }

        throw new \UnexpectedValueException('Invalid data');

        throw new HttpException('Restricted. Access has been blocked', 403);

        // /** @var JsonResponse $response */
        // $response = GeneralUtility::makeInstance(JsonResponse::class);
    }
}
