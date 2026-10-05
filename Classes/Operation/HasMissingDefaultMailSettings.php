<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

use Psr\Http\Message\ServerRequestInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * Check if strict syntax is enabled
 */
class HasMissingDefaultMailSettings implements IOperation {

    /**
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        // Local variables instead of (undeclared) properties: the service is shared,
        // properties would keep values between calls.
        $values = [];
        $errors = [];

        foreach (['defaultMailFromAddress', 'defaultMailFromName'] as $setting) {
            if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL'][$setting])) {
                $errors[$setting] = $setting;
            } else {
                $values[$setting] = $GLOBALS['TYPO3_CONF_VARS']['MAIL'][$setting];
            }
        }

        // Previously checked an undefined variable, so missing settings were never reported
        if ($errors === []) {
            return new OperationResult(true, [ $values ]);
        }
        return new OperationResult(true, [ $errors ], 'Missing default mail settings detected!');
    }
}
