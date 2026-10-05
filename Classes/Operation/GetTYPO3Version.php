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
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * A Operation which returns the current TYPO3 version
 * @todo TYPO3 12 - remove unnecessary code if TYPO3 version 12 is available
 */
class GetTYPO3Version implements IOperation {

    public function __construct(
        private readonly Typo3Version $typo3Version,
    ) {}

    /**
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $version = $this->typo3Version->getVersion();

        return new OperationResult(true, [[ 'version' => $version ]]);
    }
}
