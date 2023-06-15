<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

use \TYPO3\CMS\Core\SingletonInterface;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 *
 * Detecting if deprecation messages are logged
 *
 * E_DEPRECATED
 * E_USER_DEPRECATED
 *
 */
class HasDeprecationLogEnabled implements IOperation, SingletonInterface
{

    protected static $levelNames = [
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        $errorHandlerErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'];

        $levels = [];
        foreach (self::$levelNames as $level => $name) {
            if (($errorHandlerErrors & $level) === $level) {
                $levels[] = $name;
            }
        }

        return new OperationResult(true, [[ 'data' => count($levels) > 0 ]]);
    }
}
