<?php
$EM_CONF['typo3_monitor_api'] = [
    'title' => 'Hauer-Heinrich - TYPO3 monitor api - allows to retrieve various information about the installed TYPO3 cms (response: json).',
    'description' => 'Hauer-Heinrich',
    'category' => 'be',
    'author' => 'Christian Hackl',
    'author_email' => 'info@hauer-heinrich.de',
    'author_company' => 'Hauer-Heinrich.de',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'version' => '1.1.6',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ],
    'autoload' => [
        'psr-4' => [
            'HauerHeinrich\\Typo3MonitorApi\\' => 'Classes',
        ],
    ],
];
