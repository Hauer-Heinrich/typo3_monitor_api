<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

use \TYPO3\CMS\Core\SingletonInterface;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \HauerHeinrich\Typo3MonitorApi\OperationResult;


/**
 * Returns values about the mail file spool.
 * Possible values are:
 * - pending: Returns mails in spool
 * - sending: Returns mails trying to send.
 * - lag: Returns time passed in seconds from oldest file in spool.
 */
class GetFileSpoolValue implements IOperation, SingletonInterface
{
    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        $value = $parameter['value'] ?? null;
        $filePath = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_spool_filepath'] ?? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['spool_file_path'] ?? null;

        if (!$filePath) {
            return new OperationResult(false, [], 'mail transport_spool_filepath // spool_file_path not set = null!');
        }

        switch ($value) {
            case 'pending':
                $count = 0;
                $filePath = GeneralUtility::getFileAbsFileName($filePath);
                foreach (new \DirectoryIterator($filePath) as $file) {
                    if ($file->getExtension() === 'message') {
                        $count += 1;
                    }
                }
                return new OperationResult(true, [[ 'data' => $count ]]);
                break;
            case 'sending':
                $count = 0;
                $filePath = GeneralUtility::getFileAbsFileName($filePath);
                foreach (new \DirectoryIterator($filePath) as $file) {
                    if ($file->getExtension() === 'sending') {
                        $count += 1;
                    }
                }
                return new OperationResult(true, [[ 'data' => $count ]]);
                break;
            case 'lag':
                $age = 0;
                $filePath = GeneralUtility::getFileAbsFileName($filePath);
                foreach (new \DirectoryIterator($filePath) as $file) {
                    if ($file->isDot() === false) {
                        $age = max($age, time() - $file->getMTime());
                    }
                }
                return new OperationResult(true, [[ 'data' => $age ]]);
                break;

            default:
                break;
        }

        throw new \InvalidArgumentException('Parameter value not set or invalid');
    }
}
