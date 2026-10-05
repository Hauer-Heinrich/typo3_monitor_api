<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Service;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Update information of all extensions from the extension manager (TER data), cached.
 * Used by HasExtensionUpdate, HasExtensionUpdateList, GetExtensionList,
 * GetOutdatedExtensionList and GetInsecureExtensionList.
 *
 * Only the fields these operations need are cached (plain arrays, no Extbase objects).
 */
final class ExtensionInformationService {
    public function __construct(
        private readonly MonitorCache $cache,
    ) {}

    /**
     * @return array<string, array{installed: bool, updateAvailable: ?bool, ter: ?array{version: string, reviewState: int, currentVersion: bool}}>|null
     *         null if EXT:extensionmanager is not loaded
     */
    public function getExtensionInformation(): ?array {
        // EXT:extensionmanager is optional, so ListUtility is fetched lazily
        if (!ExtensionManagementUtility::isLoaded('extensionmanager')) {
            return null;
        }

        return $this->cache->remember('extension_information', fn(): array => $this->fetchExtensionInformation());
    }

    private function fetchExtensionInformation(): array {
        /** @var ListUtility $listUtility */
        $listUtility = GeneralUtility::makeInstance(ListUtility::class);

        $result = [];
        foreach ($listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation() as $extensionKey => $information) {
            $terObject = $information['terObject'] ?? null;
            $result[$extensionKey] = [
                'installed' => ($information['installed'] ?? false) === true,
                'updateAvailable' => isset($information['updateAvailable']) ? (bool)$information['updateAvailable'] : null,
                'ter' => $terObject instanceof Extension ? [
                    'version' => (string)$terObject->getVersion(),
                    'reviewState' => (int)$terObject->getReviewState(),
                    'currentVersion' => (bool)$terObject->getCurrentVersion(),
                ] : null,
            ];
        }

        return $result;
    }
}
