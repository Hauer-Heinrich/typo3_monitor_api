<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\SingletonInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 *
 * Check if strict syntax is enabled
 *
 */
class HasMissingDefaultMailSettings implements IOperation, SingletonInterface
{
    use \HauerHeinrich\Typo3MonitorApi\Utility\CheckBodyContent;

    public function __construct() {
        $this->allowedParameter = ['test' => 'array', 'install' => boolean, 'blubb' => integer];
    }

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        $checkBody = $this->checkBodyContent($parameter['request']);

        if($checkBody === false) {
            return new OperationResult(true, [ $this->errors ], 'bodyContent not valid! Value-type or key wrong / not allowed!');
        }

        if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])) {
            $this->errors['defaultMailFromAddress']= 'defaultMailFromAddress';
        } else {
            $this->returnValue['defaultMailFromAddress'] = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        }

        if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])) {
            $this->errors['defaultMailFromName']= 'defaultMailFromName';
        } else {
            $this->returnValue['defaultMailFromName'] = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
        }

        if(empty($errors)) {
            return new OperationResult(true, [ $this->returnValue ]);
        }

        return new OperationResult(true, [ $this->errors ], 'Missing default mail settings detected!');
    }
}
