<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * An Operation that returns the version of an installed extension
 *
 */
class GetExtensionVersion implements IOperation, SingletonInterface
{
    /**
     * Get the extension version of the given extension by extension key
     *
     * @param array $parameter None
     * @return OperationResult The extension version
     */
    public function execute(array $parameter = []): OperationResult
    {
        if (!isset($parameter['extensionKey'])) {
            // throw new InvalidArgumentException('no extensionKey set');
            return new OperationResult(false, [], 'No extensionKey set!');
        }

        if($parameter['extensionKey'] === '') {
            return new OperationResult(false, [], 'ExtensionKey empty!');
        }

        $extensionKey = $parameter['extensionKey'];

        if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
            return new OperationResult(false, [], 'Extension [' . $extensionKey . '] is not loaded');
        }

        include(ExtensionManagementUtility::extPath($extensionKey, 'ext_emconf.php'));

        if (is_array($EM_CONF[$extensionKey])) {
            return new OperationResult(true, [[ 'version' => $EM_CONF[$extensionKey]['version'] ]]);
        }

        return new OperationResult(false, [], 'Cannot read EM_CONF for extension [' . $extensionKey . ']');
    }
}
