<?php

namespace BarrierefreiSpace\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DonationStatusController
{
    private const DEFAULT_SERVER_URL = 'https://alt-text-api-647809240796.europe-west3.run.app';

    public function status(): ResponseInterface
    {
        try {
            $extConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('barrierefrei_space');
            $serverUrl = self::DEFAULT_SERVER_URL;
            $licenseKey = (string)($extConfig['licenseKey'] ?? '');
            $siteUrl = $this->resolveSingleSiteBaseUrl();

            if ($serverUrl === '' || $licenseKey === '' || $siteUrl === '') {
                return new JsonResponse(['error' => 'Extension not configured'], 400);
            }

            $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
            $response = $requestFactory->request($serverUrl . '/v1/donation-status', 'GET', [
                'query' => [
                    'license_key' => $licenseKey,
                    'site_url' => $siteUrl,
                ],
                'timeout' => 15,
            ]);

            $payload = json_decode((string)$response->getBody(), true);
            if (!is_array($payload)) {
                return new JsonResponse(['error' => 'Invalid server response'], 502);
            }

            return new JsonResponse($payload, $response->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function resolveSingleSiteBaseUrl(): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $requestHost = $request instanceof \Psr\Http\Message\ServerRequestInterface
            ? strtolower($request->getUri()->getHost())
            : '';

        try {
            $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
            if ($requestHost !== '') {
                foreach ($sites as $site) {
                    $siteBase = (string)$site->getBase();
                    $siteHost = strtolower((string)parse_url($siteBase, PHP_URL_HOST));
                    if ($siteHost === $requestHost) {
                        return $siteBase;
                    }
                }
            }
            if (count($sites) === 1) {
                return (string)array_values($sites)[0]->getBase();
            }
        } catch (\Throwable) {
        }

        if (!$request instanceof \Psr\Http\Message\ServerRequestInterface) {
            return '';
        }

        $uri = $request->getUri();
        $authority = $uri->getAuthority();
        if ($authority === '') {
            return '';
        }

        $scheme = $uri->getScheme() !== '' ? $uri->getScheme() : 'https';
        return $scheme . '://' . $authority . '/';
    }
}
