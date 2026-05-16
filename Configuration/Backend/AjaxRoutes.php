<?php

use BarrierefreiSpace\Controller\AltTextController;
use BarrierefreiSpace\Controller\DonationStatusController;

return [
    'barrierefrei_space_generate' => [
        'path' => '/barrierefrei-space/generate',
        'target' => AltTextController::class . '::generate',
        'methods' => ['POST'],
    ],
    'barrierefrei_space_save_preferences' => [
        'path' => '/barrierefrei-space/save-preferences',
        'target' => AltTextController::class . '::savePreferences',
        'methods' => ['POST'],
    ],
    'barrierefrei_space_donation_status' => [
        'path' => '/barrierefrei-space/donation-status',
        'target' => DonationStatusController::class . '::status',
        'methods' => ['GET'],
    ],
];
