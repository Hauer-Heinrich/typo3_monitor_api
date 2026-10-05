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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use HauerHeinrich\Typo3MonitorApi\OperationResult;
use HauerHeinrich\Typo3MonitorApi\Utility\FormatUtility;


class GetLastExtensionListUpdate implements IOperation {

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        // Should the extension list (extensionmanager) be used? false = scheduler task
        $useExtensionList = ($parameter['extensionlist'] ?? true) === true;

        if ($useExtensionList) {
            $timestamp = $this->getExtensionListLastUpdate();
        } else {
            if (!ExtensionManagementUtility::isLoaded('scheduler')) {
                return new OperationResult(false, [], 'EXT:scheduler not loaded/installed!');
            }
            $timestamp = $this->getExtensionListLastUpdateScheduler();
        }

        if ($timestamp === 0) {
            return new OperationResult(true);
        }

        if (!empty($parameter['format'])) {
            $formatDateTime = FormatUtility::formatDateTime($timestamp, $parameter['format']);
            if ($formatDateTime === '') {
                return new OperationResult(false, [], 'Param \'format\' not valid! Valid values are: \'' . implode(', ', FormatUtility::DATE_FORMATS) . '\'');
            }
            return new OperationResult(true, [[ 'formated' => $formatDateTime ]]);
        }

        return new OperationResult(true, [[ 'tstamp' => $timestamp ]]);
    }

    /**
     * Last update of the extension list (TER), as shown in the extension manager.
     *
     * Previously read the table modification time from information_schema with a hard coded
     * database name and the removed Doctrine method query(), so it always returned 0.
     *
     * @return int timestamp, 0 if the list was never updated
     */
    public function getExtensionListLastUpdate(): int {
        if (!ExtensionManagementUtility::isLoaded('extensionmanager')) {
            return 0;
        }

        $lastUpdate = 0;
        $remoteRegistry = GeneralUtility::makeInstance(RemoteRegistry::class);
        foreach ($remoteRegistry->getListableRemotes() as $remote) {
            $date = $remote->getLastUpdate();
            // TYPO3 returns 1975-04-13 if the list has never been downloaded
            if ($date->format('Y-m-d') !== '1975-04-13') {
                $lastUpdate = max($lastUpdate, $date->getTimestamp());
            }
        }

        return $lastUpdate;
    }

    /**
     * Last execution of the scheduler task "Update extension list"
     *
     * @return int timestamp, 0 if not found
     */
    public function getExtensionListLastUpdateScheduler(): int {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('serialized_task_object', 'lastexecution_time')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery();

        while ($task = $result->fetchAssociative()) {
            // Only allow the expected class, everything else becomes __PHP_Incomplete_Class
            $taskObject = unserialize((string)$task['serialized_task_object'], ['allowed_classes' => [UpdateExtensionListTask::class]]);
            if ($taskObject instanceof UpdateExtensionListTask && !empty($task['lastexecution_time'])) {
                return (int)$task['lastexecution_time'];
            }
        }

        return 0;
    }
}
