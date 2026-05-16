<?php

namespace BarrierefreiSpace\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class DonationWidgetController extends ActionController
{
    private const DEFAULT_SERVER_URL = 'https://alt-text-api-647809240796.europe-west3.run.app';

    public function showAction(): ResponseInterface
    {
        $extConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('barrierefrei_space');
        $serverUrl = self::DEFAULT_SERVER_URL;
        $licenseKey = (string)($extConfig['licenseKey'] ?? '');
        $site = $this->request->getAttribute('site');
        $siteUrl = $site instanceof Site ? (string)$site->getBase() : '';
        $contentData = $this->resolveContentData();
        $positionClass = $this->resolvePositionClass((int)($contentData['layout'] ?? 0));

        $payload = null;
        if ($serverUrl !== '' && $licenseKey !== '' && $siteUrl !== '') {
            try {
                $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
                $response = $requestFactory->request($serverUrl . '/v1/donation-status', 'GET', [
                    'query' => [
                        'license_key' => $licenseKey,
                        'site_url' => $siteUrl,
                    ],
                    'timeout' => 15,
                ]);
                $decoded = json_decode((string)$response->getBody(), true);
                $payload = is_array($decoded) ? $decoded : null;
            } catch (\Throwable) {
                $payload = null;
            }
        }

        $this->view->assignMultiple([
            'payload' => $payload,
            'contentData' => $contentData,
            'positionClass' => $positionClass,
        ]);

        return $this->htmlResponse();
    }

    private function resolveContentData(): array
    {
        $contentObject = $this->request->getAttribute('currentContentObject');
        return is_object($contentObject) && isset($contentObject->data) && is_array($contentObject->data)
            ? $contentObject->data
            : [];
    }

    private function resolvePositionClass(int $layout): string
    {
        return match ($layout) {
            3, 10 => 'alt-text-gen-donation-widget--fixed-bottom-left',
            default => 'alt-text-gen-donation-widget--fixed-bottom-right',
        };
    }
}
