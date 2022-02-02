<?php
$EM_CONF['typo3_monitor_api'] = [
    'title' => 'Hauer-Heinrich - ',
    'description' => 'Hauer-Heinrich',
    'category' => 'be',
    'author' => 'Christian Hackl',
    'author_email' => 'chackl@hauer-heinrich.de',
    'author_company' => 'Hauer-Heinrich.de',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
            'dashboard' => ''
        ],
        'conflicts' => [],
        'suggests' => []
    ],
    'autoload' => [
        'psr-4' => [
            'HauerHeinrich\\Typo3MonitorApi\\' => 'Classes',
            'Pecee\\' => 'Vendor/simple-php-router/src/Pecee'
        ],
    ],
];
