<?php

defined('TYPO3') or die();

// Cache for expensive or external lookups (see \HauerHeinrich\Typo3MonitorApi\Service\MonitorCache).
// FileBackend: no database tables needed, entries expire after "cacheLifetime" (extension settings).
// "??=" keeps a configuration set in config/system/additional.php (e.g. Redis instead of files).
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['typo3_monitor_api'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
];
