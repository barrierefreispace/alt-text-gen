# barrierefrei.space ALT Text Generator

[Deutsch](README.de.md) | [English](README.md)

Website: https://www.barrierefrei.space/

TYPO3-v13-Erweiterung für KI-generierte barrierefreie ALT-Texte, Abo-Verwaltung und die Anzeige des Charity-Impacts.

Die Erweiterung ergänzt TYPO3-Bildreferenzen um einen ALT-Text-Generator, stellt ein gebrandetes Backend-Modul „Subscription Center“ bereit und enthält ein kleines Donation Widget, das den gesamten über das Site-Abonnement gespendeten Betrag anzeigt.

## Funktionen

- KI-gestützte ALT-Text-Generierung für `sys_file_reference.alternative`
- Zauberstab-Aktion direkt neben TYPO3-Bild-ALT-Feldern
- Pro Bildreferenz gespeicherte Generierungseinstellungen:
  - Stil: `tx_barrierefrei_space_style`
  - SEO-Schlüsselwörter: `tx_barrierefrei_space_seo_keywords`
- 5 kostenlose Nutzungen pro Domain nach der Installation
- Backend Subscription Center für Lizenz- und Abo-Verwaltung
- Echte Site-URL aus der TYPO3-Site-Konfiguration
- Backend-Oberfläche auf Deutsch und Englisch
- Donation Widget als Content Element mit Anzeige des gesamten Spendenbetrags

## Anforderungen

- PHP `>= 8.2`
- TYPO3 `^13.0`
- Eine konfigurierte TYPO3-Site-Base-URL
- Ausgehender HTTPS-Zugriff von TYPO3 auf den barrierefrei.space-Dienst

## Installation

Installiere die Erweiterung direkt von Packagist:

```bash
composer require barrierefrei-space/alt-text-gen
```

Für eine bestimmte Release-Linie:

```bash
composer require barrierefrei-space/alt-text-gen:^1.0
```

## In TYPO3 aktivieren

1. Extension Key `barrierefrei_space` aktivieren.
2. Datenbank-Updates im Install Tool oder Maintenance-Modul ausführen.
3. Das statische TypoScript-Template `ALT Text Generator` einbinden.
4. TYPO3-Caches leeren.

Die Datenbank-Updates ergänzen diese Felder in `sys_file_reference`:

- `tx_barrierefrei_space_style`
- `tx_barrierefrei_space_seo_keywords`

## Extension Configuration

TYPO3-Backend-Pfad:

`Settings -> Extension Configuration -> barrierefrei_space`

Verfügbare Einstellung:

- `licenseKey`: Lizenzschlüssel für das aktuelle Site-Abonnement

Der Service-Endpunkt ist fest eingebaut und in der Extension Configuration ausgeblendet.

Die Site-URL wird aus der aktuellen TYPO3-Site-Konfiguration ermittelt und kann nicht in der Extension Configuration geändert werden. Stelle sicher, dass die Base-URL der Site in TYPO3 korrekt konfiguriert ist.

## ALT-Text-Generierung

Öffne einen TYPO3-Datensatz mit Bildreferenz. Die Erweiterung ergänzt neben dem Feld „Alternative Text“ eine Zauberstab-Aktion.

Der Generator sendet Bild und Generierungseinstellungen an den Dienst und schreibt den generierten barrierefreien ALT-Text anschließend zurück in das Feld.

Unterstützte Backend-Sprachen:

- Deutsch
- Englisch

## Subscription Center

Backend-Modul:

`Web -> Subscription Center`

Route:

`/module/web/barrierefrei-space/subscription`

Das Subscription Center bietet:

- Aktuellen Lizenz- und Abo-Status
- Aktuell ermittelte Site-URL
- Neuesten für die Site zurückgegebenen Lizenzschlüssel
- Plan-Karten für Abonnement und Top-up-Kauf
- Zugriff auf das Billing Portal
- Kündigung des aktuellen Abonnements
- Status-Timeline mit Pagination
- Erststart-Assistent, der über das Fragezeichen-Icon erneut geöffnet werden kann
- Ladezustände für Aktualisieren, Abonnieren, Kündigen und Einmalkauf
- Links zu barrierefrei.space und Kontaktinformationen für Abo-Anfragen

Stripe-Checkout- und Billing-Portal-Links öffnen sich in einem neuen Browser-Tab, um iframe-CSP-Einschränkungen im TYPO3-Backend zu vermeiden.

## Donation Widget

Die Erweiterung stellt ein Frontend-Content-Element bereit:

`Donation Widget`

CType:

`barrierefreispace_donationwidget`

Das Widget zeigt ausschließlich den gesamten an gemeinnützige Zwecke gespendeten Betrag für das Site-Abonnement und verlinkt auf:

https://www.barrierefrei.space/#charity

Der Betrag wird anhand des konfigurierten `licenseKey` und der ermittelten TYPO3-Site-URL vom Dienst geladen.

### Widget-Position

Im Appearance-Tab des Content Elements stehen nur diese Layouts zur Verfügung:

- Fixed bottom left
- Fixed bottom right

Das Widget ist bewusst kompakt und fixiert positioniert, damit es den Charity-Impact sichtbar macht, ohne das Seitenlayout zu dominieren.

## Internationalisierung

Sprachdateien:

- Englisch: `Resources/Private/Language/locallang.xlf`
- Deutsch: `Resources/Private/Language/de.locallang.xlf`

Die Backend-Oberfläche folgt der TYPO3-Backend-Benutzersprache.

## Migrationshinweise

Aktueller Donation-Widget-CType:

`barrierefreispace_donationwidget`

Ein Upgrade-Wizard migriert ältere List-Type-Werte auf den aktuellen CType:

- `alttextgen_donationwidget`
- `barrierefrei_space_donationwidget`
- `barrierefreispace_donationwidget`

## Fehlerbehebung

### Composer findet das Paket nicht

Stelle sicher, dass der Paketname korrekt geschrieben ist und Packagist in deiner Composer-Konfiguration aktiviert ist.

```bash
composer require barrierefrei-space/alt-text-gen
```

### Site-URL ist falsch

Die Erweiterung stellt keinen Site-URL-Override bereit. Korrigiere stattdessen die TYPO3-Site-Konfiguration.

Prüfe:

- Site Management -> Sites
- Die konfigurierte Base-URL der Site
- Reverse-Proxy- und HTTPS-Header, falls TYPO3 hinter einem Proxy läuft

### Lizenzstatus aktualisiert sich nicht

Prüfe:

- `licenseKey` ist in der Extension Configuration gespeichert
- TYPO3 kann den barrierefrei.space-Dienst per HTTPS erreichen
- Die angezeigte Site-URL entspricht der Site, für die das Abonnement abgeschlossen wurde
- TYPO3-Caches wurden nach Konfigurationsänderungen geleert

### Donation Widget ist nicht sichtbar

Prüfe:

- Das statische TypoScript-Template `ALT Text Generator` ist eingebunden
- `licenseKey` ist konfiguriert
- Site-URL wird korrekt ermittelt
- Der Dienst liefert einen Spendenbetrag für das Abonnement zurück
- Das Content Element verwendet eines der Fixed-Bottom-Layouts

## Lizenz

Copyright (c) 2026 barrierefrei.space

Lizenziert unter GPL-2.0-or-later. Siehe [LICENSE](LICENSE).
