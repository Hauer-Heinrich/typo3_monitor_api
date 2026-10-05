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
use TYPO3\CMS\Core\Information\Typo3Version;
use HauerHeinrich\Typo3MonitorApi\Service\Typo3ReleaseInformation;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 * Compares the installed TYPO3 version with the latest security release of the same major version
 * (get.typo3.org, cached, see extension setting "cacheLifetime").
 *
 * value.bool: true = security update available
 **/
class HasSecurityUpdate implements IOperation {

    public function __construct(
        private readonly Typo3ReleaseInformation $releaseInformation,
        private readonly Typo3Version $typo3Version,
    ) {}

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $currentTypo3Version = $this->typo3Version->getVersion();

        try {
            $latestVersion = $this->releaseInformation->getLatestVersion($this->typo3Version->getMajorVersion(), true);
        } catch (\Throwable $th) {
            return new OperationResult(false, [[ 'exception' => $th->getMessage() ]], 'Error retrieving the patch releases!');
        }

        if (version_compare($currentTypo3Version, $latestVersion, '<')) {
            return new OperationResult(true, [[ 'bool' => true, 'version' => $latestVersion ]], 'Security update available (' . $latestVersion . ')');
        }

        return new OperationResult(true, [[ 'bool' => false ]], 'No security update available.');
    }
}
