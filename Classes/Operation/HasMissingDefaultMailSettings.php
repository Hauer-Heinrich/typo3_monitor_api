<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\SingletonInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 *
 * Check if strict syntax is enabled
 *
 */
class HasMissingDefaultMailSettings implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        $returnValue = [];
        $missing = [];
        if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])) {
            $missing['defaultMailFromAddress']= 'defaultMailFromAddress';
        } else {
            $returnValue['defaultMailFromAddress'] = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        }

        if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])) {
            $missing['defaultMailFromName']= 'defaultMailFromName';
        } else {
            $returnValue['defaultMailFromName'] = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
        }

        if(empty($missing)) {
            return new OperationResult(true, [ $returnValue ]);
        }

        return new OperationResult(true, [ $missing ], 'Missing default mail settings detected!');
    }
}
