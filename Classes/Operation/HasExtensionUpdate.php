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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use HauerHeinrich\Typo3MonitorApi\Service\ExtensionInformationService;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


class HasExtensionUpdate implements IOperation {

    public function __construct(
        private readonly ExtensionInformationService $extensionInformationService,
    ) {}

    /**
     * @param array $parameter extensionKey
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $extensionKey = $parameter['extensionKey'] ?? '';
        if($extensionKey === '') {
            return new OperationResult(false, [], 'Param \'extensionKey\' is not allowed to be empty!');
        }

        return $this->getResultForExtension($extensionKey, $this->extensionInformationService->getExtensionInformation());
    }

    /**
     * @param array|null $extensionInformation result of ExtensionInformationService::getExtensionInformation()
     */
    public function getResultForExtension(string $extensionKey, ?array $extensionInformation): OperationResult {
        if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
            return new OperationResult(false, [], 'Extension [' . $extensionKey . '] is not loaded');
        }

        if ($extensionInformation === null) {
            return new OperationResult(false, [], 'EXT:extensionmanager not loaded!');
        }

        $updateAvailable = $extensionInformation[$extensionKey]['updateAvailable'] ?? null;
        if ($updateAvailable !== null) {
            return new OperationResult(true, [[ 'data' => $updateAvailable ]]);
        }

        return new OperationResult(false, [], 'No update information available for extension [' . $extensionKey . ']');
    }
}
