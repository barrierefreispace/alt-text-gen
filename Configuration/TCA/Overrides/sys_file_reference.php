<?php

defined('TYPO3') or die();

$alternativeConfig = &$GLOBALS['TCA']['sys_file_reference']['columns']['alternative']['config'];

if (isset($alternativeConfig['fieldWizard']['altTextGenerator'])) {
    unset($alternativeConfig['fieldWizard']['altTextGenerator']);
}

$alternativeConfig['fieldControl'] ??= [];
$alternativeConfig['fieldControl']['altTextGenerator'] = [
    'renderType' => 'altTextGeneratorControl',
];

$GLOBALS['TCA']['sys_file_reference']['columns']['tx_barrierefrei_space_style'] = [
    'label' => 'ALT Text Gen Style / ALT 文风',
    'config' => [
        'type' => 'passthrough',
    ],
];

$GLOBALS['TCA']['sys_file_reference']['columns']['tx_barrierefrei_space_seo_keywords'] = [
    'label' => 'ALT Text Gen SEO Keywords / ALT SEO 关键词',
    'config' => [
        'type' => 'passthrough',
    ],
];

// After adding the right-side fieldControl button, the default visible input width becomes too small; increase size for better readability.
// A single-line input is not ideal for long ALT text; switching to text with larger rows/cols improves both width and height.
$alternativeConfig['type'] = 'text';
$alternativeConfig['cols'] = max((int) ($alternativeConfig['cols'] ?? 80), 100);
$alternativeConfig['rows'] = max((int) ($alternativeConfig['rows'] ?? 3), 3);
unset($alternativeConfig['size']);
