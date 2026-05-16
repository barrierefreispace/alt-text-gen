<?php

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][] = [
    'nodeName' => 'altTextGeneratorWizard',
    'priority' => 40,
    'class' => \BarrierefreiSpace\FormEngine\Wizard\AltTextGeneratorWizard::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][] = [
    'nodeName' => 'altTextGeneratorControl',
    'priority' => 40,
    'class' => \BarrierefreiSpace\FormEngine\FieldControl\AltTextGeneratorControl::class,
];

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'BarrierefreiSpace',
    'DonationWidget',
    [
        \BarrierefreiSpace\Controller\DonationWidgetController::class => 'show',
    ],
    [],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
