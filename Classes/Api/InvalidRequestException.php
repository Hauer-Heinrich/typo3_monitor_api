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
 * Invalid client input (JSON, parameters). Results in a 400 response with the message.
 */
final class InvalidRequestException extends \RuntimeException {}
