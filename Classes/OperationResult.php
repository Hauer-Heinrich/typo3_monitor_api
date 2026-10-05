<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */


/**
 * An Operation Result encapsulates the result of an Operation execution.
 */
class OperationResult {

    protected bool $status;
    protected array $value;

    /**
     * additional $message
     * e. g. error message if $status is false
     */
    protected string $message;

    /**
     * Construct a new operation result
     *
     * @param bool $status
     * @param array $value
     * @param string $message
     */
    public function __construct(bool $status, array $value = [], string $message = '') {
        $this->status = $status;
        $this->value = $value;
        $this->message = $message;
    }

    /**
     * @return bool If the operation was executed successful
     */
    public function isSuccessful(): bool {
        return $this->status;
    }

    /**
     * @return array The operation value
     */
    public function getValue(): array {
        return $this->value;
    }

    /**
     * @return string The operation message
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * @return array The Operation Result as an array
     */
    public function toArray(): array {
        return ['status' => $this->status, 'value' => $this->value, 'message' => $this->message];
    }
}
