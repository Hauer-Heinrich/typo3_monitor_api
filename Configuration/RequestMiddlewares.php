<?php

return [
    'frontend' => [
        'hauerheinrich/typo3monitorapi' => [
            'target' => \HauerHeinrich\Typo3MonitorApi\Middleware\MonitorApi::class,
            'before' => [
                'typo3/cms-redirects/redirecthandler',
                'typo3/cms-frontend/base-redirect-resolver',
                'typo3/cms-frontend/static-route-resolver'
            ],
            'after' => [
                'typo3/cms-frontend/authentication'
            ]
        ],
    ]
];
