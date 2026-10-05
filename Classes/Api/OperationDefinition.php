<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Api;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * One entry of Configuration/MonitorOperations.php
 */
final class OperationDefinition {

    public const ALLOWED_HTTP_METHODS = ['GET', 'POST', 'PATCH'];
    public const ALLOWED_PARAMETER_TYPES = ['string', 'boolean', 'integer'];

    /**
     * @param array<string, string> $parameters name => type
     * @param list<string> $required
     */
    public function __construct(
        public readonly string $name,
        public readonly string $className,
        public readonly string $httpMethod,
        public readonly array $parameters,
        public readonly array $required,
    ) {}

    /**
     * Validates the configuration, so a typo in MonitorOperations.php results in a clear error message.
     *
     * @throws \LogicException
     */
    public static function fromArray(string $name, array $configuration): self {
        $className = $configuration['class'] ?? '';
        if (!is_string($className) || $className === '') {
            throw new \LogicException(sprintf('Monitor API operation "%s": "class" is missing in MonitorOperations.php', $name), 1759654801);
        }

        $httpMethod = strtoupper((string)($configuration['httpMethod'] ?? 'GET'));
        if (!in_array($httpMethod, self::ALLOWED_HTTP_METHODS, true)) {
            throw new \LogicException(sprintf('Monitor API operation "%s": httpMethod "%s" is not allowed (allowed: %s)', $name, $httpMethod, implode(', ', self::ALLOWED_HTTP_METHODS)), 1759654802);
        }

        $parameters = $configuration['parameters'] ?? [];
        foreach ($parameters as $parameterName => $type) {
            if (!is_string($parameterName) || !in_array($type, self::ALLOWED_PARAMETER_TYPES, true)) {
                throw new \LogicException(sprintf('Monitor API operation "%s": parameter "%s" has invalid type "%s" (allowed: %s)', $name, $parameterName, (string)$type, implode(', ', self::ALLOWED_PARAMETER_TYPES)), 1759654803);
            }
        }

        $required = $configuration['required'] ?? [];
        foreach ($required as $parameterName) {
            if (!isset($parameters[$parameterName])) {
                throw new \LogicException(sprintf('Monitor API operation "%s": required parameter "%s" is not defined in "parameters"', $name, $parameterName), 1759654804);
            }
        }

        return new self($name, $className, $httpMethod, $parameters, array_values($required));
    }

    /**
     * Public description, used by the operation GetAllowedOperations
     */
    public function toArray(): array {
        return [
            'httpMethod' => $this->httpMethod,
            'parameters' => $this->parameters,
            'required' => $this->required,
        ];
    }
}
