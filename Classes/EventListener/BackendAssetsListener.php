<?php

namespace BarrierefreiSpace\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeBackendPageRenderEvent;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class BackendAssetsListener
{
    /**
     * Inject required extension JavaScript modules before backend page rendering.
     *
     * ALT button may appear first via dynamic Ajax insertion; preloading globally ensures immediate initialization
     * and avoids requiring close/reopen cycles.
     */
    public function __invoke(BeforeBackendPageRenderEvent $event): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@barrierefrei-space/backend-donation-status.js');
        $pageRenderer->loadJavaScriptModule('@barrierefrei-space/alt-text-generator.js');
    }
}
