<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        "@import 'EXT:typo3_monitor_api/Configuration/TypoScript/setup.typoscript'"
    );

    // Add UserTS config as default for all BE users
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        "@import 'EXT:typo3_monitor_api/Configuration/TsConfig/User/0100_default.typoscript'"
    );


});
