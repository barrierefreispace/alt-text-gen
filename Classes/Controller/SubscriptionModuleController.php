<?php

namespace BarrierefreiSpace\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

final readonly class SubscriptionModuleController
{
    private const DEFAULT_SERVER_URL = 'https://alt-text-api-647809240796.europe-west3.run.app';

    /**
     * Render TYPO3 backend subscription center and create Stripe payment-link redirect when a plan is selected.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplateFactory = GeneralUtility::makeInstance(ModuleTemplateFactory::class);
        $moduleTemplate = $moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle($this->translate('subscription.module.title'));
        // Backend module is not always in Extbase context, so f:uri.resource may fail resolving extension assets.
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:barrierefrei_space/Resources/Public/Css/subscription-center.css');
        $pageRenderer->addJsFile('EXT:barrierefrei_space/Resources/Public/JavaScript/SubscriptionCenter.js');

        $config = $this->readExtensionConfig();
        $siteUrl = $this->resolveSiteUrl($request);
        $installationToken = $this->buildInstallationTokenForSiteUrl($siteUrl);
        $messages = [];
        $paymentUrlToOpen = '';
        $portalUrlToOpen = '';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $moduleUrl = (string) $uriBuilder->buildUriFromRoute('web_barrierefreispace_subscription');
        $absoluteModuleUrl = $this->buildAbsoluteUrlFromRequest($request, $moduleUrl);

        $action = (string) ($request->getQueryParams()['action'] ?? '');
        $selectedPlan = strtolower(trim((string) ($request->getQueryParams()['plan'] ?? '')));
        $configuredLicenseKey = (string) $config['licenseKey'];
        $plans = $this->fetchPlans((string) $config['serverUrl'], $messages);
        foreach ($plans as $index => $plan) {
            $planCode = strtolower(trim((string) ($plan['code'] ?? '')));
            $plans[$index]['buy_url'] = $planCode !== ''
                ? (string) $uriBuilder->buildUriFromRoute('web_barrierefreispace_subscription', ['action' => 'buy', 'plan' => $planCode])
                : '';
        }

        $latestSiteLicense = null;
        if ($siteUrl !== '') {
            $latestSiteLicense = $this->fetchSiteLatestLicense(
                (string) $config['serverUrl'],
                $siteUrl,
                $installationToken,
                $messages
            );
        }
        $siteTrialStatus = $siteUrl !== '' ? $this->fetchSiteTrialStatus(
            (string) $config['serverUrl'],
            $siteUrl,
            $installationToken,
            $messages
        ) : null;
        if (is_array($siteTrialStatus) && (bool) ($siteTrialStatus['requires_subscription'] ?? false)) {
            $messages[] = $this->translate('subscription.message.freeQuotaExhausted');
        }

        $licenseStatus = null;
        if ($configuredLicenseKey !== '') {
            $messageCountBeforeLicenseStatus = count($messages);
            $licenseStatus = $this->fetchLicenseStatus(
                serverUrl: (string) $config['serverUrl'],
                licenseKey: $configuredLicenseKey,
                siteUrl: $siteUrl,
                messages: $messages,
            );
            if ($licenseStatus === null && count($messages) === $messageCountBeforeLicenseStatus) {
                $messages[] = $this->translate('subscription.message.licenseStatusQueryFailed');
            }
        }
        $displayLicenseStatus = $licenseStatus === null ? null : $this->buildDisplayStatus($licenseStatus);
        $displayLatestSiteLicense = $latestSiteLicense === null ? null : $this->buildDisplayStatus($latestSiteLicense);
        $hasSubscription = $displayLicenseStatus !== null
            && !in_array((string) ($displayLicenseStatus['status'] ?? ''), ['canceled', 'pending'], true);
        $currentPlanCode = $hasSubscription ? strtolower(trim((string) ($displayLicenseStatus['plan_code'] ?? ''))) : '';
        foreach ($plans as $index => $plan) {
            $planCode = strtolower(trim((string) ($plan['code'] ?? '')));
            $plans[$index]['is_current'] = $currentPlanCode !== '' && $planCode === $currentPlanCode;
        }

        if (
            $action === 'buy'
            && $hasSubscription
            && $currentPlanCode !== ''
            && $selectedPlan !== ''
            && $selectedPlan !== $currentPlanCode
            && $selectedPlan !== 'topup20'
        ) {
            $messages[] = $this->translate('subscription.message.alreadySubscribedOtherBlocked');
        }

        if ($action === 'buy' && $selectedPlan !== '') {
            if ($selectedPlan === 'topup20') {
                if ($configuredLicenseKey === '') {
                    $messages[] = $this->translate('subscription.message.needLicenseForTopup');
                } else {
                    try {
                        $paymentUrl = $this->createTopupLink(
                            serverUrl: (string) $config['serverUrl'],
                            siteUrl: $siteUrl,
                            licenseKey: $configuredLicenseKey,
                        );
                        $paymentUrlToOpen = $paymentUrl;
                        $messages[] = $this->translate('subscription.message.topupOpenInNewTab');
                    } catch (\Throwable $e) {
                        $messages[] = $this->translate('subscription.message.topupCreateFailed');
                        $messages[] = $this->translate('subscription.message.detailPrefix') . ' ' . $e->getMessage();
                    }
                }
            } elseif ($hasSubscription && $currentPlanCode !== '' && $selectedPlan !== $currentPlanCode) {
                // Backend guard to prevent manual URL tampering from bypassing frontend disabled state.
                $messages[] = $this->translate('subscription.message.singleSubscriptionBlocked');
            } elseif ($hasSubscription && $currentPlanCode !== '' && $selectedPlan === $currentPlanCode) {
                if ($configuredLicenseKey === '') {
                    $messages[] = $this->translate('subscription.message.needLicenseForPortal');
                } else {
                    try {
                        $portalUrlToOpen = $this->createBillingPortalUrl(
                            serverUrl: (string) $config['serverUrl'],
                            siteUrl: $siteUrl,
                            licenseKey: $configuredLicenseKey,
                            returnUrl: $absoluteModuleUrl
                        );
                        $messages[] = $this->translate('subscription.message.usePortalForCancel');
                    } catch (\Throwable $e) {
                        $messages[] = $this->translate('subscription.message.portalCreateFailed');
                        $messages[] = $this->translate('subscription.message.detailPrefix') . ' ' . $e->getMessage();
                    }
                }
            } else {
                try {
                    $paymentUrl = $this->createPaymentLink(
                        serverUrl: (string) $config['serverUrl'],
                        siteUrl: $siteUrl,
                        planCode: $selectedPlan,
                    );
                    if ($paymentUrl !== '') {
                        // Backend modules run inside an iframe; direct 3xx redirects to Stripe are blocked by frame-src CSP.
                        $paymentUrlToOpen = $paymentUrl;
                        $messages[] = $this->translate('subscription.message.checkoutOpenInNewTab');
                    } else {
                        $messages[] = $this->translate('subscription.message.paymentLinkEmpty');
                    }
                } catch (\Throwable $e) {
                    $messages[] = $this->translate('subscription.message.paymentLinkCreateFailed');
                    $messages[] = $this->translate('subscription.message.detailPrefix') . ' ' . $e->getMessage();
                }
            }
        } elseif ($action === 'portal') {
            if ($configuredLicenseKey === '') {
                $messages[] = $this->translate('subscription.message.needLicenseForPortal');
            } else {
                try {
                    $portalUrlToOpen = $this->createBillingPortalUrl(
                        serverUrl: (string) $config['serverUrl'],
                        siteUrl: $siteUrl,
                        licenseKey: $configuredLicenseKey,
                        returnUrl: $absoluteModuleUrl
                    );
                    if ($portalUrlToOpen !== '') {
                        $messages[] = $this->translate('subscription.message.portalOpenInNewTab');
                    }
                } catch (\Throwable $e) {
                    $messages[] = $this->translate('subscription.message.portalCreateFailed');
                    $messages[] = $this->translate('subscription.message.detailPrefix') . ' ' . $e->getMessage();
                }
            }
        }

        $moduleTemplate->assignMultiple(
            [
                'plans' => $plans,
                'messages' => $messages,
                'licenseStatus' => $displayLicenseStatus,
                'siteUrl' => $siteUrl,
                'licenseKeyConfigured' => (string) $config['licenseKey'] !== '',
                'paymentUrlToOpen' => $paymentUrlToOpen,
                'portalUrlToOpen' => $portalUrlToOpen,
                'latestSiteLicense' => $displayLatestSiteLicense,
                'siteTrialStatus' => $siteTrialStatus,
                'moduleUrl' => $moduleUrl,
                'absoluteModuleUrl' => $absoluteModuleUrl,
                'configuredLicenseKey' => $configuredLicenseKey,
                'logoUrl' => PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName('EXT:barrierefrei_space/Resources/Public/Icons/logo.svg')),
                'cancelSubscriptionUrl' => (string) $uriBuilder->buildUriFromRoute('web_barrierefreispace_subscription', ['action' => 'portal']),
                'refreshUrl' => (string) $uriBuilder->buildUriFromRoute('web_barrierefreispace_subscription', ['action' => 'refresh']),
                'hasSubscription' => $hasSubscription,
                'currentPlanCode' => $currentPlanCode,
            ]
        );
        return $moduleTemplate->renderResponse('Index');
    }

    /**
     * Read extension configuration and force built-in server URL to avoid exposing configurable serverUrl in backend.
     */
    private function readExtensionConfig(): array
    {
        $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('barrierefrei_space');
        return [
            'serverUrl' => self::DEFAULT_SERVER_URL,
            'licenseKey' => (string) ($config['licenseKey'] ?? ''),
        ];
    }

    private function resolveSiteUrl(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $requestHost = strtolower($uri->getHost());

        try {
            $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
            if ($requestHost !== '') {
                foreach ($sites as $site) {
                    $siteBase = (string) $site->getBase();
                    $siteHost = strtolower((string) parse_url($siteBase, PHP_URL_HOST));
                    if ($siteHost === $requestHost) {
                        return $siteBase;
                    }
                }
            }
            if (count($sites) === 1) {
                return (string) array_values($sites)[0]->getBase();
            }
        } catch (\Throwable) {
        }

        $authority = $uri->getAuthority();
        if ($authority === '') {
            return '';
        }

        $scheme = $uri->getScheme() !== '' ? $uri->getScheme() : 'https';
        return $scheme . '://' . $authority . '/';
    }

    /**
     * Build installation token from site host + TYPO3 encryptionKey to protect site-status APIs against enumeration.
     */
    private function buildInstallationTokenForSiteUrl(string $siteUrl): string
    {
        $siteHost = $this->extractSiteHost($siteUrl);
        if ($siteHost === '') {
            return '';
        }

        $encryptionKey = trim((string) ($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? ''));
        if ($encryptionKey === '') {
            return '';
        }
        // Include site host in signature so different sites under one installation do not share the same token.
        return hash_hmac('sha256', $siteHost, $encryptionKey);
    }

    /**
     * Extract normalized host from site URL (lowercase, strip www.) to align with AI-side grouping rules.
     */
    private function extractSiteHost(string $siteUrl): string
    {
        $host = strtolower(trim((string) parse_url($siteUrl, PHP_URL_HOST)));
        if ($host === '') {
            return '';
        }
        if (str_starts_with($host, 'www.')) {
            return substr($host, 4);
        }
        return $host;
    }

    /**
     * Request subscription plans and return array suitable for template rendering.
     */
    private function fetchPlans(string $serverUrl, array &$messages): array
    {
        if ($serverUrl === '') {
            $messages[] = $this->translate('subscription.message.plansLoadFailed') . ' Detail: serverUrl is empty';
            return [];
        }

        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request($serverUrl . '/v1/plans', 'GET', [
                'timeout' => 10,
            ]);
            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload) || !isset($payload['plans']) || !is_array($payload['plans'])) {
                $messages[] = $this->buildRemoteErrorMessage(
                    $this->translate('subscription.message.plansLoadFailed'),
                    'Invalid JSON payload from /v1/plans'
                );
                return [];
            }
            return $payload['plans'];
        } catch (\Throwable $e) {
            $messages[] = $this->buildRemoteErrorMessage(
                $this->translate('subscription.message.plansLoadFailed'),
                $e
            );
            return [];
        }
    }

    /**
     * Query current license status from server for backend subscription page display.
     */
    private function fetchLicenseStatus(string $serverUrl, string $licenseKey, string $siteUrl, array &$messages): ?array
    {
        if ($serverUrl === '' || $licenseKey === '') {
            return null;
        }

        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request($serverUrl . '/v1/license-status', 'GET', [
                'query' => [
                    'license_key' => $licenseKey,
                    'site_url' => $siteUrl,
                ],
                'timeout' => 10,
            ]);
            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                $messages[] = $this->buildRemoteErrorMessage(
                    $this->translate('subscription.message.licenseStatusQueryFailed'),
                    'Invalid JSON payload from /v1/license-status'
                );
                return null;
            }
            return $payload;
        } catch (\Throwable $e) {
            $messages[] = $this->buildRemoteErrorMessage(
                $this->translate('subscription.message.licenseStatusQueryFailed'),
                $e
            );
            return null;
        }
    }

    /**
     * Fetch latest site license to help users copy/paste license key into TYPO3 configuration.
     */
    private function fetchSiteLatestLicense(string $serverUrl, string $siteUrl, string $installationToken, array &$messages): ?array
    {
        if ($serverUrl === '' || $siteUrl === '' || $installationToken === '') {
            return null;
        }
        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request($serverUrl . '/v1/site-latest-license', 'GET', [
                'query' => [
                    'site_url' => $siteUrl,
                    'installation_token' => $installationToken,
                ],
                'timeout' => 10,
            ]);
            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                $messages[] = $this->buildRemoteErrorMessage(
                    $this->translate('subscription.message.siteLatestLicenseQueryFailed'),
                    'Invalid JSON payload from /v1/site-latest-license'
                );
                return null;
            }
            return $payload;
        } catch (\Throwable $e) {
            if ($this->extractHttpStatusCodeFromThrowable($e) === 404) {
                return null;
            }
            $messages[] = $this->buildRemoteErrorMessage(
                $this->translate('subscription.message.siteLatestLicenseQueryFailed'),
                $e
            );
            return null;
        }
    }

    /**
     * Fetch site free-trial status (total/used/remaining/requires subscription).
     */
    private function fetchSiteTrialStatus(string $serverUrl, string $siteUrl, string $installationToken, array &$messages): ?array
    {
        if ($serverUrl === '' || $siteUrl === '' || $installationToken === '') {
            return null;
        }
        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request($serverUrl . '/v1/site-trial-status', 'GET', [
                'query' => [
                    'site_url' => $siteUrl,
                    'installation_token' => $installationToken,
                ],
                'timeout' => 10,
            ]);
            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                $messages[] = $this->buildRemoteErrorMessage(
                    $this->translate('subscription.message.siteTrialStatusQueryFailed'),
                    'Invalid JSON payload from /v1/site-trial-status'
                );
                return null;
            }
            return $payload;
        } catch (\Throwable $e) {
            $messages[] = $this->buildRemoteErrorMessage(
                $this->translate('subscription.message.siteTrialStatusQueryFailed'),
                $e
            );
            return null;
        }
    }

    /**
     * Convert remote API failures into visible diagnostics while redacting license keys and installation tokens.
     */
    private function buildRemoteErrorMessage(string $prefix, \Throwable|string $error): string
    {
        $detail = $error instanceof \Throwable
            ? get_class($error) . ': ' . $error->getMessage()
            : $error;
        $detail = $this->sanitizeRemoteErrorMessage($detail);
        return trim($prefix . ' Detail: ' . $detail);
    }

    private function extractHttpStatusCodeFromThrowable(\Throwable $error): int
    {
        if (!method_exists($error, 'getResponse')) {
            return 0;
        }
        try {
            $response = $error->getResponse();
            if (is_object($response) && method_exists($response, 'getStatusCode')) {
                return (int) $response->getStatusCode();
            }
        } catch (\Throwable) {
        }
        return 0;
    }

    private function sanitizeRemoteErrorMessage(string $message): string
    {
        $safe = trim($message);
        if ($safe === '') {
            return 'No exception message';
        }
        $safe = preg_replace('/(license_key=)[^&\s]+/i', '$1[REDACTED]', $safe) ?? $safe;
        $safe = preg_replace('/(installation_token=)[^&\s]+/i', '$1[REDACTED]', $safe) ?? $safe;
        $safe = preg_replace('/(api[_-]?key\s*[:=]\s*)[^\s,;]+/i', '$1[REDACTED]', $safe) ?? $safe;
        $safe = preg_replace('/(token\s*[:=]\s*)[^\s,;]+/i', '$1[REDACTED]', $safe) ?? $safe;
        $safe = preg_replace('/\b(sk|pk)_(live|test)_[A-Za-z0-9]+/', '[REDACTED_STRIPE_KEY]', $safe) ?? $safe;
        $safe = preg_replace('/\bwhsec_[A-Za-z0-9]+/', '[REDACTED_WEBHOOK_SECRET]', $safe) ?? $safe;
        return $safe;
    }

    /**
     * Call Python backend to create payment link and return redirect URL.
     */
    private function createPaymentLink(string $serverUrl, string $siteUrl, string $planCode): string
    {
        if ($serverUrl === '' || $siteUrl === '') {
            throw new \RuntimeException('Missing serverUrl or siteUrl');
        }

        try {
            $response = GeneralUtility::makeInstance(RequestFactory::class)->request($serverUrl . '/v1/checkout-url', 'POST', [
                'json' => [
                    'plan_code' => $planCode,
                    'site_url' => $siteUrl,
                ],
                'timeout' => 15,
            ]);
            $status = $response->getStatusCode();
            $payload = json_decode((string) $response->getBody(), true);
            if ($status >= 400) {
                $detail = is_array($payload) ? (string) ($payload['detail'] ?? json_encode($payload, JSON_UNESCAPED_UNICODE)) : 'HTTP ' . $status;
                throw new \RuntimeException('Server error: ' . $detail);
            }
            if (!is_array($payload)) {
                throw new \RuntimeException('Server response is not valid JSON');
            }
            $paymentUrl = (string) ($payload['checkout_url'] ?? '');
            if ($paymentUrl === '') {
                $paymentUrl = (string) ($payload['payment_url'] ?? '');
            }
            if ($paymentUrl === '') {
                throw new \RuntimeException('checkout_url missing in response');
            }
            return $paymentUrl;
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Create Stripe Billing Portal URL for subscription cancellation and billing management.
     */
    private function createBillingPortalUrl(string $serverUrl, string $siteUrl, string $licenseKey, string $returnUrl): string
    {
        if ($serverUrl === '' || $siteUrl === '' || $licenseKey === '') {
            throw new \RuntimeException('Missing serverUrl/siteUrl/licenseKey');
        }
        $response = GeneralUtility::makeInstance(RequestFactory::class)->request($serverUrl . '/v1/billing-portal-url', 'POST', [
            'json' => [
                'license_key' => $licenseKey,
                'site_url' => $siteUrl,
                'return_url' => $returnUrl,
            ],
            'timeout' => 15,
        ]);
        $status = $response->getStatusCode();
        $payload = json_decode((string) $response->getBody(), true);
        if ($status >= 400) {
            $detail = is_array($payload) ? (string) ($payload['detail'] ?? 'HTTP ' . $status) : 'HTTP ' . $status;
            throw new \RuntimeException('Server error: ' . $detail);
        }
        if (!is_array($payload)) {
            throw new \RuntimeException('Server response is not valid JSON');
        }
        $portalUrl = (string) ($payload['portal_url'] ?? '');
        if ($portalUrl === '') {
            throw new \RuntimeException('portal_url missing in response');
        }
        return $portalUrl;
    }

    /**
     * Create one-time +20 credits top-up payment link for temporary monthly quota extension.
     */
    private function createTopupLink(string $serverUrl, string $siteUrl, string $licenseKey): string
    {
        if ($serverUrl === '' || $siteUrl === '' || $licenseKey === '') {
            throw new \RuntimeException('Missing serverUrl/siteUrl/licenseKey');
        }
        $response = GeneralUtility::makeInstance(RequestFactory::class)->request($serverUrl . '/v1/topup-link', 'POST', [
            'json' => [
                'license_key' => $licenseKey,
                'site_url' => $siteUrl,
            ],
            'timeout' => 15,
        ]);
        $status = $response->getStatusCode();
        $payload = json_decode((string) $response->getBody(), true);
        if ($status >= 400) {
            $detail = is_array($payload) ? (string) ($payload['detail'] ?? 'HTTP ' . $status) : 'HTTP ' . $status;
            throw new \RuntimeException('Server error: ' . $detail);
        }
        if (!is_array($payload)) {
            throw new \RuntimeException('Server response is not valid JSON');
        }
        $paymentUrl = (string) ($payload['payment_url'] ?? '');
        if ($paymentUrl === '') {
            throw new \RuntimeException('payment_url missing in response');
        }
        return $paymentUrl;
    }

    /**
     * Build template-friendly subscription status fields including formatted timestamps and status badge class.
     */
    private function buildDisplayStatus(array $status): array
    {
        $rawStatus = strtolower(trim((string) ($status['status'] ?? 'pending')));
        $badgeClass = match ($rawStatus) {
            'active' => 'success',
            'past_due' => 'warning',
            'canceled' => 'danger',
            default => 'default',
        };

        $status['status_badge_class'] = $badgeClass;
        $status['current_period_end_label'] = $this->formatTimestamp((int) ($status['current_period_end'] ?? 0));
        $status['last_synced_at_label'] = $this->formatTimestamp((int) ($status['last_synced_at'] ?? 0));
        $status['canceled_at_label'] = $this->formatTimestamp((int) ($status['canceled_at'] ?? 0));
        $status['pending_cancellation'] = (bool) ($status['pending_cancellation'] ?? false);
        $timeline = $status['timeline'] ?? [];
        if (is_array($timeline)) {
            foreach ($timeline as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $timeline[$index]['created_at_label'] = $this->formatTimestamp((int) ($item['created_at'] ?? 0));
            }
            $status['timeline'] = $timeline;
        }
        return $status;
    }

    /**
     * Format unix timestamp into readable text to avoid repeating date logic in template.
     */
    private function formatTimestamp(int $timestamp): string
    {
        if ($timestamp <= 0) {
            return '-';
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Build absolute URL for backend module route to satisfy Stripe return_url requirements.
     */
    private function buildAbsoluteUrlFromRequest(ServerRequestInterface $request, string $pathOrUrl): string
    {
        if ($pathOrUrl === '') {
            return '';
        }
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }

        $requestUri = $request->getUri();
        $baseUri = new Uri($requestUri->getScheme() . '://' . $requestUri->getAuthority());
        $parts = parse_url($pathOrUrl);
        $path = (string) ($parts['path'] ?? '');
        $query = (string) ($parts['query'] ?? '');

        $uri = $baseUri->withPath($path);
        if ($query !== '') {
            $uri = $uri->withQuery($query);
        }
        return (string) $uri;
    }

    /**
     * Resolve localized label based on current backend user language and fallback to key when missing.
     */
    private function translate(string $key): string
    {
        $label = 'LLL:EXT:barrierefrei_space/Resources/Private/Language/locallang.xlf:' . $key;
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if ($backendUser === null) {
            return $key;
        }
        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($backendUser);
        $translated = (string) $languageService->sL($label);
        return $translated !== '' ? $translated : $key;
    }
}
