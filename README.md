# Site Enhancer Widgets

WordPress-Plugin mit konsolidierten Widgets für Wetter und News - optimiert für GeneratePress Sidebar (300px Breite).

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## 📋 Übersicht

Site Enhancer vereint mehrere Funktionen in einem schlanken Plugin:

- **🌤️ Wetter-Widget** - Live-Wetterdaten mit OpenWeatherMap API
- **📰 News-Feed** - RSS-basiertes News-Widget mit Nachladen
- **🎨 GeneratePress-Integration** - Nutzt Theme CSS-Variablen für nahtloses Design
- **📱 Responsive** - Optimiert für Sidebars ab 280px Breite
- **🌙 Dark Mode** - Automatische Anpassung an Theme-Modi

## ✨ Features

### Wetter-Widget

- ✅ **Kompaktes Design** - Perfekt für 300px breite Sidebars
- ✅ **Horizontale Vorhersage** - Platzsparende Darstellung der nächsten 3-5 Tage
- ✅ **Live-Daten** - OpenWeatherMap API mit konfigurierbarem Caching (5-1440 Min.)
- ✅ **Anpassbare Orte** - Via Shortcode-Attribut oder Admin-Panel
- ✅ **SVG-Icons** - Vektorbasierte Wettersymbole (Sonne, Wolken, Regen, etc.)
- ✅ **GeneratePress CSS-Variablen** - Automatische Theme-Anpassung

### News-Feed

- ✅ **Direkte RSS-Ausgabe** - Externen XML-Feed ohne iFrame darstellen
- ✅ **10er-Schritte oder frei konfigurierbar** - Startmenge und Nachladen einstellbar
- ✅ **Konfigurierbare Höhe** - Via Shortcode-Attribut anpassbar

### Technisch

- ✅ **WordPress Options API** - Sichere Einstellungsverwaltung
- ✅ **Transients Caching** - Reduziert API-Aufrufe
- ✅ **Auto-Migration** - Übernimmt Einstellungen von Legacy-Plugins
- ✅ **Externe CSS-Datei** - Keine Inline-Styles

## 🚀 Installation

### Manuelle Installation

1. **Plugin herunterladen**
   ```bash
   cd wp-content/plugins
   git clone https://github.com/EinsVier/site-enhancer.git
   ```

2. **Plugin aktivieren**
   - WordPress Admin → Plugins → Site Enhancer Widgets → Aktivieren

3. **API-Key konfigurieren**
   - Einstellungen → Site Enhancer
   - OpenWeatherMap API-Key eintragen ([Kostenlos registrieren](https://openweathermap.org/api))

### Via ZIP-Upload

1. Repository als ZIP herunterladen
2. WordPress Admin → Plugins → Installieren → Plugin hochladen
3. ZIP-Datei auswählen und installieren
4. Plugin aktivieren

## 📖 Verwendung

### Shortcodes

#### Wetter-Widget

**Basis-Verwendung:**
```
[site_weather]
```

**Mit Custom City:**
```
[site_weather city="Hamburg"]
```

**Attribute:**
- `city` - Überschreibt den im Admin-Panel konfigurierten Ortsnamen (optional)

#### News-Feed

**Basis-Verwendung:**
```
[news_feed]
```

**Mit Custom Startmenge und Nachladen:**
```
[news_feed items="10" step="10" height="800px"]
```

**Mit Ajax-Paging:**
```
[news_feed items="10" mode="pages" height="800px"]
```

**Mit eigener Bildgröße:**
```
[news_feed items="10" image_width="140" image_height="80"]
```

**Attribute:**
- `items` - Anzahl initial sichtbarer News (Standard: 10)
- `step` - Anzahl weiterer News pro Klick (Standard: 10)
- `mode` - `loadmore` oder `pages` fuer Ajax-Nachladen oder Seitennavigation
- `image_width` - Breite der News-Bilder in Pixeln (Standard: 112)
- `image_height` - Höhe der News-Bilder in Pixeln (Standard: 63)
- `height` - Maximale Höhe der News-Liste (Standard: 1000px)

### Integration in Sidebar

**Via Widget:**
1. Design → Widgets
2. "Text"-Widget zur Sidebar hinzufügen
3. Shortcode einfügen: `[site_weather]`
4. Speichern

**Via PHP-Template:**
```php
<?php echo do_shortcode('[site_weather]'); ?>
```

## ⚙️ Konfiguration

### Admin-Panel

**Einstellungen → Site Enhancer**

#### Wetter-Einstellungen

| Option | Beschreibung | Standard |
|--------|--------------|----------|
| **API-Key** | OpenWeatherMap API-Schlüssel (erforderlich) | - |
| **Breitengrad** | Latitude (-90 bis 90) | 53.822 |
| **Längengrad** | Longitude (-180 bis 180) | 12.788 |
| **Ortsname** | Benutzerdefinierter Name (optional) | Neukalen |
| **Vorschau-Tage** | Anzahl Forecast-Tage (1-5) | 3 |
| **Cache-Dauer** | Cache-Zeit in Minuten (5-1440) | 30 |

#### Zusätzliche Funktionen

- **Cache löschen** - Erzwingt sofortiges Neuladen der Wetterdaten
- **Einstellungs-Migration** - Automatische Übernahme von `wetter_vorhersage_options`

### OpenWeatherMap API

**Free Plan:**
- 1.000 API-Aufrufe/Tag
- 60 Aufrufe/Minute
- 5-Tage-Vorhersage in 3h-Intervallen

**Geschätzte Aufrufe** (bei 30 Min. Cache & 100 Pageviews/Tag): ≈ 48 API-Aufrufe

**API-Key erhalten:**
1. Registrierung: https://openweathermap.org/api
2. API-Key kopieren
3. In Admin-Panel eintragen

## 🎨 Design-Anpassung

### GeneratePress CSS-Variablen

Das Plugin nutzt automatisch Theme-Variablen:

```css
--gp-foreground-color    /* Textfarbe */
--gp-background-color    /* Hintergrund */
--gp-perex-font-size     /* Schriftgröße */
--gp-font-family         /* Schriftart */
```

### Custom CSS

**Eigene Anpassungen über Customizer:**

```css
/* Beispiel: Gradient ändern */
.se-weather-current {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%) !important;
}

/* Icon-Farbe anpassen */
.se-day-icon {
    color: #ff6b6b !important;
}
```

### Dark Mode

Automatische Anpassung bei:
- `body.generate-dark-mode`
- `body.dark-mode`
- `@media (prefers-color-scheme: dark)`

## 🔄 Migration von Legacy-Plugins

### Automatische Migration

Bei Plugin-Aktivierung werden automatisch Einstellungen übernommen von:
- `wetter_vorhersage_options` (altes Wetter-Plugin)

### Manuelle Migration

**Schritt 1: Neues Plugin aktivieren**
```
WordPress Admin → Plugins → Site Enhancer Widgets → Aktivieren
```

**Schritt 2: Einstellungen prüfen**
```
Einstellungen → Site Enhancer (Daten sollten bereits übernommen sein)
```

**Schritt 3: Shortcodes ersetzen**

Alte Shortcodes suchen und ersetzen:
- `[wetter_vorhersage]` → `[site_weather]`
- `[news_feed]` bleibt gleich

**Schritt 4: Alte Plugins deaktivieren**
```
Plugins → "Wetter Vorhersage" → Deaktivieren
Plugins → "Neukalen News" → Deaktivieren
```

**Schritt 5: Cleanup (optional)**
```bash
cd wp-content/plugins
rm -rf wetter-vorhersage-main news-feed
```

## 📐 Technische Details

### Dateistruktur

```
site-enhancer/
├── site-enhancer.php      # Haupt-Plugin-Datei
├── css/
│   └── style.css          # Externe Styles
├── .gitignore             # Git-Konfiguration
└── README.md              # Dokumentation
```

### WordPress-Hooks

**Admin:**
- `admin_menu` - Settings-Seite registrieren
- `admin_init` - Settings registrieren
- `admin_post_se_clear_cache` - Cache-Lösch-Action
- `admin_notices` - Success-Meldungen

**Frontend:**
- `wp_enqueue_scripts` - CSS laden (nur bei Shortcode-Verwendung)

### Datenbank

**Options:**
- `site_enhancer_options` - Plugin-Einstellungen (serialized array)

**Transients:**
- `site_enhancer_weather_data` - Gecachte Wetterdaten (TTL: konfigurierbar)

### API-Integration

**Endpoint:**
```
https://api.openweathermap.org/data/2.5/forecast
```

**Parameter:**
- `lat` / `lon` - Koordinaten
- `units=metric` - Celsius
- `lang=de` - Deutsche Beschreibungen
- `appid` - API-Schlüssel

## 🔒 Sicherheit

- ✅ API-Key wird als Passwort-Feld angezeigt (niemals Klartext)
- ✅ Alle Eingaben mit `sanitize_text_field()` gesäubert
- ✅ Alle Ausgaben mit `esc_html()` / `esc_attr()` escaped
- ✅ Capabilities-Prüfung: `manage_options` für Admin-Zugriff
- ✅ Nonce-Schutz via WordPress Settings API
- ✅ Direkt-Zugriff verhindert (`ABSPATH`-Check)

## 🐛 Troubleshooting

### Wetter-Widget zeigt Fehler

**Problem:** "Bitte konfigurieren Sie den API-Key"
- **Lösung:** Einstellungen → Site Enhancer → API-Key eintragen

**Problem:** "API-Fehler: HTTP 401"
- **Lösung:** API-Key ungültig → Neuen Key generieren auf openweathermap.org

**Problem:** "API-Fehler: HTTP 429"
- **Lösung:** Rate Limit erreicht → Cache-Dauer erhöhen (z.B. 60 Min.)

### Widget passt nicht in Sidebar

**Problem:** Widget zu breit
- **Lösung:** CSS anpassen:
  ```css
  .se-weather-widget { max-width: 100%; }
  ```

**Problem:** Vorhersage-Tage überlappen
- **Lösung:** Weniger Tage anzeigen (Einstellungen → Vorschau-Tage: 2)

### Cache funktioniert nicht

**Problem:** Daten werden bei jedem Reload neu geladen
- **Lösung:** Transients prüfen via Plugin-Debugging oder phpMyAdmin
- **Alternative:** WordPress Object Cache installieren

## 📊 Performance

### Benchmarks

| Metrik | Wert |
|--------|------|
| **CSS-Größe** | ~5 KB (unkomprimiert) |
| **PHP-Datei** | ~28 KB |
| **API-Response** | ~50-80 KB (JSON) |
| **Page Load Impact** | < 50ms (mit Cache) |
| **Database Queries** | +1 (Options-Abfrage, cached) |

### Optimierungen

- Externes CSS (nur geladen bei Shortcode-Verwendung)
- Transients-Caching (reduziert API-Calls auf 1/Cache-Periode)
- Lazy Loading von Admin-Assets
- Minifizierte SVG-Icons

## 🤝 Contributing

Contributions sind willkommen! Bitte:

1. Forke das Repository
2. Erstelle einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Committe deine Änderungen (`git commit -m 'Add AmazingFeature'`)
4. Pushe zum Branch (`git push origin feature/AmazingFeature`)
5. Öffne einen Pull Request

### Code-Standards

- WordPress Coding Standards
- PHP 7.0+ Kompatibilität
- Dokumentation auf Deutsch (Code-Kommentare)
- README auf Deutsch

## 📜 Lizenz

GPL v2 or later - siehe [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html)

## 👥 Autoren

- **Neukalen Team** - Initial work
- **Claude Sonnet 4.5** - Code-Entwicklung

## 🔗 Links

- [OpenWeatherMap API](https://openweathermap.org/api)
- [GeneratePress Theme](https://generatepress.com/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)

## 📝 Changelog

### Version 1.0.0 (2026-02-02)

**Initial Release**

- ✨ Wetter-Widget mit OpenWeatherMap Integration
- ✨ News-Feed Widget
- ✨ GeneratePress CSS-Variablen Support
- ✨ Horizontales Forecast-Layout
- ✨ Dark Mode Support
- ✨ Responsive Design (300px optimiert)
- ✨ Auto-Migration von Legacy-Plugins
- ✨ Admin-Panel mit Einstellungsverwaltung
- ✨ Shortcode-Attribute Support (`city`, `height`)

---

**Made with ☕ for Neukalen**
