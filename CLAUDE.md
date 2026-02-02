# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

WordPress Plugin: **Site Enhancer Widgets** - Konsolidiertes Plugin für Wetter- und News-Widgets, optimiert für GeneratePress Sidebar (300px Breite).

**Entwicklungsfokus:** Nur die Plugin-Dateien in diesem Verzeichnis - WordPress Core nicht modifizieren.

## Plugin-Architektur

### Dateien

```
site-enhancer/
├── site-enhancer.php    # Haupt-Plugin (650 Zeilen, alle PHP-Logik)
└── css/
    └── style.css        # Externe Styles mit GeneratePress CSS-Variablen
```

### Code-Struktur (site-enhancer.php)

Das Plugin ist funktional in Blöcken organisiert:

1. **Admin Settings (Zeilen 23-269)**
   - WordPress Options API Integration
   - Settings: `site_enhancer_options` in wp_options
   - Admin-Seite: Einstellungen → Site Enhancer

2. **Weather API Client (Zeilen 271-437)**
   - OpenWeatherMap Forecast API
   - WordPress Transients für Caching
   - Cache-Key: `site_enhancer_weather_data`

3. **Shortcode Handler (Zeilen 439-547)**
   - `[site_weather]` - Wetter-Widget
   - `[news_feed]` - News iframe

4. **CSS & Assets (Zeilen 549-573)**
   - Conditional loading (nur bei Shortcode-Verwendung)

5. **Admin Actions (Zeilen 575-618)**
   - Cache-Management
   - Admin-Notices

6. **Plugin Activation (Zeilen 620-649)**
   - Auto-Migration von `wetter_vorhersage_options`
   - Default-Einstellungen

### Datenfluss

```
Admin-Panel (Einstellungen → Site Enhancer)
    ↓
Options gespeichert: site_enhancer_options
    ↓
Shortcode im Content: [site_weather] oder [news_feed]
    ↓
site_enhancer_get_forecast_data() prüft Cache
    ↓
Bei Cache-Miss: OpenWeatherMap API-Call
    ↓
Cache via set_transient() für konfigurierte Dauer
    ↓
Widget-Rendering mit SVG-Icons
```

## Konfigurationssystem

**Zentrale Funktion:** `site_enhancer_get_option($key)` - Lädt aus DB mit Fallback auf Defaults.

### Verfügbare Optionen

**API & Standort:**
- `api_key` - OpenWeatherMap API-Key (Passwort-Feld, nie in Klartext)
- `latitude` / `longitude` - Koordinaten (-90 bis 90, -180 bis 180)
- `location_name` - Custom Ortsname (optional)
- `forecast_days` - Anzahl Vorschau-Tage (1-5, default: 3)
- `cache_duration` - Cache-Zeit in Minuten (5-1440, default: 30)

### Neue Option hinzufügen

```php
// 1. Default definieren
function site_enhancer_get_default_options() {
    return array(
        // ... existing ...
        'new_option' => 'default_value'
    );
}

// 2. Validierung
function site_enhancer_sanitize_options($input) {
    // ... existing code ...
    if (isset($input['new_option'])) {
        $sanitized['new_option'] = sanitize_text_field($input['new_option']);
    }
    return $sanitized;
}

// 3. Formular-Feld in site_enhancer_render_settings_page()
<tr>
    <th><label for="se_new_option">New Option</label></th>
    <td>
        <input type="text" id="se_new_option"
               name="site_enhancer_options[new_option]"
               value="<?php echo esc_attr($options['new_option']); ?>" />
    </td>
</tr>

// 4. Verwendung
$value = site_enhancer_get_option('new_option');
```

## Cache-Management

**Cache löschen:**
```
Via UI: Einstellungen → Site Enhancer → "Cache jetzt löschen"
Via URL: /wp-admin/admin-post.php?action=se_clear_cache
Via PHP: site_enhancer_clear_cache()
```

**Cache prüfen:**
```php
get_transient('site_enhancer_weather_data');
```

## Shortcodes

### Wetter-Widget

```
[site_weather]
[site_weather city="Hamburg"]
```

**Attribute:**
- `city` - Überschreibt admin_panel location_name

### News-Feed

```
[news_feed]
[news_feed height="800px"]
```

**Attribute:**
- `height` - Iframe-Höhe (default: 1000px)

## API-Integration

**Endpoint:** `https://api.openweathermap.org/data/2.5/forecast`

**Parameter:**
- `lat` / `lon` - Aus Optionen
- `units=metric` - Celsius
- `lang=de` - Deutsche Beschreibungen
- `appid` - API-Key

**Rate Limits (Free Plan):**
- 1.000 Calls/Tag
- 60 Calls/Minute

**Caching-Strategie:**
Bei 30 Min. Cache & 100 Pageviews/Tag ≈ 48 API-Calls → gut innerhalb Limits.

## CSS-System

**GeneratePress CSS-Variablen (automatisch verwendet):**
```css
--gp-foreground-color    /* Textfarbe */
--gp-background-color    /* Hintergrund */
--gp-perex-font-size     /* Schriftgröße */
--gp-font-family         /* Schriftart */
```

**Dark Mode:** Automatisch via `body.generate-dark-mode` oder `@media (prefers-color-scheme: dark)`

**Responsive:**
- Default: 300px max-width (Sidebar)
- < 280px: kleinere Fonts/Icons
- < 500px: 100% width

## Sicherheits-Pattern

**API-Key-Schutz:**
- Password-Input mit Asterisks
- Sanitization bewahrt existierenden Wert bei Placeholder-Submit
- Nie in Klartext ausgegeben

**Input-Validierung:**
- Alle Inputs: `sanitize_text_field()`
- Numerische Ranges mit Fallback auf Defaults
- Geo-Koordinaten: validiert gegen Realwelt-Ranges
- Admin-Capability: `manage_options`

**Output-Escaping:**
- User-facing: `esc_html()` / `esc_attr()`
- SVG-Icons: hardcoded, nicht user-steuerbar

## WordPress-Hooks

**Admin:**
- `admin_menu` - Settings-Seite registrieren
- `admin_init` - Settings registrieren
- `admin_post_se_clear_cache` - Cache-Lösch-Action
- `admin_notices` - Success-Meldungen

**Frontend:**
- `wp_enqueue_scripts` - CSS laden (Priority: 999, nur bei Shortcode)

**Activation:**
- `register_activation_hook` - Auto-Migration + Defaults

## Wichtige Funktionen

**Wetterdaten:**
```php
$forecast_data = site_enhancer_get_forecast_data();
if (is_wp_error($forecast_data)) { /* handle */ }
```

**Aktuelles Wetter extrahieren:**
```php
$current = site_enhancer_get_current($forecast_data);
// Liefert: temp, temp_min, temp_max, description, main, icon, dt
```

**Vorhersage-Tage:**
```php
$next_days = site_enhancer_get_next_days($forecast_data);
// Array, Anzahl basierend auf forecast_days Option
```

**Ortsnamen-Logik:**
```php
$location = site_enhancer_get_location_name($forecast_data, $custom_city);
// Priorität: $custom_city → location_name Option → API city → "Unbekannter Ort"
```

## Migration von Legacy-Plugins

**Automatische Migration bei Aktivierung:**
- Prüft auf `wetter_vorhersage_options`
- Übernimmt alle Einstellungen nach `site_enhancer_options`
- Alte Shortcodes manuell ersetzen: `[wetter_vorhersage]` → `[site_weather]`

## Entwicklungshinweise

**Sprache:** Alle Kommentare und UI-Texte auf Deutsch.

**WordPress-Standards:**
- Transients API für Caching
- Options API für Settings
- Shortcode API mit `shortcode_atts()`
- `wp_remote_get()` für HTTP-Requests

**Code-Stil:**
- PHP 7.0+ Kompatibilität
- Funktionale Struktur (keine Klassen)
- Descriptive function names mit Prefix `site_enhancer_`
- Output buffering für HTML-Rendering

**Testing:**
1. Plugin aktivieren: WordPress Admin → Plugins
2. API-Key konfigurieren: Einstellungen → Site Enhancer
3. Shortcode testen: In Post/Page oder via `do_shortcode('[site_weather]')`

## Version Information

- **Version:** 1.0.0
- **WordPress:** 5.0+
- **PHP:** 7.0+
- **Lizenz:** GPL v2 or later
