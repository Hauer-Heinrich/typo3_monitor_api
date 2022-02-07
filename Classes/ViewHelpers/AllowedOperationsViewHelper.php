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
use \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class AllowedOperationsViewHelper extends AbstractViewHelper {

    /**
     * apiKey
     *
     * @param array $config
     * @param $const
     * @return string
     */
    public function select(array $config, $const)
    {
        $extensionKey = 'typo3_monitor_api';
        // Typo3 extension manager gearwheel icon (ext_conf_template.txt)
        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey];
        $operations = $extensionConfiguration['operations'];

        $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey);
        $fileNames = array_diff(scandir($extPath.'Classes/Operation/'), ['.', '..']);
        $return = '
            <style>
                #allowedOperations { display: grid; grid-template-columns: repeat(auto-fill, 20em); #allowedOperations .option label { margin-left: 5px; } }
            </style>
            <div id="allowedOperations">
        ';
        foreach ($fileNames as $fileName) {
            if(self::endsWith($fileName, '.php')) {
                $cleardName = substr($fileName, 0, -4);
                $checked = '';
                $value = '';
                if($operations[$cleardName] !== "0") {
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

    /**
     * endsWith
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    static function endsWith(string $haystack, string $needle ): bool {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }
}
