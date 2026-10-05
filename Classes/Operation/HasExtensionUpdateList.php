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
use HauerHeinrich\Typo3MonitorApi\Service\ExtensionInformationService;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 * Returns an array of extensions which have updates available
 */
class HasExtensionUpdateList implements IOperation {

    public function __construct(
        private readonly ExtensionInformationService $extensionInformationService,
    ) {}

    /**
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $scope = isset($parameter['scope']) ? $parameter['scope'] : '';

        $extensionInformation = $this->extensionInformationService->getExtensionInformation();
        if ($extensionInformation === null) {
            return new OperationResult(false, [], 'EXT:extensionmanager not loaded!');
        }

        $loadedOutdated = [];
        $existingOutdated = [];
        foreach ($extensionInformation as $extensionKey => $information) {
            $ter = $information['ter'];
            if ($ter !== null && $information['updateAvailable'] === true && !$ter['currentVersion']) {
                $entry = [
                    'extensionKey' => $extensionKey,
                    'version' => $ter['version'],
                ];
                if ($information['installed']) {
                    $loadedOutdated[] = $entry;
                } else {
                    $existingOutdated[] = $entry;
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
