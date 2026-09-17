<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class AllowedOperationsViewHelper extends AbstractViewHelper {

    protected $escapeOutput = false;

    /**
     * Entry point for the extension configuration (ext_conf_template.txt):
     * type=user[HauerHeinrich\Typo3MonitorApi\ViewHelpers\AllowedOperationsViewHelper->select]
     *
     * Called by GeneralUtility::callUserFunction() with the field parameters
     * and the calling object, both of which are not needed here.
     *
     * @param array $params
     * @param object|null $ref
     * @return string
     */
    public function select(array $params = [], ?object $ref = null): string {
        return $this->render();
    }

    /**
     * List all Operations
     * Usage for example TYPO3 backend settings -> extension settings
     *
     * @return string
     */
    public function render(): string {
        $extensionKey = 'typo3_monitor_api';

        // Typo3 extension manager gearwheel icon (ext_conf_template.txt)
        $extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$extensionKey] ?? [];
        $operations = $extensionConfiguration['operations'] ?? [];

        $routingConfig = GeneralUtility::makeInstance(\HauerHeinrich\Typo3MonitorApi\Utility\RoutingConfig::class);
        $allowedOperations = $routingConfig->getMethodsAllowed();

        $return = '
            <style>
                #allowedOperations { display: grid; grid-template-columns: repeat(auto-fill, 20em); }
                #allowedOperations .option label { margin-left: 5px; }
            </style>
            <div id="allowedOperations">
        ';

        foreach ($allowedOperations as $key => $value) {
            if(is_string($key) && is_array($value)) {
                $clearName = $key;
                $checked = '';
                $fieldValue = '0';

                if(array_key_exists($clearName, $operations) && $operations[$clearName] !== "0") {
                    $checked = 'checked';
                    $fieldValue = '1';
                }

                $return .= '
                    <div class="option">
                        <input type="hidden" name="operations.'.$clearName.'" value="0">
                        <input type="checkbox" id="'.$clearName.'" name="operations.'.$clearName.'" value="'.$fieldValue.'" '.$checked.'>
                        <label for="'.$clearName.'">'.$clearName.'</label>
                    </div>';
            }
        }
        $return .= '</div>';

        return $return;
    }
}
