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

use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use HauerHeinrich\Typo3MonitorApi\OperationResult;
use HauerHeinrich\Typo3MonitorApi\Service\ExtensionInformationService;


/**
 * An Operation that returns a list of installed extensions
 *
 * original-authors:
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 * @author Sven Wappler <typo3YYYY@wappler.systems>
 *
 * Uses the PackageManager instead of scanning typo3/sysext/ and typo3conf/ext/,
 * so it works in composer mode (extensions in vendor/) as well as in classic mode.
 */
class GetExtensionList implements IOperation {
    /**
     * @var array Available extension scopes
     *   system / simple-system: system extensions (typo3/cms-*)
     *   local / simple-local:   all other extensions
     *   simple-*:               only the extension keys
     */
    protected array $scopes = ['system', 'local', 'simple-system', 'simple-local'];

    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly HasExtensionUpdate $hasExtensionUpdate,
        private readonly ExtensionInformationService $extensionInformationService,
    ) {}

    /**
     *
     * @param array $parameter scopes (comma separated), withUpdateInfo
     * @return OperationResult The extension list
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $locations = empty($parameter['scopes']) ? $this->scopes : GeneralUtility::trimExplode(',', $parameter['scopes'], true);
        // Unknown scopes are ignored (as before), e.g. the old scope "global"
        $locations = array_values(array_intersect($locations, $this->scopes));
        if ($locations === []) {
            return new OperationResult(false, [], 'No valid extension scope given! Allowed values are: \'' . implode(', ', $this->scopes) . '\'');
        }

        $withUpdateInfo = ($parameter['withUpdateInfo'] ?? false) === true;
        // Fetched once for all extensions (previously once per extension), cached
        $updateInformation = $withUpdateInfo ? $this->extensionInformationService->getExtensionInformation() : null;

        $extensionList = [];
        foreach ($locations as $scope) {
            $extensionList = array_merge($extensionList, $this->getExtensionListForScope($scope, $withUpdateInfo, $updateInformation));
        }

        $returnArray = [];
        foreach ($extensionList as $extension => $value) {
            $returnArray[$extension] = [$value];
        }

        return new OperationResult(true, [ $returnArray ]);
    }

    /**
     * Get the list of extensions in the given scope
     *
     * @param string $scope
     * @param bool $withUpdateInfo
     * @param array|null $updateInformation see ExtensionInformationService::getExtensionInformation()
     * @return array
     */
    protected function getExtensionListForScope(string $scope, bool $withUpdateInfo, ?array $updateInformation): array {
        $packages = $this->getPackagesForScope($scope);

        if (str_starts_with($scope, 'simple-')) {
            return array_keys($packages);
        }

        $extensionInfo = [];
        foreach ($packages as $extKey => $package) {
            $extensionInfo[$extKey]['ext_key'] = $extKey;
            $extensionInfo[$extKey]['installed'] = $this->packageManager->isPackageActive($extKey);

            $extensionVersion = (string)$package->getPackageMetaData()->getVersion();
            if ($extensionVersion !== '') {
                $extensionInfo[$extKey]['version'] = $extensionVersion;
                $extensionInfo[$extKey]['scope'][$scope] = $extensionVersion;
            }

            if ($withUpdateInfo) {
                $extensionInfo[$extKey]['hasExtensionUpdate'] = $this->hasExtensionUpdate->getResultForExtension($extKey, $updateInformation)->toArray();
            }
        }

        return $extensionInfo;
    }

    /**
     * All available extensions (in classic mode including inactive ones), sorted by extension key
     *
     * @return array<string, PackageInterface>
     */
    protected function getPackagesForScope(string $scope): array {
        $systemScope = in_array($scope, ['system', 'simple-system'], true);

        $packages = [];
        foreach ($this->packageManager->getAvailablePackages() as $extKey => $package) {
            $metaData = $package->getPackageMetaData();
            if ($metaData->isExtensionType() && $metaData->isFrameworkType() === $systemScope) {
                $packages[$extKey] = $package;
            }
        }
        ksort($packages);

        return $packages;
    }
}
