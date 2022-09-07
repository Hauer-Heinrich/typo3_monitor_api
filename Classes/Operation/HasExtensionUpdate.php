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
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Object\ObjectManager;
use \TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 *
 */
class HasExtensionUpdate implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        if (!isset($parameter['extensionKey'])) {
            // throw new InvalidArgumentException('no extensionKey set');
            return new OperationResult(false, [], 'Param \'extensionKey\' not set!');
        }

        if($parameter['extensionKey'] === '') {
            return new OperationResult(false, [], 'Param \'extensionKey\' is not allowed to be empty!');
        }

        $extensionKey = $parameter['extensionKey'];

        if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
            return new OperationResult(false, [], 'Extension [' . $extensionKey . '] is not loaded');
        }

        /** @var ListUtility $listUtility */
        $listUtility = GeneralUtility::makeInstance(ObjectManager::class)->get(ListUtility::class);
        $extensionInformation = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();

        if (isset($extensionInformation[$extensionKey]['updateAvailable'])) {
            return new OperationResult(true, [[ 'data' =>  (boolean)$extensionInformation[$extensionKey]['updateAvailable'] ]]);
        }

        // TODO: return proper error message
        return new OperationResult(false, [], '');
    }
}
