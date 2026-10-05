<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\ViewHelpers;

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

        $allowedOperations = \HauerHeinrich\Typo3MonitorApi\Api\OperationRegistry::getDefinitions();

        $return = '
            <style>
                #allowedOperations { display: grid; grid-template-columns: repeat(auto-fill, 20em); }
                #allowedOperations .option label { margin-left: 5px; }
            </style>
            <div id="allowedOperations">
        ';

        foreach ($allowedOperations as $key => $value) {
            if(is_string($key)) {
                $clearName = htmlspecialchars($key);
                $checked = '';

                if(array_key_exists($key, $operations) && (string)$operations[$key] === '1') {
                    $checked = 'checked';
                }

                // The checkbox value must always be "1": the hidden field sends "0" when unchecked,
                // a checked checkbox overrides it. (Previously value="0" for unchecked operations,
                // so they could never be enabled.)
                $return .= '
                    <div class="option">
                        <input type="hidden" name="operations.'.$clearName.'" value="0">
                        <input type="checkbox" id="operation-'.$clearName.'" name="operations.'.$clearName.'" value="1" '.$checked.'>
                        <label for="operation-'.$clearName.'">'.$clearName.'</label>
                    </div>';
            }
        }
        $return .= '</div>';

        return $return;
    }
}
