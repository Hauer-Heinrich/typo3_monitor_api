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
use \TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use \TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 * An Operation that returns a list of insecure extensions
 *
 */
class GetInsecureExtensionList implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter Array of extension locations as string (loaded, existing)
     * @return OperationResult The extension list
     */
    public function execute(array $parameter = []): OperationResult
    {
        $scope = isset($parameter['scope']) ? $parameter['scope'] : '';

        /** @var ListUtility $listUtility */
        $listUtility = GeneralUtility::makeInstance(ListUtility::class);
        $extensionInformation = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $loadedInsecure = [];
        $existingInsecure = [];

        foreach ($extensionInformation as $extensionKey => $information) {
            if (
                array_key_exists('terObject', $information)
                && $information['terObject'] instanceof Extension
            ) {
                /** @var Extension $terObject */
                $terObject = $information['terObject'];
                $insecureStatus = $terObject->getReviewState();
                if ($insecureStatus === -1) {
                    if (
                        array_key_exists('installed', $information)
                        && $information['installed'] === true
                    ) {
                        $loadedInsecure[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    } else {
                        $existingInsecure[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    }
                }
            }

        }

        if ($scope === 'loaded') {
            $exts = $loadedInsecure;
        } else {
            if ($scope === 'existing') {
                $exts = $existingInsecure;
            } else {
                $exts = array_merge($loadedInsecure, $existingInsecure);
            }
        }

        $out = '';
        foreach ($exts as $ext) {
            $out .= $ext['extensionKey'] . ',';
        }
        $out = substr($out, 0, -1);

        if($out < 1) {
            return new OperationResult(true, []);
        }

        return new OperationResult(true, [[ 'list' => $out ]]);
    }

}
