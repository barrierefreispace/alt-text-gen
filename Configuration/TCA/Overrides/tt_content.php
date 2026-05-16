<?php

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf:plugin.donationWidget.title',
        'barrierefreispace_donationwidget',
        'tx-barrierefrei-space-logo',
    ],
    'list_type',
    'barrierefrei_space'
);

$cTypes = [
    'barrierefreispace_donationwidget',
    'barrierefrei_space_donationwidget',
];

foreach ($cTypes as $cType) {
    if (isset($GLOBALS['TCA']['tt_content']['types']['list']) && !isset($GLOBALS['TCA']['tt_content']['types'][$cType])) {
        $GLOBALS['TCA']['tt_content']['types'][$cType] = $GLOBALS['TCA']['tt_content']['types']['list'];
    }

    $GLOBALS['TCA']['tt_content']['types'][$cType]['showitem']
        = '--div--;General,--palette--;;general,'
        . '--div--;Appearance,--palette--;;frames,'
        . '--div--;Language,--palette--;;language,'
        . '--div--;Access,--palette--;;hidden,--palette--;;access,'
        . '--div--;Categories,categories,'
        . '--div--;Notes,rowDescription';

    if (!isset($GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides'])) {
        $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides'] = [];
    }

    $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['list_type']['config']['type'] = 'passthrough';
    $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['header_layout']['config']['default'] = 100;
    $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['layout']['config']['default'] = 11;
    $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['layout']['config']['removeItems'] = '0,1,2,3,4';
    $GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['layout']['config']['items'] = [
        [
            'label' => 'LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf:plugin.donationWidget.position.fixedBottomLeft',
            'value' => 10,
        ],
        [
            'label' => 'LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf:plugin.donationWidget.position.fixedBottomRight',
            'value' => 11,
        ],
    ];
}
