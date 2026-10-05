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
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


class HasRemainingUpdates implements IOperation {

    /**
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        // EXT:install is optional, so the service is fetched lazily
        if (!ExtensionManagementUtility::isLoaded('install')) {
            return new OperationResult(false, [], 'EXT:install not loaded!');
        }

        $upgradeWizardsService = GeneralUtility::makeInstance(UpgradeWizardsService::class);
        $incompleteWizards = $upgradeWizardsService->getUpgradeWizardsList();
        $incompleteWizards = array_filter(
            $incompleteWizards,
            function ($wizard) {
                return $wizard['shouldRenderWizard'];
            }
        );

        return new OperationResult(true, [[ 'bool' => count($incompleteWizards) > 0 ]]);
    }

}
