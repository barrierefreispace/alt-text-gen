<?php

use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx-barrierefrei-space-generate' => [
        'provider' => SvgSpriteIconProvider::class,
        'sprite' => 'EXT:barrierefrei_space/Resources/Public/Icons/sprite.svg#tx-barrierefrei-space-generate',
    ],
    'tx-barrierefrei-space-wand' => [
        'provider' => SvgSpriteIconProvider::class,
        'sprite' => 'EXT:barrierefrei_space/Resources/Public/Icons/sprite.svg#tx-barrierefrei-space-wand',
    ],
    'tx-barrierefrei-space-logo' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:barrierefrei_space/Resources/Public/Icons/logo.svg',
    ],
];
