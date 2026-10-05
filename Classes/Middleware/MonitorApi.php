<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Middleware;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use HauerHeinrich\Typo3MonitorApi\Api\OperationDispatcher;
use HauerHeinrich\Typo3MonitorApi\Authentication\BasicAuthenticationProvider;
use HauerHeinrich\Typo3MonitorApi\Authentication\IpAuthenticationProvider;
use HauerHeinrich\Typo3MonitorApi\Authentication\LoginRateLimiter;
use HauerHeinrich\Typo3MonitorApi\Authorization\OperationAuthorizationProvider;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\User;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;

/**
 * Handles all requests below /typo3-monitor-api:
 * HTTPS -> global IP whitelist -> brute-force protection -> Basic-Auth
 * -> per-user IP whitelist -> per-user operation permissions -> API routing.
 * All other requests are passed on unchanged.
 *
 * Credentials are only accepted from the Authorization header (as sent by scripts/monitoring tools).
 * No "WWW-Authenticate" challenge is ever sent, so browsers never show a login dialog.
 */
final class MonitorApi implements MiddlewareInterface {

    private const PATH_PREFIX = '/typo3-monitor-api';

    public function __construct(
        private readonly IpAuthenticationProvider $ipAuthenticationProvider,
        private readonly BasicAuthenticationProvider $basicAuthenticationProvider,
        private readonly LoginRateLimiter $loginRateLimiter,
        private readonly OperationAuthorizationProvider $operationAuthorizationProvider,
        private readonly OperationDispatcher $operationDispatcher,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LoggerInterface $logger,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if (!$this->isApiPath($request->getUri()->getPath())) {
            return $handler->handle($request);
        }

        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);
        $debug = (bool)($config['debugOutput'] ?? false);

        if ((bool)($config['requireHttps'] ?? true) && !$this->isHttps($request)) {
            return $this->deny($request, $debug, 403, 'HTTPS required');
        }

        if (!$this->ipAuthenticationProvider->isAllowed($request)) {
            return $this->deny($request, $debug, 403, 'IP not allowed');
        }

        if ($this->loginRateLimiter->isBlocked($request)) {
            $this->logger->warning('Monitor API: request blocked after too many failed logins', [
                'ip' => $this->getRemoteAddress($request),
            ]);
            return $this->deny($request, $debug, 429, 'Too many failed login attempts');
        }

        [$username, $password] = $this->getCredentials($request);
        $user = new User($username, $password);

        $authenticatedUser = $this->basicAuthenticationProvider->authenticate($user);
        $failureReason = match (true) {
            $authenticatedUser === null => 'wrong credentials',
            !$this->ipAuthenticationProvider->isAllowedForUser($request, $authenticatedUser) => 'IP not allowed for this user',
            !$this->operationAuthorizationProvider->hasAnyAllowedOperation($authenticatedUser) => 'user has no API permissions',
            default => null,
        };

        if ($failureReason !== null) {
            // Same response for every reason: valid passwords must not be distinguishable from wrong ones
            $this->loginRateLimiter->registerFailedAttempt($request);
            $this->logger->warning('Monitor API: authentication failed (' . $failureReason . ')', [
                'username' => $username,
                'ip' => $this->getRemoteAddress($request),
            ]);
            return $this->deny($request, $debug, 401, 'Name or password wrong or not set');
        }

        $request = $request->withAttribute(OperationAuthorizationProvider::REQUEST_ATTRIBUTE_USER, $authenticatedUser);

        return $this->operationDispatcher->dispatch($request, $authenticatedUser);
    }

    /**
     * Matches "/typo3-monitor-api" and everything below "/typo3-monitor-api/",
     * but not e.g. "/typo3-monitor-api-foo".
     */
    private function isApiPath(string $path): bool {
        return $path === self::PATH_PREFIX || str_starts_with($path, self::PATH_PREFIX . '/');
    }

    /**
     * Reads Basic-Auth credentials from the Authorization header.
     *
     * PHP_AUTH_USER / PHP_AUTH_PW are no separate source: with mod_php, PHP fills them from the very same
     * Authorization header and does not always pass the header on. They are read for compatibility only.
     *
     * @return array{0: string, 1: string} [username, password]; empty strings if not available
     */
    private function getCredentials(ServerRequestInterface $request): array {
        $serverParams = $request->getServerParams();

        $username = (string)($serverParams['PHP_AUTH_USER'] ?? '');
        $password = (string)($serverParams['PHP_AUTH_PW'] ?? '');
        if ($username !== '') {
            return [$username, $password];
        }

        // REDIRECT_HTTP_AUTHORIZATION is not mapped to a PSR-7 header, hence the fallback
        $header = $request->getHeaderLine('Authorization');
        if ($header === '') {
            $header = (string)($serverParams['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        }

        if (stripos($header, 'basic ') !== 0) {
            return ['', ''];
        }

        $decoded = base64_decode(trim(substr($header, 6)), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return ['', ''];
        }

        // Limit 2: passwords may contain colons themselves
        [$username, $password] = explode(':', $decoded, 2);

        return [$username, $password];
    }

    /**
     * Respects TYPO3's reverseProxySSL setting when running behind a TLS-terminating proxy
     */
    private function isHttps(ServerRequestInterface $request): bool {
        $normalizedParams = $request->getAttribute('normalizedParams');

        return $normalizedParams instanceof NormalizedParams && $normalizedParams->isHttps();
    }

    private function getRemoteAddress(ServerRequestInterface $request): string {
        $normalizedParams = $request->getAttribute('normalizedParams');

        return $normalizedParams instanceof NormalizedParams ? $normalizedParams->getRemoteAddress() : '';
    }

    /**
     * Without debug output, the API is hidden behind the regular 404 page of the site
     * (errorHandling in the site configuration).
     */
    private function deny(ServerRequestInterface $request, bool $debug, int $status, string $message): ResponseInterface {
        if (!$debug) {
            return GeneralUtility::makeInstance(ErrorController::class)
                ->pageNotFoundAction($request, 'Not found');
        }

        // Intentionally no "WWW-Authenticate" header, even for 401: it would make browsers show a login dialog
        return new JsonResponse([
            'success' => false,
            'exception' => $message,
            'message' => $message,
        ], $status);
    }
}
