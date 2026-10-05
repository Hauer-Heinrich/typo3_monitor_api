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
use TYPO3\CMS\Core\Core\Environment;
use HauerHeinrich\Typo3MonitorApi\Utility\FormatUtility;
use HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 * A Operation which returns the current disk space
 *
 * @author Tobias Liebig <tobias.liebig@typo3.org>
 *
 * Measures the filesystem TYPO3 is installed on (project path), previously always "/".
 * On servers with several partitions or under Windows "/" is often a different disk.
 * Note: hosting quotas (shared hosting) are not visible to PHP, only the free space of the filesystem.
 */
class GetDiskSpace implements IOperation {

    /**
     * @param array $parameter format (bool): human readable values
     * @return OperationResult
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        $path = Environment::getProjectPath();
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false) {
            // Previously a TypeError in format mode (false passed to getHumanReadableSize())
            return new OperationResult(false, [], 'Can\'t determine disk space (disk_total_space()/disk_free_space() disabled or not allowed)');
        }

        if (($parameter['format'] ?? false) === true) {
            return new OperationResult(true, [[
                'total' => FormatUtility::getHumanReadableSize($total),
                'free' => FormatUtility::getHumanReadableSize($free),
            ]]);
        }

        return new OperationResult(true, [[
            'total' => $total,
            'free' => $free,
        ]]);
    }
}
