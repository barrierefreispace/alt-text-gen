<?php

namespace BarrierefreiSpace\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Doctrine\DBAL\ParameterType;

final class VendorServerException extends \RuntimeException
{
    private int $vendorStatusCode;
    private string $vendorErrorCode;

    public function __construct(string $message, int $vendorStatusCode, string $vendorErrorCode = '')
    {
        parent::__construct($message);
        $this->vendorStatusCode = $vendorStatusCode;
        $this->vendorErrorCode = $vendorErrorCode;
    }

    public function getVendorStatusCode(): int
    {
        return $this->vendorStatusCode;
    }

    public function getVendorErrorCode(): string
    {
        return $this->vendorErrorCode;
    }
}

final class AltTextController
{
    private const DEFAULT_SERVER_URL = 'https://alt-text-api-647809240796.europe-west3.run.app';
    private const TARGET_MAX_EDGE_PX = 512;
    private const JPEG_QUALITY = 80;
    private const MAX_SOURCE_BYTES = 20_000_000;
    private const DEFAULT_STYLE = 'formal';
    private const STYLE_ALLOWLIST = [
        'formal',
        'friendly',
        'casual',
        'professional',
        'diplomatic',
        'confident',
        'primary_school',
        'middle_school',
        'high_school',
        'academic',
        'simplified',
        'vivid',
        'empathetic',
        'luxury',
        'engaging',
        'direct',
        'persuasive',
        'minimalist',
        'storytelling',
        'technical',
        'brand_safe',
    ];
    private const MAX_SEO_KEYWORDS = 6;

    /**
     * Function: Handle backend Ajax request: load file reference → resolve content language → build resized JPEG (512px/quality80/no EXIF) → forward to vendor server → return ALT text.
     *
     * Parameters:
     *     $request (ServerRequestInterface): PSR-7 request.
     * Returns:
     *     ResponseInterface: JSON response.
     */
    public function generate(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $fileReferenceUid = $this->readFileReferenceUid($request);
            $style = $this->readStyle($request);
            $seoKeywords = $this->readSeoKeywords($request);
            $this->persistPreferences($fileReferenceUid, $style, $seoKeywords);
            $extConfig = $this->readExtensionConfig();

            $fileReferenceRow = $this->fetchSysFileReferenceRow($fileReferenceUid);
            $languageTag = $this->resolveLanguageTagFromFileReferenceRow($fileReferenceRow);
            $siteUrl = $this->resolveSiteUrlFromFileReferenceRow($fileReferenceRow);
            $installationToken = $this->buildInstallationTokenForSiteUrl($siteUrl);

            $jpegPath = $this->buildResizedJpegForFileReference($fileReferenceUid);
            try {
                $altText = $this->requestAltTextFromVendorServer(
                    serverUrl: $extConfig['serverUrl'],
                    licenseKey: $extConfig['licenseKey'],
                    siteUrl: $siteUrl,
                    languageTag: $languageTag,
                    style: $style,
                    seoKeywords: $seoKeywords,
                    jpegPath: $jpegPath,
                    installationToken: $installationToken
                );
            } finally {
                // Remove temp JPEG regardless of request success/failure to prevent /tmp residue accumulation.
                @unlink($jpegPath);
            }

            return new JsonResponse(['alt_text' => $altText]);
        } catch (VendorServerException $e) {
            $statusCode = $e->getVendorStatusCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 400;
            }
            return new JsonResponse(
                [
                    'error' => 'ALT_GENERATION_FAILED',
                    'error_code' => $e->getVendorErrorCode(),
                    'message' => $e->getMessage(),
                ],
                $statusCode
            );
        } catch (\Throwable $e) {
            return new JsonResponse(
                [
                    'error' => 'ALT_GENERATION_FAILED',
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    /**
     * Function: Save file-reference level generation preferences (style, SEO keywords) for cross-browser/device persistence.
     *
     * Parameters:
     *     $request (ServerRequestInterface): PSR-7 request.
     * Returns:
     *     ResponseInterface: JSON response.
     */
    public function savePreferences(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $fileReferenceUid = $this->readFileReferenceUid($request);
            $style = $this->readStyle($request);
            $seoKeywords = $this->readSeoKeywords($request);
            $this->persistPreferences($fileReferenceUid, $style, $seoKeywords);
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(
                [
                    'error' => 'ALT_PREFERENCES_SAVE_FAILED',
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    /**
     * Function: Read and validate fileReferenceUid from request body, supporting both form and JSON payloads.
     *
     * Parameters:
     *     $request (ServerRequestInterface): Request.
     * Returns:
     *     int: sys_file_reference.uid.
     * Exceptions:
     *     \RuntimeException: Invalid fileReferenceUid.
     */
    private function readFileReferenceUid(ServerRequestInterface $request): int
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $raw = (string) $request->getBody();
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        }

        $uid = (int) ($body['fileReferenceUid'] ?? 0);
        if ($uid <= 0) {
            throw new \RuntimeException('Invalid fileReferenceUid');
        }

        return $uid;
    }

    /**
     * Function: Read and normalize style parameter, fallback to default when missing or invalid.
     *
     * Parameters:
     *     $request (ServerRequestInterface): Request.
     * Returns:
     *     string: Style identifier.
     */
    private function readStyle(ServerRequestInterface $request): string
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $raw = (string) $request->getBody();
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        }

        $style = strtolower(trim((string) ($body['style'] ?? self::DEFAULT_STYLE)));
        if (!in_array($style, self::STYLE_ALLOWLIST, true)) {
            return self::DEFAULT_STYLE;
        }
        return $style;
    }

    /**
     * Function: Read and normalize SEO keywords (comma-separated, max 6) to guide ALT text generation.
     *
     * Parameters:
     *     $request (ServerRequestInterface): Request.
     * Returns:
     *     string: Normalized keyword string.
     */
    private function readSeoKeywords(ServerRequestInterface $request): string
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            $raw = (string) $request->getBody();
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        }

        $rawKeywords = (string) ($body['seoKeywords'] ?? '');
        $parts = preg_split('/,/', $rawKeywords) ?: [];
        $seen = [];
        $normalized = [];
        foreach ($parts as $part) {
            $keyword = trim((string) $part);
            if ($keyword === '') {
                continue;
            }
            $dedupeKey = strtolower($keyword);
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;
            $normalized[] = $keyword;
            if (count($normalized) >= self::MAX_SEO_KEYWORDS) {
                break;
            }
        }

        return implode(', ', $normalized);
    }

    /**
     * Function: Read extension configuration and force built-in AI server URL to avoid exposing serverUrl in backend settings.
     *
     * Returns:
     *     array{serverUrl:string,licenseKey:string}: Config.
     */
    private function readExtensionConfig(): array
    {
        $extConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('barrierefrei_space');
        $licenseKey = (string) ($extConfig['licenseKey'] ?? '');

        return [
            'serverUrl' => self::DEFAULT_SERVER_URL,
            'licenseKey' => $licenseKey,
        ];
    }

    /**
     * Function: Load sys_file_reference row for language and site resolving (without coupling to FormEngine internals).
     *
     * Parameters:
     *     $fileReferenceUid (int): sys_file_reference.uid.
     * Returns:
     *     array<string, mixed>: Row.
     * Exceptions:
     *     \RuntimeException: Record not found.
     */
    private function fetchSysFileReferenceRow(int $fileReferenceUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        $row = $queryBuilder
            ->select('uid', 'pid', 'sys_language_uid', 'tablenames', 'uid_foreign')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($fileReferenceUid, ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($row)) {
            throw new \RuntimeException('File reference not found');
        }

        return $row;
    }

    /**
     * Function: Resolve site language tag (e.g. de-DE) from file reference pid + sys_language_uid to generate ALT text in that language.
     *
     * Parameters:
     *     $fileReferenceRow (array): sys_file_reference row.
     * Returns:
     *     string: BCP47 language tag.
     */
    private function resolveLanguageTagFromFileReferenceRow(array $fileReferenceRow): string
    {
        $pid = (int) ($fileReferenceRow['pid'] ?? 0);
        $languageUid = (int) ($fileReferenceRow['sys_language_uid'] ?? 0);

        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
            $siteLanguage = $site->getLanguageById($languageUid);

            if (method_exists($siteLanguage, 'getHreflang')) {
                $hreflang = (string) $siteLanguage->getHreflang();
                if ($hreflang !== '') {
                    return $hreflang;
                }
            }

            if (method_exists($siteLanguage, 'getLocale')) {
                $locale = $siteLanguage->getLocale();
                $localeString = is_object($locale) ? (string) $locale : (string) $locale;
                return $localeString !== '' ? str_replace('_', '-', $localeString) : 'en';
            }
        } catch (\Throwable) {
        }

        return 'en';
    }

    private function resolveSiteUrlFromFileReferenceRow(array $fileReferenceRow): string
    {
        $pid = (int) ($fileReferenceRow['pid'] ?? 0);

        try {
            return (string) GeneralUtility::makeInstance(SiteFinder::class)
                ->getSiteByPageId($pid)
                ->getBase();
        } catch (\Throwable) {
        }

        return '';
    }

    /**
     * Function: Build a resized JPEG from sys_file_reference (max edge 512px, quality 80, strip EXIF).
     *
     * Parameters:
     *     $fileReferenceUid (int): sys_file_reference.uid.
     * Returns:
     *     string: Local temp path of generated JPEG.
     */
    private function buildResizedJpegForFileReference(int $fileReferenceUid): string
    {
        $fileReference = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject($fileReferenceUid);
        $file = $fileReference->getOriginalFile();
        $localPath = (string) $file->getForLocalProcessing(false);

        if ($localPath === '' || !is_file($localPath)) {
            throw new \RuntimeException('Cannot access local file');
        }

        $size = @filesize($localPath);
        if (is_int($size) && $size > self::MAX_SOURCE_BYTES) {
            throw new \RuntimeException('Source image too large');
        }

        return $this->createResizedJpegFromLocalPath($localPath);
    }

    /**
     * Function: Use GD to resize and re-encode image as JPEG, naturally stripping EXIF metadata (re-encoding does not preserve original metadata).
     *
     * Parameters:
     *     $sourcePath (string): Source image path.
     * Returns:
     *     string: JPEG temp file path.
     */
    private function createResizedJpegFromLocalPath(string $sourcePath): string
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new \RuntimeException('PHP GD extension required');
        }

        $bytes = @file_get_contents($sourcePath);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Failed to read image');
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            throw new \RuntimeException('Unsupported image format');
        }

        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);
        if ($srcWidth <= 0 || $srcHeight <= 0) {
            imagedestroy($src);
            throw new \RuntimeException('Invalid image dimensions');
        }

        [$dstWidth, $dstHeight] = $this->calculateResizedDimensions($srcWidth, $srcHeight, self::TARGET_MAX_EDGE_PX);

        $dst = imagecreatetruecolor($dstWidth, $dstHeight);
        $this->fillBackgroundWhite($dst);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

        $tmpPath = (string) tempnam(sys_get_temp_dir(), 'barrierefrei_space_');
        $tmpJpegPath = $tmpPath . '.jpg';
        @unlink($tmpPath);

        $ok = imagejpeg($dst, $tmpJpegPath, self::JPEG_QUALITY);
        imagedestroy($src);
        imagedestroy($dst);

        if (!$ok || !is_file($tmpJpegPath)) {
            throw new \RuntimeException('Failed to encode JPEG');
        }

        return $tmpJpegPath;
    }

    /**
     * Function: Calculate resized dimensions while preserving aspect ratio (max edge does not exceed target).
     *
     * Parameters:
     *     $width (int): Original width.
     *     $height (int): Original height.
     *     $maxEdge (int): Target max edge.
     * Returns:
     *     array{0:int,1:int}: New dimensions.
     */
    private function calculateResizedDimensions(int $width, int $height, int $maxEdge): array
    {
        $currentMax = max($width, $height);
        if ($currentMax <= $maxEdge) {
            return [$width, $height];
        }
        $scale = $maxEdge / $currentMax;
        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }

    /**
     * Function: Fill target image with white background to avoid black background when converting transparent PNG to JPEG (JPEG has no alpha).
     *
     * Parameters:
     *     $dst (\GdImage): Target canvas.
     * Returns:
     *     void
     */
    private function fillBackgroundWhite(\GdImage $dst): void
    {
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, imagesx($dst), imagesy($dst), $white);
    }

    /**
     * Function: Send the JPEG to vendor server and request ALT text in the specified language.
     *
     * Parameters:
     *     $serverUrl (string): Vendor server base URL.
     *     $licenseKey (string): License key.
     *     $siteUrl (string): Site base URL.
     *     $languageTag (string): Language tag.
     *     $style (string): Style identifier.
     *     $seoKeywords (string): SEO keywords (comma-separated).
     *     $jpegPath (string): Local JPEG path.
     * Returns:
     *     string: ALT text.
     */
    private function requestAltTextFromVendorServer(
        string $serverUrl,
        string $licenseKey,
        string $siteUrl,
        string $languageTag,
        string $style,
        string $seoKeywords,
        string $jpegPath,
        string $installationToken = ''
    ): string {
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

        $response = $requestFactory->request($serverUrl . '/v1/alt-text', 'POST', [
            'multipart' => [
                [
                    'name' => 'image',
                    'contents' => fopen($jpegPath, 'rb'),
                    'filename' => 'image.jpg',
                ],
                ['name' => 'license_key', 'contents' => $licenseKey],
                ['name' => 'language', 'contents' => $languageTag],
                ['name' => 'style', 'contents' => $style],
                ['name' => 'seo_keywords', 'contents' => $seoKeywords],
                ['name' => 'site_url', 'contents' => $siteUrl],
                ['name' => 'installation_token', 'contents' => $installationToken],
                ['name' => 'source', 'contents' => 'typo3'],
            ],
            'timeout' => 30,
        ]);

        $statusCode = $response->getStatusCode();
        $raw = (string) $response->getBody();
        $payload = json_decode($raw, true);

        if ($statusCode >= 400) {
            $detail = '';
            $errorCode = '';
            if (is_array($payload)) {
                if (isset($payload['error_code'])) {
                    $errorCode = trim((string) $payload['error_code']);
                }
                if (isset($payload['detail']) && is_array($payload['detail'])) {
                    $detail = trim((string) ($payload['detail']['detail'] ?? $payload['detail']['message'] ?? ''));
                    if ($errorCode === '' && isset($payload['detail']['error_code'])) {
                        $errorCode = trim((string) $payload['detail']['error_code']);
                    }
                } elseif (isset($payload['detail'])) {
                    $detail = trim((string) $payload['detail']);
                } elseif (isset($payload['message'])) {
                    $detail = trim((string) $payload['message']);
                }
            }
            if ($detail === '') {
                $detail = trim($raw);
            }
            throw new VendorServerException($detail, $statusCode, $errorCode);
        }

        if (!is_array($payload) || !isset($payload['alt_text'])) {
            throw new \RuntimeException('Invalid vendor response');
        }

        $altText = trim((string) $payload['alt_text']);
        if ($altText === '') {
            throw new \RuntimeException('Empty ALT text');
        }

        return $altText;
    }

    /**
     * Function: Build HMAC-SHA256 installation token from site URL to prove the request originates from a real TYPO3 installation.
     */
    private function buildInstallationTokenForSiteUrl(string $siteUrl): string
    {
        $siteHost = $this->extractSiteHostForToken($siteUrl);
        if ($siteHost === '') {
            return '';
        }
        $encryptionKey = trim((string) ($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? ''));
        if ($encryptionKey === '') {
            return '';
        }
        // Sign with siteHost to prevent token reuse across different sites
        return hash_hmac('sha256', $siteHost, $encryptionKey);
    }

    /**
     * Function: Extract normalized host from site URL (lowercase, strip www.) aligned with SubscriptionModuleController::extractSiteHost.
     */
    private function extractSiteHostForToken(string $siteUrl): string
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
     * Function: Persist style and SEO keywords into sys_file_reference so settings can be reused across browsers/devices.
     */
    private function persistPreferences(int $fileReferenceUid, string $style, string $seoKeywords): void
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder
                ->update('sys_file_reference')
                ->set('tx_barrierefrei_space_style', $style)
                ->set('tx_barrierefrei_space_seo_keywords', $seoKeywords)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($fileReferenceUid, ParameterType::INTEGER)
                    )
                )
                ->executeStatement();
        } catch (\Throwable) {
            // Ignore preference persistence failures since they should not break the main ALT generation flow
        }
    }
}
