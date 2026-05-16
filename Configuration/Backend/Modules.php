<?php

use BarrierefreiSpace\Controller\SubscriptionModuleController;

return [
    'web_barrierefreispace_subscription' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/barrierefrei-space/subscription',
        'labels' => 'LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf',
        'iconIdentifier' => 'tx-barrierefrei-space-logo',
        'routes' => [
            '_default' => [
                'target' => SubscriptionModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
