# barrierefrei.space ALT Text Generator

[English](README.md) | [Deutsch](README.de.md)

Website: https://www.barrierefrei.space/

TYPO3 v13 extension for AI-generated accessible ALT text, subscription management, and charity impact display.

The extension adds an ALT text generator to TYPO3 image references, provides a branded backend Subscription Center, and includes a small Donation Widget that shows the total amount donated to charity through the site's subscription.

## Features

- AI ALT text generation for `sys_file_reference.alternative`
- Magic wand action directly next to TYPO3 image ALT fields
- Per-image generation preferences persisted on the file reference:
  - Style: `tx_barrierefrei_space_style`
  - SEO keywords: `tx_barrierefrei_space_seo_keywords`
- 5 free usages per domain after installation
- Backend Subscription Center for license and subscription management
- Real Site URL resolution from TYPO3 site configuration
- English and German backend UI
- Donation Widget content element showing total donated amount

## Requirements

- PHP `>= 8.2`
- TYPO3 `^13.0`
- A configured TYPO3 site base URL
- Outbound HTTPS access from TYPO3 to the barrierefrei.space API

## Installation

Install the extension directly from Packagist:

```bash
composer require barrierefrei-space/alt-text-gen
```

To require a specific release line:

```bash
composer require barrierefrei-space/alt-text-gen:^1.0
```

## Enable in TYPO3

1. Enable the extension key `barrierefrei_space`.
2. Run database updates in the Install Tool or Maintenance module.
3. Include the static TypoScript template `ALT Text Generator`.
4. Clear TYPO3 caches.

Database updates add these fields to `sys_file_reference`:

- `tx_barrierefrei_space_style`
- `tx_barrierefrei_space_seo_keywords`

## Extension Configuration

TYPO3 backend path:

`Settings -> Extension Configuration -> barrierefrei_space`

Available setting:

- `licenseKey`: license key for the current site subscription

The service endpoint is built in and hidden from Extension Configuration.

The Site URL is resolved from the current TYPO3 site configuration and cannot be edited in Extension Configuration. Make sure your site base URL is configured correctly in TYPO3.

## ALT Text Generation

Open a TYPO3 record that contains an image reference. The extension adds a magic wand control next to the Alternative Text field.

The generator sends the image and generation preferences to the API, then writes the generated accessible ALT text back into the field.

Supported backend languages:

- English
- German

## Subscription Center

Backend module:

`Web -> Subscription Center`

Route:

`/module/web/barrierefrei-space/subscription`

The Subscription Center provides:

- Current license and subscription status
- Current resolved Site URL
- Latest license key returned for the site
- Plan cards for subscription and top-up purchase
- Billing portal access
- Subscription cancellation for the current plan
- Status timeline with pagination
- First-open guide that can be reopened with the question mark icon
- Loading states for refresh, subscribe, cancel, and buy-once actions
- Links to barrierefrei.space and subscription contact information

Stripe checkout and billing portal links open in a new browser tab to avoid TYPO3 backend iframe CSP restrictions.

## Donation Widget

The extension provides a frontend content element:

`Donation Widget`

CType:

`barrierefreispace_donationwidget`

The widget shows only the total amount donated to charity for this site's subscription and links to:

https://www.barrierefrei.space/charity

The amount is fetched from the API using the configured `licenseKey` and the resolved TYPO3 Site URL.

### Widget Position

In the content element Appearance tab, only these layout choices are available:

- Fixed bottom left
- Fixed bottom right

The widget is intentionally compact and fixed-position so it can communicate charity impact without taking over the page layout.

## Internationalization

Language files:

- English: `Resources/Private/Language/locallang.xlf`
- German: `Resources/Private/Language/de.locallang.xlf`

The backend UI follows the TYPO3 backend user language.

## Migration Notes

Current Donation Widget CType:

`barrierefreispace_donationwidget`

An upgrade wizard migrates legacy list type values to the current CType:

- `alttextgen_donationwidget`
- `barrierefrei_space_donationwidget`
- `barrierefreispace_donationwidget`

## Troubleshooting

### Composer cannot find the package

Make sure the package name is spelled correctly and Packagist is enabled in your Composer configuration.

```bash
composer require barrierefrei-space/alt-text-gen
```

### Site URL is wrong

The extension does not provide a Site URL override. Fix the TYPO3 site configuration instead.

Check:

- Site Management -> Sites
- The site's configured base URL
- Reverse proxy / HTTPS headers, if TYPO3 runs behind a proxy

### License status does not update

Check:

- `licenseKey` is saved in Extension Configuration
- TYPO3 can reach the API over HTTPS
- The displayed Site URL matches the site used for the subscription
- TYPO3 caches were cleared after configuration changes

### Donation Widget is not visible

Check:

- Static TypoScript template `ALT Text Generator` is included
- `licenseKey` is configured
- Site URL is resolved correctly
- The API returns a donation payload for the subscription
- The content element uses one of the fixed bottom layouts

## License

Copyright (c) 2026 barrierefrei.space

Licensed under GPL-2.0-or-later. See [LICENSE](LICENSE).
