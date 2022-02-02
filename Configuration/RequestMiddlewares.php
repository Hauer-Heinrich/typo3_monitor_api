<?php

return [
    'frontend' => [
        'hauerheinrich/typo3monitorapi' => [
            'target' => \HauerHeinrich\Typo3MonitorApi\Middleware\MonitorApi::class,
            'before' => [
                'typo3/cms-redirects/redirecthandler'
            ],
            'after' => [
                'typo3/cms-frontend/authentication'
            ]
        ],
    ]
];
