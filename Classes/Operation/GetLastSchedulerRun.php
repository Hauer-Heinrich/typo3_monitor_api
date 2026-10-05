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
use TYPO3\CMS\Core\Registry;
use HauerHeinrich\Typo3MonitorApi\OperationResult;
use HauerHeinrich\Typo3MonitorApi\Utility\FormatUtility;


class GetLastSchedulerRun implements IOperation {

    public function __construct(
        private readonly Registry $registry,
    ) {}

    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $lastRun = $this->registry->get('tx_scheduler', 'lastRun', []);


        if (isset($lastRun['end'])) {
            if(empty($parameter['format'])) {
                return new OperationResult(true, [[ 'tstamp' => $lastRun['end'] ]]);
            } else {
                $returnValue = FormatUtility::formatDateTime((int)$lastRun['end'], $parameter['format']);
                if(empty($returnValue)) {
                    return new OperationResult(false, [], 'Param \'format\' not valid! Valid values are: \'d M Y H:i:s, d M Y, H:i:s, c, r\'');
                }

                return new OperationResult(true, [[ 'formated' => $returnValue ]]);
            }
        }

        return new OperationResult(false, [], 'Can\'t detect last scheduler run!');
    }
}
