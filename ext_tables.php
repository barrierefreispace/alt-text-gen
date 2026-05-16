<?php

defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'BarrierefreiSpace',
    'DonationWidget',
    'LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf:plugin.donationWidget.title',
    'tx-barrierefrei-space-logo',
    'plugins',
    'LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf:plugin.donationWidget.description'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'barrierefrei_space',
    'Configuration/TypoScript',
    'ALT Text Generator'
);
