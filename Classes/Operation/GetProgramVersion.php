<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\CommandUtility;
use HauerHeinrich\Typo3MonitorApi\Exception\InvalidArgumentException;
use HauerHeinrich\Typo3MonitorApi\OperationResult;
use HauerHeinrich\Typo3MonitorApi\Utility\Configuration;


/**
 * A Operation which returns the programm versions
 */
class GetProgramVersion implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute(array $parameter = []): OperationResult
    {
        if (!isset($parameter['program'])) {
            // throw new InvalidArgumentException('no program set');
            return new OperationResult(false, [], 'Param \'program\' not set! Allowed values are: \'openssl, gm, im, optipng, jpegoptim, webp\'.');
        }

        if($parameter['program'] === '') {
            return new OperationResult(false, [], 'Param \'program\' not allowed to be empty! Allowed values are: \'openssl, gm, im, optipng, jpegoptim, webp\'.');
        }

        $programName = $parameter['program'];

        $config = Configuration::getExtConfiguration();
        $paths = $config['program.'] ?? $config['program'];

        switch ($programName) {
            case 'openssl':
                $path = $paths['openssl'] ?? '';
                if ($path !== '' && @is_file($path)) {
                    $command = escapeshellarg($path) . ' version';
                    $executingResult = [];
                    CommandUtility::exec($command, $executingResult);
                    $firstResultLine = array_shift($executingResult);
                    return new OperationResult(true, explode(' ', $firstResultLine)[1]);
                }
                break;
            case 'gm':
                $path = $paths['gm'] ?? '';
                if ($path !== '' && @is_file($path)) {
                    $command = escapeshellarg($path) . ' -version';
                    $executingResult = [];
                    CommandUtility::exec($command, $executingResult);
                    $firstResultLine = array_shift($executingResult);
                    if (strpos($firstResultLine, 'GraphicsMagick') !== false) {
                        return new OperationResult(true, explode(' ', $firstResultLine)[1]);
                    }
                }
                break;
            case 'im':
                $path = $paths['im'] ?? '';
                if ($path !== '' && @is_file($path)) {
                    $command = escapeshellarg($path) . ' -version';
                    $executingResult = [];
                    CommandUtility::exec($command, $executingResult);
                    $firstResultLine = array_shift($executingResult);
                    if (strpos($firstResultLine, 'ImageMagick') !== false) {
                        return new OperationResult(true, explode(' ', $firstResultLine)[2]);
                    }
                }
                break;
            case 'optipng':
                $path = $paths['optipng'] ?? '';
                if ($path !== '' && @is_file($path)) {
                    $command = escapeshellarg($path) . ' -v';
                    $executingResult = [];
                    CommandUtility::exec($command, $executingResult);
                    $firstResultLine = array_shift($executingResult);
                    return new OperationResult(true, explode(' ', $firstResultLine)[2]);
                }
                break;
            case 'jpegoptim':
                $path = $paths['jpegoptim'] ?? '';
                if ($path !== '' && @is_file($path)) {
                    $command = escapeshellarg($path) . ' --version';
                    $executingResult = [];
                    CommandUtility::exec($command, $executingResult);
                    $firstResultLine = array_shift($executingResult);
                    return new OperationResult(true, explode(' ', $firstResultLine)[1]);
                }
                break;
            case 'webp':
                $path = $paths['webp'] ?? '';
                if ($path !== '' && @is_file($path)) {
                    $command = escapeshellarg($path) . ' -version';
                    $executingResult = [];
                    CommandUtility::exec($command, $executingResult);
                    $firstResultLine = array_shift($executingResult);
                    return new OperationResult(true, trim($firstResultLine));
                }
                break;
            default:
                return new OperationResult(false, [], 'Param \'program\' not known ('.$parameter['program'].'). Allowed values are: \'openssl, gm, im, optipng, jpegoptim, webp\'.');
                break;
        }

        return new OperationResult(false, [], 'Path to program \''.$programName.'\' not found!');
    }
}
