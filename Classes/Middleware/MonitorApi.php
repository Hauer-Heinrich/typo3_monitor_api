<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Middleware;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use HauerHeinrich\Typo3MonitorApi\Authentication\BasicAuthenticationProvider;
use HauerHeinrich\Typo3MonitorApi\Authentication\IpAuthenticationProvider;
use HauerHeinrich\Typo3MonitorApi\Authentication\LoginRateLimiter;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\User;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;
use HauerHeinrich\Typo3MonitorApi\Utility\RoutingConfig;
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

/**
 * Handles all requests below /typo3-monitor-api:
 * IP whitelist -> brute-force protection -> Basic-Auth -> API routing.
 * All other requests are passed on unchanged.
 */
final class MonitorApi implements MiddlewareInterface
{
    private const PATH_PREFIX = '/typo3-monitor-api';

    public function __construct(
        private readonly IpAuthenticationProvider $ipAuthenticationProvider,
        private readonly BasicAuthenticationProvider $basicAuthenticationProvider,
        private readonly LoginRateLimiter $loginRateLimiter,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly LoggerInterface $logger,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isApiPath($request->getUri()->getPath())) {
            return $handler->handle($request);
        }

        $config = $this->extensionConfiguration->get(Configuration::EXTENSION_KEY);
        $debug = (bool)($config['debugOutput'] ?? false);

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

        if (!$this->basicAuthenticationProvider->authenticate($user)) {
            $this->loginRateLimiter->registerFailedAttempt($request);
            $this->logger->warning('Monitor API: authentication failed', [
                'username' => $username,
                'ip' => $this->getRemoteAddress($request),
            ]);
            return $this->deny($request, $debug, 401, 'Name or password wrong or not set');
        }

        return GeneralUtility::makeInstance(RoutingConfig::class)->setRoutingConfigs($request, $user);
    }

    /**
     * Matches "/typo3-monitor-api" and everything below "/typo3-monitor-api/",
     * but not e.g. "/typo3-monitor-api-foo".
     */
    private function isApiPath(string $path): bool
    {
        return $path === self::PATH_PREFIX || str_starts_with($path, self::PATH_PREFIX . '/');
    }

    /**
     * Reads Basic-Auth credentials from PHP_AUTH_* or the Authorization header.
     *
     * @return array{0: string, 1: string} [username, password]; empty strings if not available
     */
    private function getCredentials(ServerRequestInterface $request): array
    {
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

    private function getRemoteAddress(ServerRequestInterface $request): string
    {
        $normalizedParams = $request->getAttribute('normalizedParams');

        return $normalizedParams instanceof NormalizedParams ? $normalizedParams->getRemoteAddress() : '';
    }

    /**
     * Without debug output, the API is hidden behind the regular 404 page of the site
     * (errorHandling in the site configuration).
     */
    private function deny(ServerRequestInterface $request, bool $debug, int $status, string $message): ResponseInterface
    {
        if (!$debug) {
            return GeneralUtility::makeInstance(ErrorController::class)
                ->pageNotFoundAction($request, 'Not found');
        }

        $response = new JsonResponse([
            'success' => false,
            'exception' => $message,
            'message' => $message,
        ], $status);

        if ($status === 401) {
            $response = $response->withHeader('WWW-Authenticate', 'Basic realm="TYPO3 Monitor API"');
        }

        return $response;
    }
}
