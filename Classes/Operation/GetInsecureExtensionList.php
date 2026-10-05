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
 * An Operation that returns a list of insecure extensions
 *
 */
class GetInsecureExtensionList implements IOperation {
    public function __construct(
        private readonly ExtensionInformationService $extensionInformationService,
    ) {}

    /**
     *
     * @param array $parameter Array of extension locations as string (loaded, existing)
     * @return OperationResult The extension list
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $scope = isset($parameter['scope']) ? $parameter['scope'] : '';

        $extensionInformation = $this->extensionInformationService->getExtensionInformation();
        if ($extensionInformation === null) {
            return new OperationResult(false, [], 'EXT:extensionmanager not loaded!');
        }

        $loadedInsecure = [];
        $existingInsecure = [];
        foreach ($extensionInformation as $extensionKey => $information) {
            $ter = $information['ter'];
            if ($ter !== null && $ter['reviewState'] === -1) {
                $entry = [
                    'extensionKey' => $extensionKey,
                    'version' => $ter['version'],
                ];
                if ($information['installed']) {
                    $loadedInsecure[] = $entry;
                } else {
                    $existingInsecure[] = $entry;
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

        if($out === '') {
            return new OperationResult(true, []);
        }

        return new OperationResult(true, [[ 'list' => $out ]]);
    }
}
