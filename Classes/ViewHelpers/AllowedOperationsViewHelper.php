<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\ViewHelpers;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class AllowedOperationsViewHelper extends AbstractViewHelper {

    /**
     * List all Operations
     * Usage for example TYPO3 backend settings -> extension settings
     *
     * @param array $config
     * @param $const
     * @return string
     */
    public function select(array $config, $const) {
        $extensionKey = 'typo3_monitor_api';
        // Typo3 extension manager gearwheel icon (ext_conf_template.txt)
        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey];
        $operations = $extensionConfiguration['operations'];

        $routingConfig = GeneralUtility::makeInstance(\HauerHeinrich\Typo3MonitorApi\Utility\RoutingConfig::class);
        $allowedOperations = $routingConfig->getMethodsAllowed();

        $return = '
            <style>
                #allowedOperations { display: grid; grid-template-columns: repeat(auto-fill, 20em); #allowedOperations .option label { margin-left: 5px; } }
            </style>
            <div id="allowedOperations">
        ';

        foreach ($allowedOperations as $key => $value) {
            if(is_string($key) && is_array($value)) {
                $cleardName = $key;
                $checked = '';
                $value = '';
                if(array_key_exists($cleardName, $operations) && $operations[$cleardName] !== "0") {
                    $checked = 'checked';
                    $value = 1;
                }

                $return .= '
                    <div class="option">
                        <input type="hidden" name="operations.'.$cleardName.'" value="0">
                        <input type="checkbox" id="'.$cleardName.'" name="operations.'.$cleardName.'" value="'.$value.'" '.$checked.'>
                        <label for="'.$cleardName.'">'.$cleardName.'</label>
                    </div>';
            }
        }
        $return .= '</div>';

        return $return;
    }
}
