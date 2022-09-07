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
 * Checks wether the given path exists or not
 *
 * @author Felix Oertel <oertel@networkteam.com>
 *
 */
class CheckPathExists implements IOperation, SingletonInterface
{
    /**
     * execute operation (checkPathExists)
     *
     * @param array $parameter a path 'path' to a file or folder
     * @return OperationResult 'file' if path is a file, 'directory' if it's a directory and false if it doesn't exist
     */
    public function execute(array $parameter = []): OperationResult
    {
        if(!isset($parameter['path'])) {
            return new OperationResult(false, [], 'Param path not set!');
        }

        if(empty($parameter['path'])) {
            return new OperationResult(false, [], 'Param path is empty!');
        }

        $path = $this->getPath($parameter['path']);
        list($path) = glob($path);

        if (is_file($path)) {
            //if file exists, get the tstamp
            $time = filemtime($path);
            $size = filesize($path);

            return new OperationResult(true, [[
                'type' => 'file',
                'path' => $parameter['path'],
                'time' => $time,
                'size' => $size,
            ]]);
        } elseif (is_dir($path)) {
            return new OperationResult(true, [[
                'type' => 'folder',
                'path' => $parameter['path'],
            ]]);
        }

        return new OperationResult(false, [[ 'path' => $parameter['path'] ]], 'Param path is not a file or directory!');
    }

    /**
     * prepare path, resolve relative path and resolve EXT: path
     *
     * @param string $path absolute or relative path or EXT:foobar/
     * @return string empty if path is invalid, else the absolute path
     */
    protected function getPath(string $path): string
    {
        // getFileAbsFileName can't handle directory path with trailing / correctly
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        // FIXME remove this hacky part
        // skip path checks for CLI mode
        if (defined('TYPO3_cliMode')) {
            return $path;
        }

        $path = GeneralUtility::getFileAbsFileName($path);
        if (GeneralUtility::isAllowedAbsPath($path)) {
            return $path;
        }

        return '';
    }
}
