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
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Object\ObjectManager;
use \TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use \TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 * Returns an array of extensions which have updates available
 */
class HasExtensionUpdateList implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        $scope = $parameter['scope'];

        /** @var ListUtility $listUtility */
        $listUtility = GeneralUtility::makeInstance(ObjectManager::class)->get(ListUtility::class);
        $extensionInformation = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $loadedOutdated = [];
        $existingOutdated = [];

        foreach ($extensionInformation as $extensionKey => $information) {
            if (
                array_key_exists('terObject', $information)
                && $information['terObject'] instanceof Extension
            ) {
                /** @var Extension $terObject */
                $terObject = $information['terObject'];

                if ($information['updateAvailable'] == true && !$terObject->getCurrentVersion()) {
                    if (
                        array_key_exists('installed', $information)
                        && $information['installed'] === true
                    ) {
                        $loadedOutdated[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    } else {
                        $existingOutdated[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    }
                }
            }
        }

        if ($scope === 'loaded') {
            $exts = $loadedOutdated;
        } else {
            if ($scope === 'existing') {
                $exts = $existingOutdated;
            } else {
                $exts = array_merge($loadedOutdated, $existingOutdated);
            }
        }

        return new OperationResult(true, $exts);
    }
}
