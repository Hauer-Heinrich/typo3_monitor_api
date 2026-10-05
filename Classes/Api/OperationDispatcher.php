<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Api;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use HauerHeinrich\Typo3MonitorApi\Authorization\OperationAuthorizationProvider;
use HauerHeinrich\Typo3MonitorApi\Domain\Model\AuthenticatedUser;
use HauerHeinrich\Typo3MonitorApi\Operation\IOperation;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * Runs the operation for an authenticated request:
 *
 *   /typo3-monitor-api/v1/<Operation>
 *     -> unknown operation                         404
 *     -> wrong HTTP method                         405
 *     -> not enabled globally / not for this user  403
 *     -> invalid JSON or parameters                400
 *     -> exception inside the operation            500 (details only in the TYPO3 log)
 *     -> otherwise                                 200 with the operation result
 *
 * Every response has the same format: [{"status": bool, "value": [...], "message": "..."}]
 */
final class OperationDispatcher {

    public const PATH_PREFIX = '/typo3-monitor-api/v1/';

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly OperationAuthorizationProvider $operationAuthorizationProvider,
        private readonly LoggerInterface $logger,
    ) {}

    public function dispatch(ServerRequestInterface $request, AuthenticatedUser $user): ResponseInterface {
        $path = rtrim($request->getUri()->getPath(), '/');
        $operationName = str_starts_with($path . '/', self::PATH_PREFIX) ? substr($path, strlen(self::PATH_PREFIX)) : '';

        $definition = $operationName !== '' ? OperationRegistry::findDefinition($operationName) : null;
        if ($definition === null) {
            return $this->errorResponse(404, 'Operation not found');
        }

        if ($request->getMethod() !== $definition->httpMethod) {
            return $this->errorResponse(405, sprintf('Operation %s requires HTTP method %s', $definition->name, $definition->httpMethod))
                ->withHeader('Allow', $definition->httpMethod);
        }

        if (!$this->operationAuthorizationProvider->isOperationAllowed($definition->name, $user)) {
            return $this->errorResponse(403, 'Operation not enabled');
        }

        try {
            $parameters = $this->validateParameters($definition, ...$this->getParameters($request));
        } catch (InvalidRequestException $e) {
            return $this->errorResponse(400, $e->getMessage());
        }

        try {
            $operation = $this->container->get($definition->className);
            if (!$operation instanceof IOperation) {
                throw new \LogicException(sprintf('%s must implement %s', $definition->className, IOperation::class), 1759654805);
            }
            $result = $operation->execute($parameters, $request);
        } catch (\Throwable $e) {
            $this->logger->error('Monitor API: operation ' . $definition->name . ' failed', ['exception' => $e]);
            return $this->errorResponse(500, 'Error while executing operation ' . $definition->name . ', see TYPO3 log');
        }

        return new JsonResponse([$result->toArray()]);
    }

    /**
     * Parameters come from the JSON body or, if there is no body, from the query string.
     *
     * @return array{0: array<mixed>, 1: bool} [parameters, fromQueryString]
     * @throws InvalidRequestException
     */
    private function getParameters(ServerRequestInterface $request): array {
        $body = trim((string)$request->getBody());
        if ($body === '') {
            return [$request->getQueryParams(), true];
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new InvalidRequestException('Request body is not a valid JSON object');
        }

        // Legacy format from the README: [{"extensionKey": "news"}]
        if ($data !== [] && array_is_list($data)) {
            if (count($data) !== 1 || !is_array($data[0])) {
                throw new InvalidRequestException('Request body must be a JSON object, e.g. {"extensionKey": "news"}');
            }
            $data = $data[0];
        }

        return [$data, false];
    }

    /**
     * @return array<string, string|bool|int>
     * @throws InvalidRequestException
     */
    private function validateParameters(OperationDefinition $definition, array $parameters, bool $fromQueryString): array {
        $validated = [];
        foreach ($parameters as $name => $value) {
            $name = (string)$name;
            if (!isset($definition->parameters[$name])) {
                throw new InvalidRequestException($definition->parameters === []
                    ? sprintf('Operation %s has no parameters, "%s" given', $definition->name, $name)
                    : sprintf('Parameter "%s" is not allowed for operation %s (allowed: %s)', $name, $definition->name, implode(', ', array_keys($definition->parameters))));
            }

            $type = $definition->parameters[$name];
            if ($fromQueryString && is_string($value)) {
                // The query string only knows strings
                $value = $this->convertQueryValue($value, $type);
            }
            if (gettype($value) !== $type) {
                throw new InvalidRequestException(sprintf('Parameter "%s" must be of type %s', $name, $type));
            }
            $validated[$name] = $value;
        }

        foreach ($definition->required as $name) {
            if (!array_key_exists($name, $validated)) {
                throw new InvalidRequestException(sprintf('Parameter "%s" is required for operation %s', $name, $definition->name));
            }
        }

        return $validated;
    }

    private function convertQueryValue(string $value, string $type): string|bool|int {
        return match (true) {
            $type === 'boolean' && in_array(strtolower($value), ['1', 'true'], true) => true,
            $type === 'boolean' && in_array(strtolower($value), ['0', 'false'], true) => false,
            $type === 'integer' && preg_match('/^-?\d+$/', $value) === 1 => (int)$value,
            default => $value,
        };
    }

    private function errorResponse(int $status, string $message): ResponseInterface {
        return new JsonResponse([(new OperationResult(false, [], $message))->toArray()], $status);
    }
}
