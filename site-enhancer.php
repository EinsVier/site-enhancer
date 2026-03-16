<?php
/**
 * Plugin Name: Neukalen Site Enhancer
 * Plugin URI: https://neukalen.de
 * Description: Konsolidierte Widgets für Wetter und News - optimiert für GeneratePress Sidebar (300px)
 * Version: 1.0.0
 * Author: Neukalen Team
 * License: GPL v2 or later
 * Text Domain: site-enhancer
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten
define('SITE_ENHANCER_VERSION', '1.0.0');

// Site Design & CSS-Einstellungen
require_once plugin_dir_path(__FILE__) . 'neukalen-site-styles.php';
define('SITE_ENHANCER_PATH', plugin_dir_path(__FILE__));
define('SITE_ENHANCER_URL', plugin_dir_url(__FILE__));

/**
 * ============================================================================
 * ADMIN-EINSTELLUNGEN & OPTIONS API
 * ============================================================================
 */

/**
 * Standard-Einstellungen definieren
 */
function site_enhancer_get_default_options() {
    return array(
        // API & Standort
        'api_key' => '',
        'latitude' => '53.822',
        'longitude' => '12.788',
        'location_name' => 'Neukalen',
        'forecast_days' => 3,
        'cache_duration' => 30,

        // Widget-Anzeige
        'show_footer' => true,

        // News-Feed
        'news_feed_url'           => 'https://baltsch.de/news/relativ_neukalen-news-feed.xml',
        'news_feed_height'        => '1000px',
        'news_feed_initial_count' => 10,
        'news_feed_load_count'    => 10,
        'news_feed_image_width'   => 112,
        'news_feed_image_height'  => 63,

        // Veranstaltungs-Anzeige
        'event_meta_color'      => '#e67e22',
        'event_meta_bold'       => true,
        'event_meta_in_excerpt' => true,
        'event_meta_in_single'  => true,

        // Copyright-Footer
        'copyright_text' => 'Copyright © ' . date('Y') . ' Peenestadt Neukalen.de',
        'datenschutz_link' => '/datenschutz/',
        'datenschutz_text' => 'Datenschutz',
        'impressum_link' => '/impressum/',
        'impressum_text' => 'Impressum',
    );
}

/**
 * Plugin-Option mit Fallback auf Default-Werte abrufen
 */
function site_enhancer_get_option($key) {
    $options = get_option('site_enhancer_options', array());
    $defaults = site_enhancer_get_default_options();

    if (isset($options[$key])) {
        return $options[$key];
    }

    return isset($defaults[$key]) ? $defaults[$key] : null;
}

/**
 * Admin-Menü registrieren
 */
function site_enhancer_admin_menu() {
    add_options_page(
        'Site Enhancer Einstellungen',
        'Site Enhancer',
        'manage_options',
        'site-enhancer',
        'site_enhancer_render_settings_page'
    );
}
add_action('admin_menu', 'site_enhancer_admin_menu');

/**
 * Settings registrieren
 */
function site_enhancer_register_settings() {
    register_setting(
        'site_enhancer_options_group',
        'site_enhancer_options',
        'site_enhancer_sanitize_options'
    );
}
add_action('admin_init', 'site_enhancer_register_settings');

/**
 * Eingaben validieren und sanitieren
 */
function site_enhancer_sanitize_options($input) {
    // Defaults + bestehende Werte als Basis – so sind alle Felder immer vorhanden
    $old      = get_option('site_enhancer_options', array());
    $defaults = site_enhancer_get_default_options();
    $section  = isset($input['_section']) ? $input['_section'] : 'all';

    // Defaults zuerst, dann vorhandene DB-Werte überschreiben → alle Keys immer vorhanden
    $sanitized = array_merge($defaults, $old);

    if ($section === 'weather' || $section === 'all') {
        // API-Key (Sternchen = unveränderter Wert)
        if (isset($input['api_key'])) {
            $api_key = sanitize_text_field($input['api_key']);
            $sanitized['api_key'] = ($api_key !== '' && !preg_match('/^\*+$/', $api_key))
                ? $api_key
                : ($old['api_key'] ?? '');
        }
        if (isset($input['latitude'])) {
            $lat = floatval($input['latitude']);
            $sanitized['latitude'] = ($lat >= -90 && $lat <= 90) ? $lat : $defaults['latitude'];
        }
        if (isset($input['longitude'])) {
            $lon = floatval($input['longitude']);
            $sanitized['longitude'] = ($lon >= -180 && $lon <= 180) ? $lon : $defaults['longitude'];
        }
        if (isset($input['location_name'])) {
            $sanitized['location_name'] = sanitize_text_field($input['location_name']);
        }
        if (isset($input['forecast_days'])) {
            $days = intval($input['forecast_days']);
            $sanitized['forecast_days'] = ($days >= 1 && $days <= 5) ? $days : $defaults['forecast_days'];
        }
        if (isset($input['cache_duration'])) {
            $duration = intval($input['cache_duration']);
            $sanitized['cache_duration'] = ($duration >= 5 && $duration <= 1440) ? $duration : $defaults['cache_duration'];
        }
        // Checkbox: nur setzen wenn wir explizit in der weather-Sektion sind
        if ($section === 'weather') {
            $sanitized['show_footer'] = isset($input['show_footer']);
        }
    }

    if ($section === 'news_feed' || $section === 'all') {
        if (isset($input['news_feed_url'])) {
            $sanitized['news_feed_url'] = esc_url_raw(trim($input['news_feed_url']));
        }
        if (isset($input['news_feed_height'])) {
            $h = sanitize_text_field($input['news_feed_height']);
            $sanitized['news_feed_height'] = preg_match('/^\d+(px|%)$/', $h) ? $h : $defaults['news_feed_height'];
        }
        if (isset($input['news_feed_initial_count'])) {
            $initial_count = intval($input['news_feed_initial_count']);
            $sanitized['news_feed_initial_count'] = ($initial_count >= 1 && $initial_count <= 50)
                ? $initial_count
                : $defaults['news_feed_initial_count'];
        }
        if (isset($input['news_feed_load_count'])) {
            $load_count = intval($input['news_feed_load_count']);
            $sanitized['news_feed_load_count'] = ($load_count >= 1 && $load_count <= 50)
                ? $load_count
                : $defaults['news_feed_load_count'];
        }
        if (isset($input['news_feed_image_width'])) {
            $image_width = intval($input['news_feed_image_width']);
            $sanitized['news_feed_image_width'] = ($image_width >= 40 && $image_width <= 400)
                ? $image_width
                : $defaults['news_feed_image_width'];
        }
        if (isset($input['news_feed_image_height'])) {
            $image_height = intval($input['news_feed_image_height']);
            $sanitized['news_feed_image_height'] = ($image_height >= 24 && $image_height <= 300)
                ? $image_height
                : $defaults['news_feed_image_height'];
        }
    }

    if ($section === 'events' || $section === 'all') {
        if (isset($input['event_meta_color'])) {
            $color = sanitize_text_field($input['event_meta_color']);
            $sanitized['event_meta_color'] = preg_match('/^#[0-9a-fA-F]{3,6}$/', $color) ? $color : $defaults['event_meta_color'];
        }
        // Checkboxen: nur setzen wenn wir explizit in der events-Sektion sind
        if ($section === 'events') {
            $sanitized['event_meta_bold']       = isset($input['event_meta_bold']);
            $sanitized['event_meta_in_excerpt'] = isset($input['event_meta_in_excerpt']);
            $sanitized['event_meta_in_single']  = isset($input['event_meta_in_single']);
        }
    }

    if ($section === 'copyright' || $section === 'all') {
        if (isset($input['copyright_text'])) {
            $sanitized['copyright_text'] = sanitize_text_field($input['copyright_text']);
        }
        if (isset($input['datenschutz_link'])) {
            $sanitized['datenschutz_link'] = sanitize_text_field($input['datenschutz_link']);
        }
        if (isset($input['datenschutz_text'])) {
            $sanitized['datenschutz_text'] = sanitize_text_field($input['datenschutz_text']);
        }
        if (isset($input['impressum_link'])) {
            $sanitized['impressum_link'] = sanitize_text_field($input['impressum_link']);
        }
        if (isset($input['impressum_text'])) {
            $sanitized['impressum_text'] = sanitize_text_field($input['impressum_text']);
        }
    }

    add_settings_error(
        'site_enhancer_options',
        'settings_updated',
        'Einstellungen erfolgreich gespeichert.',
        'success'
    );

    return $sanitized;
}

/**
 * Einstellungsseite rendern
 */
function site_enhancer_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Sie haben keine ausreichenden Berechtigungen für diese Seite.');
    }

    $options = get_option('site_enhancer_options', site_enhancer_get_default_options());
    $api_key = isset($options['api_key']) ? $options['api_key'] : '';
    $has_api_key = !empty($api_key);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php if (!$has_api_key): ?>
            <div class="notice notice-warning">
                <p>
                    <strong>Kein API-Key konfiguriert!</strong><br>
                    Das Wetter-Widget benötigt einen gültigen OpenWeatherMap API-Key.<br>
                    <a href="https://openweathermap.org/api" target="_blank">Hier kostenlos registrieren</a>
                </p>
            </div>
        <?php endif; ?>

        <?php settings_errors('site_enhancer_options'); ?>

        <h2>Wetter-Widget Einstellungen</h2>
        <form method="post" action="options.php">
            <?php settings_fields('site_enhancer_options_group'); ?>
            <input type="hidden" name="site_enhancer_options[_section]" value="weather">

            <table class="form-table">
                <tr>
                    <th><label for="se_api_key">OpenWeatherMap API-Key *</label></th>
                    <td>
                        <input type="password" id="se_api_key"
                               name="site_enhancer_options[api_key]"
                               value="<?php echo $has_api_key ? '********************' : ''; ?>"
                               class="regular-text" placeholder="Ihren API-Key hier eintragen" />
                        <p class="description">
                            <a href="https://openweathermap.org/api" target="_blank">Kostenlosen API-Key erhalten</a>
                            <?php if ($has_api_key): ?>
                                <br><span style="color: green;">✓ API-Key ist gesetzt</span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_latitude">Breitengrad *</label></th>
                    <td>
                        <input type="text" id="se_latitude"
                               name="site_enhancer_options[latitude]"
                               value="<?php echo esc_attr($options['latitude']); ?>"
                               class="regular-text" placeholder="z.B. 53.822" />
                        <p class="description">Zwischen -90 und 90</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_longitude">Längengrad *</label></th>
                    <td>
                        <input type="text" id="se_longitude"
                               name="site_enhancer_options[longitude]"
                               value="<?php echo esc_attr($options['longitude']); ?>"
                               class="regular-text" placeholder="z.B. 12.788" />
                        <p class="description">Zwischen -180 und 180</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_location">Ortsname (optional)</label></th>
                    <td>
                        <input type="text" id="se_location"
                               name="site_enhancer_options[location_name]"
                               value="<?php echo esc_attr($options['location_name']); ?>"
                               class="regular-text" placeholder="z.B. Neukalen" />
                        <p class="description">Standard: Neukalen</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_days">Anzahl Vorschau-Tage</label></th>
                    <td>
                        <input type="number" id="se_days"
                               name="site_enhancer_options[forecast_days]"
                               value="<?php echo esc_attr($options['forecast_days']); ?>"
                               min="1" max="5" class="small-text" />
                        <p class="description">1-5 Tage (Standard: 3)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_cache">Cache-Dauer (Minuten)</label></th>
                    <td>
                        <input type="number" id="se_cache"
                               name="site_enhancer_options[cache_duration]"
                               value="<?php echo esc_attr($options['cache_duration']); ?>"
                               min="5" max="1440" step="5" class="small-text" />
                        <p class="description">5-1440 Minuten (Standard: 30)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_show_footer">Widget-Footer anzeigen</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="se_show_footer"
                                   name="site_enhancer_options[show_footer]"
                                   value="1" <?php checked($options['show_footer'], true); ?> />
                            OpenWeatherMap Attribution und Uhrzeit im Widget-Footer anzeigen
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button('Einstellungen speichern'); ?>
        </form>

        <hr>
        <h2>News-Feed Einstellungen</h2>
        <form method="post" action="options.php">
            <?php settings_fields('site_enhancer_options_group'); ?>
            <input type="hidden" name="site_enhancer_options[_section]" value="news_feed">

            <table class="form-table">
                <tr>
                    <th><label for="se_news_feed_url">Feed-URL</label></th>
                    <td>
                        <input type="url" id="se_news_feed_url"
                               name="site_enhancer_options[news_feed_url]"
                               value="<?php echo esc_attr($options['news_feed_url'] ?? 'https://baltsch.de/news/relativ_neukalen-news-feed.xml'); ?>"
                               class="large-text"
                               placeholder="https://example.com/news-feed.xml" />
                        <p class="description">URL des RSS- oder XML-Feeds, den das Widget direkt ausliest.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_news_feed_initial_count">Einträge beim Start</label></th>
                    <td>
                        <input type="number" id="se_news_feed_initial_count"
                               name="site_enhancer_options[news_feed_initial_count]"
                               value="<?php echo esc_attr($options['news_feed_initial_count'] ?? 10); ?>"
                               min="1" max="50" class="small-text" />
                        <p class="description">Wie viele News zuerst angezeigt werden.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_news_feed_load_count">Weitere pro Klick</label></th>
                    <td>
                        <input type="number" id="se_news_feed_load_count"
                               name="site_enhancer_options[news_feed_load_count]"
                               value="<?php echo esc_attr($options['news_feed_load_count'] ?? 10); ?>"
                               min="1" max="50" class="small-text" />
                        <p class="description">Wie viele zusätzliche News mit "Weitere News laden" sichtbar werden.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_news_feed_height">Höhe</label></th>
                    <td>
                        <input type="text" id="se_news_feed_height"
                               name="site_enhancer_options[news_feed_height]"
                               value="<?php echo esc_attr($options['news_feed_height'] ?? '1000px'); ?>"
                               class="small-text"
                               placeholder="1000px" />
                        <p class="description">Maximale Höhe der News-Liste, z.B. <code>800px</code> oder <code>100%</code>. Shortcode-Attribut <code>height="…"</code> hat Vorrang.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_news_feed_image_width">Bildbreite</label></th>
                    <td>
                        <input type="number" id="se_news_feed_image_width"
                               name="site_enhancer_options[news_feed_image_width]"
                               value="<?php echo esc_attr($options['news_feed_image_width'] ?? 112); ?>"
                               min="40" max="400" class="small-text" /> px
                        <p class="description">Breite der Vorschaubilder im News-Widget.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_news_feed_image_height">Bildhöhe</label></th>
                    <td>
                        <input type="number" id="se_news_feed_image_height"
                               name="site_enhancer_options[news_feed_image_height]"
                               value="<?php echo esc_attr($options['news_feed_image_height'] ?? 63); ?>"
                               min="24" max="300" class="small-text" /> px
                        <p class="description">Höhe der Vorschaubilder im News-Widget.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Einstellungen speichern'); ?>
        </form>

        <hr>
        <h2>Veranstaltungs-Anzeige</h2>
        <p class="description" style="margin-bottom:12px;">
            Datum, Uhrzeit und Ort aus den Veranstaltungs-Feldern automatisch in Beiträgen anzeigen.
        </p>
        <form method="post" action="options.php">
            <?php settings_fields('site_enhancer_options_group'); ?>
            <input type="hidden" name="site_enhancer_options[_section]" value="events">

            <table class="form-table">
                <tr>
                    <th><label for="se_event_color">Akzentfarbe</label></th>
                    <td>
                        <input type="color" id="se_event_color"
                               name="site_enhancer_options[event_meta_color]"
                               value="<?php echo esc_attr($options['event_meta_color'] ?? '#e67e22'); ?>"
                               style="width:50px;height:34px;padding:2px;cursor:pointer;">
                        <input type="text"
                               name="site_enhancer_options[event_meta_color]"
                               id="se_event_color_hex"
                               value="<?php echo esc_attr($options['event_meta_color'] ?? '#e67e22'); ?>"
                               style="width:90px;vertical-align:middle;"
                               placeholder="#e67e22">
                        <p class="description">Farbe für den linken Rand und Hintergrundton. Vorschläge:
                            <code>#e67e22</code> Orange ·
                            <code>#2ecc71</code> Grün ·
                            <code>#3498db</code> Blau ·
                            <code>#e74c3c</code> Rot
                        </p>
                        <script>
                        document.getElementById('se_event_color').addEventListener('input', function(){
                            document.getElementById('se_event_color_hex').value = this.value;
                        });
                        document.getElementById('se_event_color_hex').addEventListener('input', function(){
                            if (/^#[0-9a-fA-F]{6}$/.test(this.value))
                                document.getElementById('se_event_color').value = this.value;
                        });
                        </script>
                    </td>
                </tr>
                <tr>
                    <th>Stil</th>
                    <td>
                        <label>
                            <input type="checkbox" name="site_enhancer_options[event_meta_bold]"
                                   value="1" <?php checked($options['event_meta_bold'] ?? true, true); ?>>
                            Fettschrift für Datum/Uhrzeit/Ort
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Anzeige in</th>
                    <td>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="checkbox" name="site_enhancer_options[event_meta_in_excerpt]"
                                   value="1" <?php checked($options['event_meta_in_excerpt'] ?? true, true); ?>>
                            Beitragslisten / Blöcke (Neueste Beiträge, Abfrage-Loop)
                        </label>
                        <label>
                            <input type="checkbox" name="site_enhancer_options[event_meta_in_single]"
                                   value="1" <?php checked($options['event_meta_in_single'] ?? true, true); ?>>
                            Einzelbeitrag (oben im Inhalt)
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button('Einstellungen speichern'); ?>
        </form>

        <hr>
        <h2>Copyright & Footer Einstellungen</h2>
        <form method="post" action="options.php">
            <?php settings_fields('site_enhancer_options_group'); ?>
            <input type="hidden" name="site_enhancer_options[_section]" value="copyright">

            <table class="form-table">
                <tr>
                    <th><label for="se_copyright">Copyright-Text</label></th>
                    <td>
                        <input type="text" id="se_copyright"
                               name="site_enhancer_options[copyright_text]"
                               value="<?php echo esc_attr($options['copyright_text']); ?>"
                               class="regular-text" placeholder="Copyright © <?php echo date('Y'); ?> Peenestadt Neukalen.de" />
                        <p class="description">Der Copyright-Text im Footer. <code>{year}</code> wird automatisch durch das aktuelle Jahr ersetzt.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="se_datenschutz_text">Datenschutz-Link Text</label></th>
                    <td>
                        <input type="text" id="se_datenschutz_text"
                               name="site_enhancer_options[datenschutz_text]"
                               value="<?php echo esc_attr($options['datenschutz_text']); ?>"
                               class="regular-text" placeholder="Datenschutz" />
                    </td>
                </tr>
                <tr>
                    <th><label for="se_datenschutz_link">Datenschutz-Link URL</label></th>
                    <td>
                        <input type="text" id="se_datenschutz_link"
                               name="site_enhancer_options[datenschutz_link]"
                               value="<?php echo esc_attr($options['datenschutz_link']); ?>"
                               class="regular-text" placeholder="/datenschutz/" />
                    </td>
                </tr>
                <tr>
                    <th><label for="se_impressum_text">Impressum-Link Text</label></th>
                    <td>
                        <input type="text" id="se_impressum_text"
                               name="site_enhancer_options[impressum_text]"
                               value="<?php echo esc_attr($options['impressum_text']); ?>"
                               class="regular-text" placeholder="Impressum" />
                    </td>
                </tr>
                <tr>
                    <th><label for="se_impressum_link">Impressum-Link URL</label></th>
                    <td>
                        <input type="text" id="se_impressum_link"
                               name="site_enhancer_options[impressum_link]"
                               value="<?php echo esc_attr($options['impressum_link']); ?>"
                               class="regular-text" placeholder="/impressum/" />
                    </td>
                </tr>
            </table>

            <?php submit_button('Einstellungen speichern'); ?>
        </form>

        <hr>
        <h2>Shortcodes</h2>
        <table class="widefat" style="max-width: 800px;">
            <tbody>
                <tr>
                    <td style="padding: 15px;">
                        <strong>Wetter-Widget:</strong><br>
                        <code>[site_weather]</code> oder <code>[site_weather city="Neukalen"]</code>
                    </td>
                </tr>
                <tr style="background: #f9f9f9;">
                    <td style="padding: 15px;">
                        <strong>News-Feed:</strong><br>
                        <code>[news_feed]</code> oder <code>[news_feed items="10" step="10" mode="pages"]</code>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 15px;">
                        <strong>Cache löschen:</strong><br>
                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=se_clear_cache')); ?>"
                           class="button">Cache jetzt löschen</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * ============================================================================
 * WETTER-WIDGET FUNKTIONEN (OpenWeatherMap Integration)
 * ============================================================================
 */

/**
 * Wetterdaten von OpenWeatherMap abrufen
 */
function site_enhancer_get_forecast_data() {
    $api_key = site_enhancer_get_option('api_key');
    $latitude = site_enhancer_get_option('latitude');
    $longitude = site_enhancer_get_option('longitude');
    $cache_duration = site_enhancer_get_option('cache_duration');

    $cache_seconds = intval($cache_duration) * 60;
    $transient_key = 'site_enhancer_weather_data';

    // Cache prüfen
    $cached_data = get_transient($transient_key);
    if ($cached_data !== false) {
        return $cached_data;
    }

    // API-Anfrage
    $api_url = sprintf(
        'https://api.openweathermap.org/data/2.5/forecast?lat=%s&lon=%s&units=metric&lang=de&appid=%s',
        $latitude,
        $longitude,
        $api_key
    );

    $response = wp_remote_get($api_url, array('timeout' => 15, 'sslverify' => true));

    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'API-Verbindung fehlgeschlagen: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        return new WP_Error('api_error', 'API-Fehler: HTTP ' . $response_code);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['list'])) {
        return new WP_Error('parse_error', 'Ungültige API-Antwort');
    }

    set_transient($transient_key, $data, $cache_seconds);

    return $data;
}

/**
 * Aktuelles Wetter extrahieren
 */
function site_enhancer_get_current($forecast_data) {
    if (!isset($forecast_data['list']) || empty($forecast_data['list'])) {
        return null;
    }

    $first_item = $forecast_data['list'][0];
    $today_date = date('Y-m-d');
    $today_temps = array();

    foreach ($forecast_data['list'] as $item) {
        if (date('Y-m-d', $item['dt']) === $today_date) {
            $today_temps[] = $item['main']['temp_min'];
            $today_temps[] = $item['main']['temp_max'];
        }
    }

    return array(
        'temp' => $first_item['main']['temp'],
        'temp_min' => !empty($today_temps) ? min($today_temps) : $first_item['main']['temp_min'],
        'temp_max' => !empty($today_temps) ? max($today_temps) : $first_item['main']['temp_max'],
        'description' => $first_item['weather'][0]['description'],
        'main' => $first_item['weather'][0]['main'],
        'icon' => $first_item['weather'][0]['icon'],
        'dt' => $first_item['dt']
    );
}

/**
 * Vorhersage für nächste Tage gruppieren
 */
function site_enhancer_get_next_days($forecast_data) {
    if (!isset($forecast_data['list'])) {
        return array();
    }

    $forecast_days = intval(site_enhancer_get_option('forecast_days'));
    $grouped = array();
    $today_date = date('Y-m-d');

    foreach ($forecast_data['list'] as $item) {
        $date = date('Y-m-d', $item['dt']);

        if ($date > $today_date && !isset($grouped[$date]) && count($grouped) < $forecast_days) {
            $grouped[$date] = array(
                'date' => $item['dt'],
                'temp_min' => $item['main']['temp_min'],
                'temp_max' => $item['main']['temp_max'],
                'description' => $item['weather'][0]['description'],
                'icon' => $item['weather'][0]['icon'],
                'main' => $item['weather'][0]['main']
            );
        } elseif (isset($grouped[$date])) {
            $grouped[$date]['temp_min'] = min($grouped[$date]['temp_min'], $item['main']['temp_min']);
            $grouped[$date]['temp_max'] = max($grouped[$date]['temp_max'], $item['main']['temp_max']);
        }
    }

    return $grouped;
}

/**
 * Ortsnamen extrahieren
 */
function site_enhancer_get_location_name($forecast_data, $custom_city = '') {
    if (!empty($custom_city)) {
        return $custom_city;
    }

    $custom_name = site_enhancer_get_option('location_name');
    if (!empty($custom_name)) {
        return $custom_name;
    }

    if (isset($forecast_data['city']['name'])) {
        return $forecast_data['city']['name'];
    }

    return 'Unbekannter Ort';
}

/**
 * SVG-Icon für Wetterbedingungen (kompakte Version)
 */
function site_enhancer_get_icon_svg($weather_main, $icon) {
    $is_night = strpos($icon, 'n') !== false;

    switch ($weather_main) {
        case 'Clear':
            if ($is_night) {
                return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
            }
            return '<svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="0.5"><circle cx="12" cy="12" r="4"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
        case 'Clouds':
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>';
        case 'Rain':
        case 'Drizzle':
            return '<svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"><path fill="currentColor" stroke="none" d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/><line x1="8" y1="19" x2="8" y2="21"/><line x1="11" y1="19" x2="11" y2="21"/><line x1="14" y1="19" x2="14" y2="21"/></svg>';
        case 'Snow':
            return '<svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><path fill="currentColor" stroke="none" d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/><circle cx="8" cy="19" r="0.5"/><circle cx="11" cy="19" r="0.5"/><circle cx="14" cy="19" r="0.5"/></svg>';
        case 'Thunderstorm':
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/><path fill="#FFD700" d="M14 13h-3l2 5v-3h2l-2-5v3z"/></svg>';
        case 'Mist':
        case 'Fog':
        case 'Haze':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="13" x2="21" y2="13"/><line x1="3" y1="17" x2="21" y2="17"/></svg>';
        default:
            return '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>';
    }
}

/**
 * ============================================================================
 * SHORTCODE HANDLER
 * ============================================================================
 */

/**
 * News-Feed URL auf absolut auflösen
 */
function site_enhancer_news_resolve_url($url, $feed_url) {
    $url = trim((string) $url);

    if ($url === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $feed_parts = wp_parse_url($feed_url);
    if (empty($feed_parts['host']) || empty($feed_parts['scheme'])) {
        return $url;
    }

    if (strpos($url, '//') === 0) {
        return $feed_parts['scheme'] . ':' . $url;
    }

    $base = $feed_parts['scheme'] . '://' . $feed_parts['host'];
    if (!empty($feed_parts['port'])) {
        $base .= ':' . $feed_parts['port'];
    }

    if (strpos($url, '/') === 0) {
        return $base . $url;
    }

    $path = !empty($feed_parts['path']) ? dirname($feed_parts['path']) : '';
    if ($path === '.' || $path === DIRECTORY_SEPARATOR) {
        $path = '';
    }

    return rtrim($base . '/' . ltrim($path, '/'), '/') . '/' . ltrim($url, '/');
}

/**
 * Bild-URL aus einem RSS-Item extrahieren
 */
function site_enhancer_news_extract_image_url($item, $feed_url) {
    if (isset($item->enclosure)) {
        foreach ($item->enclosure as $enclosure) {
            $attributes = $enclosure->attributes();
            if (!empty($attributes['url'])) {
                return site_enhancer_news_resolve_url((string) $attributes['url'], $feed_url);
            }
        }
    }

    $namespaces = $item->getNameSpaces(true);
    if (!empty($namespaces['content'])) {
        $content = $item->children($namespaces['content']);
        if (!empty($content->encoded)) {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $content->encoded, $matches)) {
                return site_enhancer_news_resolve_url($matches[1], $feed_url);
            }
        }
    }

    return '';
}

/**
 * HTML-Inhalt aus content:encoded abrufen
 */
function site_enhancer_news_get_encoded_content($item) {
    $namespaces = $item->getNameSpaces(true);
    if (empty($namespaces['content'])) {
        return '';
    }

    $content = $item->children($namespaces['content']);
    if (empty($content->encoded)) {
        return '';
    }

    return html_entity_decode((string) $content->encoded, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * Bild-URL aus einem img-Tag mit bestimmter CSS-Klasse extrahieren
 */
function site_enhancer_news_extract_tag_image_url($html, $class_name, $feed_url) {
    if ($html === '') {
        return '';
    }

    if (!preg_match_all('/<img\b[^>]*>/i', $html, $matches)) {
        return '';
    }

    foreach ($matches[0] as $img_tag) {
        if (!preg_match('/\bclass=["\']([^"\']+)["\']/i', $img_tag, $class_matches)) {
            continue;
        }

        $classes = preg_split('/\s+/', trim($class_matches[1]));
        if (!in_array($class_name, $classes, true)) {
            continue;
        }

        if (preg_match('/\bsrc=["\']([^"\']+)["\']/i', $img_tag, $src_matches)) {
            return site_enhancer_news_resolve_url($src_matches[1], $feed_url);
        }
    }

    return '';
}

/**
 * Quell-Icon aus dem RSS-Item extrahieren
 */
function site_enhancer_news_extract_icon_url($item, $feed_url) {
    $encoded_content = site_enhancer_news_get_encoded_content($item);
    return site_enhancer_news_extract_tag_image_url($encoded_content, 'item_icon', $feed_url);
}

/**
 * RSS-Newsfeed laden und in eine einfache Struktur umwandeln
 */
function site_enhancer_get_news_feed_items($feed_url) {
    $feed_url = esc_url_raw(trim((string) $feed_url));

    if ($feed_url === '') {
        return new WP_Error('news_feed_missing_url', 'Es ist keine News-Feed-URL konfiguriert.');
    }

    $cache_key = 'site_enhancer_news_' . md5($feed_url);
    $cached_items = get_transient($cache_key);
    if ($cached_items !== false) {
        return $cached_items;
    }

    $response = wp_remote_get($feed_url, array(
        'timeout' => 15,
        'sslverify' => true,
        'headers' => array(
            'Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
        ),
    ));

    if (is_wp_error($response)) {
        return new WP_Error('news_feed_request_failed', 'News-Feed konnte nicht geladen werden: ' . $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return new WP_Error('news_feed_http_error', 'News-Feed antwortet mit HTTP ' . $status_code . '.');
    }

    $body = wp_remote_retrieve_body($response);
    if ($body === '') {
        return new WP_Error('news_feed_empty', 'Der News-Feed ist leer.');
    }

    $use_internal_errors = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();
    libxml_use_internal_errors($use_internal_errors);

    if ($xml === false || !isset($xml->channel->item)) {
        return new WP_Error('news_feed_parse_error', 'Der News-Feed konnte nicht verarbeitet werden.');
    }

    $items = array();

    foreach ($xml->channel->item as $item) {
        $title = trim(wp_strip_all_tags((string) $item->title));
        $link = esc_url_raw(trim((string) $item->link));

        if ($title === '' || $link === '') {
            continue;
        }

        $items[] = array(
            'title' => $title,
            'link' => $link,
            'description' => trim(wp_strip_all_tags(html_entity_decode((string) $item->description, ENT_QUOTES | ENT_XML1, 'UTF-8'))),
            'pub_date' => trim((string) $item->pubDate),
            'icon_url' => site_enhancer_news_extract_icon_url($item, $feed_url),
            'image_url' => site_enhancer_news_extract_image_url($item, $feed_url),
        );
    }

    set_transient($cache_key, $items, 30 * MINUTE_IN_SECONDS);

    return $items;
}

/**
 * HTML fuer eine Liste von News-Eintraegen rendern
 */
function site_enhancer_render_news_feed_items($items) {
    if (empty($items)) {
        return '';
    }

    ob_start();
    foreach ($items as $item) {
        $timestamp = !empty($item['pub_date']) ? strtotime($item['pub_date']) : false;
        ?>
        <article class="se-news-item">
            <div class="se-news-content">
                <?php if (!empty($item['icon_url'])) : ?>
                    <a class="se-news-source-link" href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer" aria-hidden="true" tabindex="-1">
                        <img class="se-news-source-icon" src="<?php echo esc_url($item['icon_url']); ?>" alt="" loading="lazy">
                    </a>
                <?php endif; ?>
                <?php if (!empty($item['image_url'])) : ?>
                    <a class="se-news-thumb" href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo esc_url($item['image_url']); ?>" alt="" loading="lazy">
                    </a>
                <?php endif; ?>
                <h3 class="se-news-title">
                    <a href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html($item['title']); ?>
                    </a>
                </h3>
                <?php if (!empty($item['description'])) : ?>
                    <p class="se-news-excerpt"><?php echo esc_html($item['description']); ?></p>
                <?php endif; ?>
                <?php if ($timestamp) : ?>
                    <p class="se-news-meta"><?php echo esc_html(date_i18n('j. F Y, H:i \\U\\h\\r', $timestamp)); ?></p>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }

    return ob_get_clean();
}

/**
 * News-Anzeigemodus normalisieren
 */
function site_enhancer_news_get_mode($mode) {
    $mode = strtolower(trim((string) $mode));
    return in_array($mode, array('loadmore', 'pages'), true) ? $mode : 'loadmore';
}

/**
 * Anzahl der News-Seiten berechnen
 */
function site_enhancer_news_get_total_pages($total_items, $per_page) {
    $per_page = max(1, intval($per_page));
    return max(1, (int) ceil(max(0, intval($total_items)) / $per_page));
}

/**
 * Pagination fuer News-Widget rendern
 */
function site_enhancer_render_news_pagination($current_page, $total_pages) {
    $current_page = max(1, intval($current_page));
    $total_pages = max(1, intval($total_pages));

    if ($total_pages <= 1) {
        return '';
    }

    $pages = array(1, $total_pages);
    for ($page = max(1, $current_page - 1); $page <= min($total_pages, $current_page + 1); $page++) {
        $pages[] = $page;
    }

    $pages = array_values(array_unique($pages));
    sort($pages);

    ob_start();
    ?>
    <nav class="se-news-pagination" aria-label="News-Seiten">
        <?php if ($current_page > 1) : ?>
            <button type="button" class="se-news-page se-news-page-nav" data-page="<?php echo esc_attr($current_page - 1); ?>" aria-label="Vorherige Seite">&lsaquo;</button>
        <?php endif; ?>

        <?php
        $previous_page = null;
        foreach ($pages as $page) :
            if ($previous_page !== null && $page - $previous_page > 1) :
                ?>
                <span class="se-news-page-gap" aria-hidden="true">…</span>
                <?php
            endif;
            ?>
            <button type="button"
                    class="se-news-page<?php echo $page === $current_page ? ' is-active' : ''; ?>"
                    data-page="<?php echo esc_attr($page); ?>"
                    <?php echo $page === $current_page ? 'aria-current="page"' : ''; ?>><?php echo esc_html($page); ?></button>
            <?php
            $previous_page = $page;
        endforeach;
        ?>

        <?php if ($current_page < $total_pages) : ?>
            <button type="button" class="se-news-page se-news-page-nav" data-page="<?php echo esc_attr($current_page + 1); ?>" aria-label="Naechste Seite">&rsaquo;</button>
        <?php endif; ?>
    </nav>
    <?php

    return ob_get_clean();
}

/**
 * Ladeplatzhalter fuer News-Widget rendern
 */
function site_enhancer_render_news_loading_skeleton($count = 2) {
    $count = max(1, min(4, intval($count)));

    ob_start();
    ?>
    <div class="se-news-loading" hidden aria-hidden="true">
        <?php for ($index = 0; $index < $count; $index++) : ?>
            <div class="se-news-skeleton-item">
                <div class="se-news-skeleton-image"></div>
                <div class="se-news-skeleton-lines">
                    <span class="se-news-skeleton-line se-news-skeleton-line-title"></span>
                    <span class="se-news-skeleton-line"></span>
                    <span class="se-news-skeleton-line se-news-skeleton-line-short"></span>
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <?php

    return ob_get_clean();
}

/**
 * Text fuer den Nachlade-Button erzeugen
 */
function site_enhancer_news_more_button_label($remaining_count) {
    $remaining_count = max(0, intval($remaining_count));
    if ($remaining_count <= 0) {
        return 'Keine weiteren News';
    }

    return 'Weitere News laden (' . $remaining_count . ' verbleibend)';
}

/**
 * Ajax-Endpunkt fuer serverseitiges Nachladen weiterer News
 */
function site_enhancer_ajax_load_news_feed() {
    check_ajax_referer('site_enhancer_news_feed', 'nonce');

    $feed_url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $mode = isset($_POST['mode']) ? site_enhancer_news_get_mode(wp_unslash($_POST['mode'])) : 'loadmore';

    $offset = max(0, $offset);
    $limit = max(1, min(50, $limit));
    $page = max(1, $page);

    $items = site_enhancer_get_news_feed_items($feed_url);
    if (is_wp_error($items)) {
        wp_send_json_error(array(
            'message' => $items->get_error_message(),
        ), 400);
    }

    if ($mode === 'pages') {
        $total_pages = site_enhancer_news_get_total_pages(count($items), $limit);
        $page = min($page, $total_pages);
        $offset = ($page - 1) * $limit;
        $slice = array_slice($items, $offset, $limit);

        wp_send_json_success(array(
            'mode' => 'pages',
            'html' => site_enhancer_render_news_feed_items($slice),
            'currentPage' => $page,
            'totalPages' => $total_pages,
            'paginationHtml' => site_enhancer_render_news_pagination($page, $total_pages),
        ));
    }

    $slice = array_slice($items, $offset, $limit);
    $next_offset = $offset + count($slice);
    $remaining = max(0, count($items) - $next_offset);

    wp_send_json_success(array(
        'mode' => 'loadmore',
        'html' => site_enhancer_render_news_feed_items($slice),
        'nextOffset' => $next_offset,
        'remaining' => $remaining,
        'hasMore' => $remaining > 0,
        'buttonLabel' => site_enhancer_news_more_button_label($remaining),
    ));
}
add_action('wp_ajax_site_enhancer_load_news_feed', 'site_enhancer_ajax_load_news_feed');
add_action('wp_ajax_nopriv_site_enhancer_load_news_feed', 'site_enhancer_ajax_load_news_feed');

/**
 * Wetter-Widget Shortcode [site_weather]
 */
function site_enhancer_weather_display($atts) {
    $atts = shortcode_atts(array(
        'city' => '',
        'link' => '',   // URL zur internen Wetterseite, z.B. link="/wetter/"
    ), $atts, 'site_weather');

    $api_key = site_enhancer_get_option('api_key');
    if (empty($api_key)) {
        return '<div class="se-error">Bitte konfigurieren Sie den API-Key unter <a href="' .
               esc_url(admin_url('options-general.php?page=site-enhancer')) . '">Einstellungen</a>.</div>';
    }

    $forecast_data = site_enhancer_get_forecast_data();

    if (is_wp_error($forecast_data)) {
        return '<div class="se-error">Fehler: ' . esc_html($forecast_data->get_error_message()) . '</div>';
    }

    $current = site_enhancer_get_current($forecast_data);
    $next_days = site_enhancer_get_next_days($forecast_data);
    $location = site_enhancer_get_location_name($forecast_data, $atts['city']);

    if (!$current) {
        return '<div class="se-error">Keine Wetterdaten verfügbar.</div>';
    }

    ob_start();
    ?>
    <div class="se-weather-compact-wrapper">

        <!-- Mini-Ansicht (immer sichtbar) -->
        <div class="se-weather-mini" tabindex="0" aria-label="Wetter <?php echo esc_attr($location); ?>: <?php echo esc_attr(round($current['temp'])); ?>°, <?php echo esc_attr(ucfirst($current['description'])); ?>">
            <span class="se-mini-icon"><?php echo site_enhancer_get_icon_svg($current['main'], $current['icon']); ?></span>
            <span class="se-mini-loc"><?php echo esc_html($location); ?></span>
            <span class="se-mini-temp"><?php echo esc_html(round($current['temp'])); ?>°</span>
            <span class="se-mini-desc"><?php echo esc_html(ucfirst($current['description'])); ?></span>
            <span class="se-mini-minmax"><?php echo esc_html(round($current['temp_min'])); ?>°/<?php echo esc_html(round($current['temp_max'])); ?>°</span>
            <span class="se-mini-arrow" aria-hidden="true">▾</span>
        </div>

        <!-- Popup (bei Hover/Fokus) -->
        <div class="se-weather-popup" role="region" aria-label="Wetterdetails">
            <div class="se-weather-widget">

                <!-- Aktuelles Wetter -->
                <div class="se-weather-current">
                    <div class="se-location"><?php echo esc_html(strtoupper($location)); ?></div>

                    <div class="se-current-main">
                        <div class="se-icon-main">
                            <?php echo site_enhancer_get_icon_svg($current['main'], $current['icon']); ?>
                        </div>
                        <div class="se-temp-main">
                            <?php echo esc_html(round($current['temp'])); ?><span>°</span>
                        </div>
                    </div>

                    <div class="se-description">
                        <?php echo esc_html(ucfirst($current['description'])); ?>
                    </div>

                    <div class="se-meta">
                        <span class="se-date"><?php echo esc_html(date_i18n('D, j. M', $current['dt'])); ?></span>
                        <span class="se-minmax">
                            <?php echo esc_html(round($current['temp_min'])); ?>° / <?php echo esc_html(round($current['temp_max'])); ?>°
                        </span>
                    </div>
                </div>

                <!-- Vorhersage (horizontal) -->
                <?php if (!empty($next_days)): ?>
                    <div class="se-weather-forecast">
                        <?php foreach ($next_days as $date => $day): ?>
                            <div class="se-forecast-day">
                                <div class="se-day-name">
                                    <?php echo esc_html(date_i18n('D', $day['date'])); ?>
                                </div>
                                <div class="se-day-icon">
                                    <?php echo site_enhancer_get_icon_svg($day['main'], $day['icon']); ?>
                                </div>
                                <div class="se-day-temp">
                                    <?php echo esc_html(round($day['temp_min'])); ?>°/<?php echo esc_html(round($day['temp_max'])); ?>°
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (site_enhancer_get_option('show_footer')): ?>
                    <div class="se-footer">
                        <small>
                            <a href="https://openweathermap.org/" target="_blank" rel="noopener">OpenWeatherMap</a>
                            • <?php echo esc_html(date_i18n('H:i', current_time('timestamp'))); ?>
                        </small>
                    </div>
                <?php endif; ?>

            </div>

            <?php if (!empty($atts['link'])): ?>
                <a href="<?php echo esc_url($atts['link']); ?>" class="se-weather-page-link">
                    Zur Wetterseite →
                </a>
            <?php endif; ?>
        </div>

    </div>
    <?php

    $output = ob_get_clean();

    // Kleines Inline-JS: Popup per position:fixed positionieren, damit kein
    // sidebar overflow:hidden das Panel abschneidet.
    $output .= '<script>
(function(){
    function initWeatherPopups(){
        document.querySelectorAll(".se-weather-compact-wrapper").forEach(function(wrapper){
            if(wrapper.dataset.seInit) return;
            wrapper.dataset.seInit = "1";
            var mini  = wrapper.querySelector(".se-weather-mini");
            var popup = wrapper.querySelector(".se-weather-popup");
            if(!mini || !popup) return;
            function showPopup(){
                var r = mini.getBoundingClientRect();
                popup.style.top  = (r.bottom + window.scrollY + 6) + "px";
                popup.style.left = Math.max(4, r.right - 300 + window.scrollX) + "px";
                popup.classList.add("se-popup-visible");
            }
            function hidePopup(){
                popup.classList.remove("se-popup-visible");
            }
            wrapper.addEventListener("mouseenter", showPopup);
            wrapper.addEventListener("mouseleave", function(e){
                if(!wrapper.contains(e.relatedTarget)) hidePopup();
            });
            mini.addEventListener("focus", showPopup);
            mini.addEventListener("blur", function(){
                setTimeout(function(){ if(!wrapper.contains(document.activeElement)) hidePopup(); }, 120);
            });
        });
    }
    if(document.readyState === "loading"){
        document.addEventListener("DOMContentLoaded", initWeatherPopups);
    } else {
        initWeatherPopups();
    }
})();
</script>';

    return $output;
}
add_shortcode('site_weather', 'site_enhancer_weather_display');

/**
 * News-Feed Shortcode [news_feed]
 */
function site_enhancer_news_feed($atts) {
    $default_height = site_enhancer_get_option('news_feed_height') ?: '1000px';
    $default_url    = site_enhancer_get_option('news_feed_url') ?: 'https://baltsch.de/news/relativ_neukalen-news-feed.xml';
    $default_items  = intval(site_enhancer_get_option('news_feed_initial_count') ?: 10);
    $default_step   = intval(site_enhancer_get_option('news_feed_load_count') ?: 10);
    $default_image_width = intval(site_enhancer_get_option('news_feed_image_width') ?: 112);
    $default_image_height = intval(site_enhancer_get_option('news_feed_image_height') ?: 63);

    $atts = shortcode_atts(array(
        'height' => $default_height,
        'url'    => $default_url,
        'items'  => $default_items,
        'step'   => $default_step,
        'mode'   => 'loadmore',
        'image_width' => $default_image_width,
        'image_height' => $default_image_height,
    ), $atts, 'news_feed');

    $mode = site_enhancer_news_get_mode($atts['mode']);
    $initial_count = max(1, min(50, intval($atts['items'])));
    $load_count = max(1, min(50, intval($atts['step'])));
    $image_width = max(40, min(400, intval($atts['image_width'])));
    $image_height = max(24, min(300, intval($atts['image_height'])));
    $items = site_enhancer_get_news_feed_items($atts['url']);

    if (is_wp_error($items)) {
        return '<div class="se-error">Fehler beim Laden des News-Feeds: ' . esc_html($items->get_error_message()) . '</div>';
    }

    if (empty($items)) {
        return '<div class="se-news-widget"><p class="se-news-empty">Aktuell sind keine News verfügbar.</p></div>';
    }

    $wrapper_id = wp_unique_id('se-news-feed-');
    $total_items = count($items);
    $total_pages = site_enhancer_news_get_total_pages($total_items, $initial_count);
    $initial_items = array_slice($items, 0, $initial_count);
    $remaining_items = max(0, $total_items - count($initial_items));
    $list_style = '';
    if (!empty($atts['height']) && preg_match('/^\d+(px|%)$/', $atts['height'])) {
        $list_style = ' style="max-height:' . esc_attr($atts['height']) . ';"';
    }
    $widget_style = ' style="--se-news-image-width:' . esc_attr($image_width) . 'px;--se-news-image-height:' . esc_attr($image_height) . 'px;"';

    ob_start();
    ?>
    <div class="se-news-widget"
         id="<?php echo esc_attr($wrapper_id); ?>"
         <?php echo $widget_style; ?>
         data-mode="<?php echo esc_attr($mode); ?>"
         data-step="<?php echo esc_attr($load_count); ?>"
         data-items="<?php echo esc_attr($initial_count); ?>"
         data-offset="<?php echo esc_attr(count($initial_items)); ?>"
         data-page="1"
         data-url="<?php echo esc_url($atts['url']); ?>"
         data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
         data-nonce="<?php echo esc_attr(wp_create_nonce('site_enhancer_news_feed')); ?>">
        <div class="se-news-list"<?php echo $list_style; ?>>
            <?php echo site_enhancer_render_news_feed_items($initial_items); ?>
        </div>

        <?php echo site_enhancer_render_news_loading_skeleton($mode === 'pages' ? 2 : min(3, $load_count)); ?>

        <p class="se-news-feedback" hidden aria-live="polite"></p>

        <?php if ($mode === 'loadmore' && $remaining_items > 0) : ?>
            <button type="button" class="se-news-more" aria-controls="<?php echo esc_attr($wrapper_id); ?>">
                <?php echo esc_html(site_enhancer_news_more_button_label($remaining_items)); ?>
            </button>
        <?php endif; ?>

        <?php if ($mode === 'pages') : ?>
            <?php echo site_enhancer_render_news_pagination(1, $total_pages); ?>
        <?php endif; ?>
    </div>
    <?php

    $output = ob_get_clean();
    $output .= '<script>
(function(){
    if (window.seNewsFeedInit) return;
    window.seNewsFeedInit = true;

    function showFeedback(widget, message) {
        var feedback = widget.querySelector(".se-news-feedback");
        if (!feedback) return;
        if (!message) {
            feedback.hidden = true;
            feedback.textContent = "";
            return;
        }
        feedback.hidden = false;
        feedback.textContent = message;
    }

    function setLoading(widget, isLoading) {
        var loading = widget.querySelector(".se-news-loading");
        var button = widget.querySelector(".se-news-more");
        var paginationButtons = widget.querySelectorAll(".se-news-page");

        widget.classList.toggle("is-loading", isLoading);
        if (loading) {
            loading.hidden = !isLoading;
        }
        if (button) {
            button.disabled = isLoading;
        }
        paginationButtons.forEach(function(pageButton) {
            pageButton.disabled = isLoading;
        });
    }

    function setButtonState(widget, payload) {
        var button = widget.querySelector(".se-news-more");
        if (!button) return;

        if (!payload.hasMore) {
            button.hidden = true;
            return;
        }

        button.hidden = false;
        button.disabled = false;
        button.textContent = payload.buttonLabel;
    }

    function setPaginationState(widget, payload) {
        var pagination = widget.querySelector(".se-news-pagination");
        if (!pagination) return;
        pagination.innerHTML = payload.paginationHtml || "";
        widget.setAttribute("data-page", String(payload.currentPage || 1));
    }

    function requestNews(widget, options) {
        var ajaxUrl = widget.getAttribute("data-ajax-url");
        var feedUrl = widget.getAttribute("data-url");
        var nonce = widget.getAttribute("data-nonce");
        var mode = widget.getAttribute("data-mode") || "loadmore";
        var list = widget.querySelector(".se-news-list");
        var button = widget.querySelector(".se-news-more");
        var step = parseInt(widget.getAttribute("data-step"), 10) || 10;
        var items = parseInt(widget.getAttribute("data-items"), 10) || 10;
        var offset = parseInt(widget.getAttribute("data-offset"), 10) || 0;
        var page = parseInt(widget.getAttribute("data-page"), 10) || 1;
        var body = new URLSearchParams();

        showFeedback(widget, "");
        setLoading(widget, true);

        body.append("action", "site_enhancer_load_news_feed");
        body.append("nonce", nonce);
        body.append("url", feedUrl);
        body.append("mode", mode);

        if (mode === "pages") {
            body.append("page", String(options.page || page));
            body.append("limit", String(items));
        } else {
            body.append("offset", String(offset));
            body.append("limit", String(step));
        }

        return fetch(ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            body: body.toString()
        })
        .then(function(response) {
            return response.json().then(function(data) {
                if (!response.ok || !data.success) {
                    var message = data && data.data && data.data.message ? data.data.message : "Weitere News konnten nicht geladen werden.";
                    throw new Error(message);
                }

                return data.data;
            });
        })
        .then(function(payload) {
            if (mode === "pages") {
                list.innerHTML = payload.html || "";
                setPaginationState(widget, payload);
            } else {
                if (payload.html) {
                    list.insertAdjacentHTML("beforeend", payload.html);
                }

                widget.setAttribute("data-offset", String(payload.nextOffset || offset));
                setButtonState(widget, payload);
            }
        })
        .catch(function(error) {
            if (mode === "loadmore" && button) {
                button.textContent = button.getAttribute("data-default-label") || "Weitere News laden";
            }
            showFeedback(widget, error.message || "Weitere News konnten nicht geladen werden.");
        })
        .finally(function() {
            setLoading(widget, false);
        });
    }

    document.addEventListener("click", function(event) {
        var button = event.target.closest(".se-news-more");
        if (button) {
            var loadMoreWidget = button.closest(".se-news-widget");
            if (!loadMoreWidget || button.disabled) return;

            button.setAttribute("data-default-label", button.textContent);
            button.textContent = "Lade weitere News ...";
            requestNews(loadMoreWidget, {});
            return;
        }

        var pageButton = event.target.closest(".se-news-page");
        if (!pageButton) return;

        var pageWidget = pageButton.closest(".se-news-widget");
        if (!pageWidget || pageButton.disabled) return;

        requestNews(pageWidget, {
            page: parseInt(pageButton.getAttribute("data-page"), 10) || 1
        });
    });

    function initNewsWidgets() {
        document.querySelectorAll(".se-news-widget").forEach(function(widget) {
            showFeedback(widget, "");

            var button = widget.querySelector(".se-news-more");
            if (button) {
                setButtonState(widget, {
                    hasMore: !button.hidden,
                    buttonLabel: button.textContent
                });
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initNewsWidgets);
    } else {
        initNewsWidgets();
    }
})();
</script>';

    return $output;
}
add_shortcode('news_feed', 'site_enhancer_news_feed');

/**
 * ============================================================================
 * CSS & ASSETS
 * ============================================================================
 */

/**
 * Styles enqueuen - bedingungslos für konsistente Widget-Darstellung
 *
 * CSS wird auf allen Seiten geladen, da:
 * - Wetter-Widget in Sidebar auf allen Seiten angezeigt wird
 * - CSS-Datei klein ist (8.8 KB) - minimale Performance-Auswirkung
 * - Verhindert unformatierte Widgets in Sidebars, Custom HTML Blocks, etc.
 */
function site_enhancer_enqueue_styles() {
    wp_enqueue_style(
        'site-enhancer-styles',
        SITE_ENHANCER_URL . 'css/style.css',
        array(),
        SITE_ENHANCER_VERSION
    );
}
add_action('wp_enqueue_scripts', 'site_enhancer_enqueue_styles');

// CSS + JS auch im Block-Editor laden
add_action('enqueue_block_editor_assets', function (): void {
    site_enhancer_enqueue_styles();
    wp_enqueue_script(
        'nk-event-meta-block-control',
        SITE_ENHANCER_URL . 'js/event-meta-block-control.js',
        array('wp-hooks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-compose'),
        SITE_ENHANCER_VERSION,
        true
    );
});

/**
 * ============================================================================
 * ADMIN-ACTIONS
 * ============================================================================
 */

/**
 * Cache löschen
 */
function site_enhancer_clear_cache() {
    delete_transient('site_enhancer_weather_data');
}

function site_enhancer_admin_clear_cache() {
    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung.');
    }

    site_enhancer_clear_cache();

    wp_redirect(add_query_arg(
        array(
            'page' => 'site-enhancer',
            'cache_cleared' => '1'
        ),
        admin_url('options-general.php')
    ));
    exit;
}
add_action('admin_post_se_clear_cache', 'site_enhancer_admin_clear_cache');

/**
 * Admin-Notices
 */
function site_enhancer_admin_notices() {
    if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] === '1') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Cache erfolgreich gelöscht!</strong> Die Daten werden beim nächsten Aufruf neu geladen.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'site_enhancer_admin_notices');

/**
 * ============================================================================
 * PLUGIN-AKTIVIERUNG
 * ============================================================================
 */

function site_enhancer_activate() {
    $existing_options = get_option('site_enhancer_options', false);

    if ($existing_options === false) {
        // Migration von altem Wetter-Plugin prüfen
        $old_weather_options = get_option('wetter_vorhersage_options', false);

        if ($old_weather_options !== false) {
            // Alte Einstellungen migrieren
            add_option('site_enhancer_options', array(
                'api_key' => isset($old_weather_options['api_key']) ? $old_weather_options['api_key'] : '',
                'latitude' => isset($old_weather_options['latitude']) ? $old_weather_options['latitude'] : '53.822',
                'longitude' => isset($old_weather_options['longitude']) ? $old_weather_options['longitude'] : '12.788',
                'location_name' => isset($old_weather_options['location_name']) ? $old_weather_options['location_name'] : 'Neukalen',
                'forecast_days' => isset($old_weather_options['forecast_days']) ? $old_weather_options['forecast_days'] : 3,
                'cache_duration' => isset($old_weather_options['cache_duration']) ? $old_weather_options['cache_duration'] : 30,
            ));
        } else {
            // Neue Installation mit Defaults
            add_option('site_enhancer_options', site_enhancer_get_default_options());
        }
    }
}
register_activation_hook(__FILE__, 'site_enhancer_activate');

/**
 * ============================================================================
 * COPYRIGHT FILTER (GeneratePress)
 * ============================================================================
 */

/**
 * GeneratePress Copyright-Text anpassen
 */
function site_enhancer_custom_copyright() {
    $copyright_text = site_enhancer_get_option('copyright_text');
    $datenschutz_text = site_enhancer_get_option('datenschutz_text');
    $datenschutz_link = site_enhancer_get_option('datenschutz_link');
    $impressum_text = site_enhancer_get_option('impressum_text');
    $impressum_link = site_enhancer_get_option('impressum_link');

    // {year} Platzhalter ersetzen
    $copyright_text = str_replace('{year}', date('Y'), $copyright_text);

    $output = '<span aria-label="Copyright">' . esc_html($copyright_text) . '</span>';

    if (!empty($datenschutz_link) && !empty($datenschutz_text)) {
        $output .= ' – <a href="' . esc_url($datenschutz_link) . '">' . esc_html($datenschutz_text) . '</a>';
    }

    if (!empty($impressum_link) && !empty($impressum_text)) {
        $output .= ' | <a href="' . esc_url($impressum_link) . '">' . esc_html($impressum_text) . '</a>';
    }

    return $output;
}
add_filter('generate_copyright', 'site_enhancer_custom_copyright');

/**
 * ============================================================================
 * VERANSTALTUNGS-FELDER (Datum, Uhrzeit, Ort) für Beiträge
 * Felder: _event_date, _event_time_start, _event_time_end, _event_location
 * ============================================================================
 */

/**
 * Meta-Box im Block-/Klassik-Editor registrieren.
 */
function nk_event_add_meta_box(): void {
    add_meta_box(
        'nk_event_details',
        'Veranstaltungs-Details',
        'nk_event_meta_box_callback',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_post', 'nk_event_add_meta_box');

/**
 * Meta-Box Inhalt rendern.
 */
function nk_event_meta_box_callback(WP_Post $post): void {
    $date       = get_post_meta($post->ID, '_event_date',       true);
    $time_start = get_post_meta($post->ID, '_event_time_start', true);
    $time_end   = get_post_meta($post->ID, '_event_time_end',   true);
    $location   = get_post_meta($post->ID, '_event_location',   true);
    // Default: anzeigen (1), außer explizit deaktiviert (0)
    $visible    = get_post_meta($post->ID, '_event_meta_visible', true);
    $is_visible = ($visible === '' || $visible === '1');
    wp_nonce_field('nk_event_nonce', '_nk_event_nonce');
    ?>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <th colspan="2" style="padding:4px 0 10px;text-align:left;">
                <label style="font-weight:600;cursor:pointer;">
                    <input type="checkbox" name="_event_meta_visible" value="1"
                           <?php checked($is_visible, true); ?>>
                    Veranstaltungsinfo anzeigen
                </label>
            </th>
        </tr>
        <tr>
            <th style="width:130px;text-align:left;padding:6px 10px 6px 0;font-weight:600;">
                <label for="nk_event_date">Datum</label>
            </th>
            <td style="padding:4px 0;">
                <input type="date" id="nk_event_date" name="_event_date"
                       value="<?php echo esc_attr($date); ?>" style="width:170px;">
            </td>
        </tr>
        <tr>
            <th style="text-align:left;padding:6px 10px 6px 0;font-weight:600;">
                <label for="nk_event_time_start">Uhrzeit</label>
            </th>
            <td style="padding:4px 0;">
                <input type="time" id="nk_event_time_start" name="_event_time_start"
                       value="<?php echo esc_attr($time_start); ?>" style="width:110px;">
                <span style="margin:0 6px;color:#555;">–</span>
                <input type="time" id="nk_event_time_end" name="_event_time_end"
                       value="<?php echo esc_attr($time_end); ?>" style="width:110px;">
                <span style="margin-left:6px;color:#888;font-size:0.85em;">Bis-Zeit optional</span>
            </td>
        </tr>
        <tr>
            <th style="text-align:left;padding:6px 10px 6px 0;font-weight:600;">
                <label for="nk_event_location">Veranstaltungsort</label>
            </th>
            <td style="padding:4px 0;">
                <input type="text" id="nk_event_location" name="_event_location"
                       value="<?php echo esc_attr($location); ?>"
                       style="width:100%;max-width:420px;"
                       placeholder="z.B. Stadthalle Neukalen">
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Veranstaltungs-Felder speichern (Editor + Quick Edit).
 */
function nk_event_save(int $post_id): void {
    if (!isset($_POST['_nk_event_nonce']) ||
        !wp_verify_nonce($_POST['_nk_event_nonce'], 'nk_event_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = [
        '_event_date'       => 'sanitize_text_field',
        '_event_time_start' => 'sanitize_text_field',
        '_event_time_end'   => 'sanitize_text_field',
        '_event_location'   => 'sanitize_text_field',
    ];
    foreach ($fields as $key => $sanitizer) {
        if (array_key_exists($key, $_POST)) {
            $val = $sanitizer(wp_unslash($_POST[$key]));
            if ($val !== '') {
                update_post_meta($post_id, $key, $val);
            } else {
                delete_post_meta($post_id, $key);
            }
        }
    }

    // Sichtbarkeit: Checkbox – nur im vollen Editor vorhanden (nonce gesetzt)
    if (array_key_exists('_event_meta_visible', $_POST)) {
        update_post_meta($post_id, '_event_meta_visible', '1');
    } else {
        // Checkbox nicht im POST = abgehakt → 0 speichern
        update_post_meta($post_id, '_event_meta_visible', '0');
    }
}
add_action('save_post_post', 'nk_event_save');

/**
 * Spalte „Veranstaltung" in der Beitragsliste ergänzen.
 */
function nk_event_add_column(array $columns): array {
    $new = [];
    foreach ($columns as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['nk_event'] = 'Veranstaltung';
        }
    }
    return $new;
}
add_filter('manage_posts_columns', 'nk_event_add_column');

/**
 * Spalteninhalt: sichtbare Werte + versteckte Spans für Quick-Edit-JS.
 */
function nk_event_column_content(string $column, int $post_id): void {
    if ($column !== 'nk_event') return;

    $date       = get_post_meta($post_id, '_event_date',       true);
    $time_start = get_post_meta($post_id, '_event_time_start', true);
    $time_end   = get_post_meta($post_id, '_event_time_end',   true);
    $location   = get_post_meta($post_id, '_event_location',   true);

    $visible = get_post_meta($post_id, '_event_meta_visible', true);
    $is_visible = ($visible === '' || $visible === '1') ? '1' : '0';

    // Versteckte Werte für das Quick-Edit-JS
    echo '<span class="nk-ev-d"   hidden>' . esc_html($date)       . '</span>';
    echo '<span class="nk-ev-ts"  hidden>' . esc_html($time_start) . '</span>';
    echo '<span class="nk-ev-te"  hidden>' . esc_html($time_end)   . '</span>';
    echo '<span class="nk-ev-l"   hidden>' . esc_html($location)   . '</span>';
    echo '<span class="nk-ev-vis" hidden>' . esc_html($is_visible) . '</span>';

    if ($date) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        echo '<strong>' . esc_html($dt ? $dt->format('d.m.Y') : $date) . '</strong>';
        if ($time_start) {
            echo '<br>' . esc_html($time_start);
            if ($time_end) {
                echo ' – ' . esc_html($time_end) . ' Uhr';
            } else {
                echo ' Uhr';
            }
        }
        if ($location) {
            echo '<br><em style="color:#666;font-size:0.9em;">' . esc_html($location) . '</em>';
        }
    } else {
        echo '<span style="color:#aaa;">—</span>';
    }
}
add_action('manage_posts_custom_column', 'nk_event_column_content', 10, 2);

/**
 * Felder in der Schnellbearbeitung anzeigen.
 */
function nk_event_quick_edit_box(string $column_name, string $post_type): void {
    if ($column_name !== 'nk_event' || $post_type !== 'post') return;
    wp_nonce_field('nk_event_nonce', '_nk_event_nonce');
    ?>
    <fieldset class="inline-edit-col-right" style="clear:none;margin-top:0;">
        <div class="inline-edit-col">
            <h4 style="margin:4px 0 10px;font-weight:600;color:#1d2327;">Veranstaltungs-Details</h4>
            <label style="display:block;margin-bottom:10px;cursor:pointer;">
                <input type="checkbox" name="_event_meta_visible" class="nk-qe-vis" value="1">
                Veranstaltungsinfo anzeigen
            </label>
            <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 10px;align-items:center;">
                <label style="font-weight:600;white-space:nowrap;">Datum</label>
                <input type="date" name="_event_date" class="nk-qe-date" style="width:160px;">

                <label style="font-weight:600;white-space:nowrap;">Von</label>
                <input type="time" name="_event_time_start" class="nk-qe-ts" style="width:110px;">

                <label style="font-weight:600;white-space:nowrap;">Bis <span style="font-weight:400;color:#888;">(opt.)</span></label>
                <input type="time" name="_event_time_end" class="nk-qe-te" style="width:110px;">

                <label style="font-weight:600;white-space:nowrap;">Ort</label>
                <input type="text" name="_event_location" class="nk-qe-l"
                       style="width:100%;max-width:280px;" placeholder="Veranstaltungsort">
            </div>
        </div>
    </fieldset>
    <?php
}
add_action('quick_edit_custom_box', 'nk_event_quick_edit_box', 10, 2);

/**
 * JS: Schnellbearbeitung mit vorhandenen Werten aus der Spalte befüllen.
 */
function nk_event_quick_edit_js(): void {
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'edit' || $screen->post_type !== 'post') return;
    ?>
    <script>
    (function ($) {
        var origEdit = inlineEditPost.edit;
        inlineEditPost.edit = function (id) {
            origEdit.apply(this, arguments);
            var postId = typeof id === 'object'
                ? parseInt($(id).closest('tr').attr('id').replace('post-', ''), 10)
                : parseInt(id, 10);
            var $row  = $('#post-' + postId);
            var $edit = $('#edit-' + postId);
            $edit.find('.nk-qe-date').val($row.find('.nk-ev-d').text());
            $edit.find('.nk-qe-ts').val($row.find('.nk-ev-ts').text());
            $edit.find('.nk-qe-te').val($row.find('.nk-ev-te').text());
            $edit.find('.nk-qe-l').val($row.find('.nk-ev-l').text());
            $edit.find('.nk-qe-vis').prop('checked', $row.find('.nk-ev-vis').text() === '1');
        };
    }(jQuery));
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'nk_event_quick_edit_js');

/**
 * ============================================================================
 * VERANSTALTUNGS-META-ANZEIGE (Frontend)
 * ============================================================================
 */

/**
 * Event-Meta-HTML für einen Beitrag erzeugen.
 * Gibt leeren String zurück, wenn keine Veranstaltungsdaten gesetzt sind.
 */
function nk_event_render_meta_html(int $post_id): string {
    // Sichtbarkeit prüfen: leer = noch nicht gesetzt → Standard: anzeigen
    $visible = get_post_meta($post_id, '_event_meta_visible', true);
    if ($visible === '0') return '';

    $date       = get_post_meta($post_id, '_event_date',       true);
    $time_start = get_post_meta($post_id, '_event_time_start', true);
    $time_end   = get_post_meta($post_id, '_event_time_end',   true);
    $location   = get_post_meta($post_id, '_event_location',   true);

    if (!$date && !$location) return '';

    $color = site_enhancer_get_option('event_meta_color') ?: '#e67e22';
    $bold  = site_enhancer_get_option('event_meta_bold')  ? '600' : '400';

    $parts = [];
    if ($date) {
        $parts[] = '<span class="nk-em-date">&#128197; ' . esc_html(date_i18n('j. F Y', strtotime($date))) . '</span>';
    }
    if ($time_start) {
        $t = $time_start . ($time_end ? ' – ' . $time_end : '') . ' Uhr';
        $parts[] = '<span class="nk-em-time">&#128336; ' . esc_html($t) . '</span>';
    }
    if ($location) {
        $parts[] = '<span class="nk-em-loc">&#128205; ' . esc_html($location) . '</span>';
    }

    if (empty($parts)) return '';

    return '<div class="nk-event-meta" style="'
        . 'border-left-color:' . esc_attr($color) . ';'
        . 'font-weight:' . esc_attr($bold) . ';">'
        . implode('', $parts)
        . '</div>';
}

/**
 * Event-Meta in Auszüge einfügen – für klassische PHP-Loop-Templates.
 */
function nk_event_excerpt_prepend(string $excerpt): string {
    if (!site_enhancer_get_option('event_meta_in_excerpt')) return $excerpt;
    $post_id = get_the_ID();
    if (!$post_id || get_post_type($post_id) !== 'post') return $excerpt;
    return nk_event_render_meta_html($post_id) . $excerpt;
}
add_filter('the_excerpt', 'nk_event_excerpt_prepend');

/**
 * Event-Meta in Auszüge einfügen – für Gutenberg Query Loop / Post Excerpt Block.
 */
add_filter('render_block_core/post-excerpt', function(string $block_content, array $block, $instance): string {
    if (!site_enhancer_get_option('event_meta_in_excerpt')) return $block_content;
    $post_id = isset($instance->context['postId']) ? (int) $instance->context['postId'] : 0;
    if (!$post_id || get_post_type($post_id) !== 'post') return $block_content;
    $meta_html = nk_event_render_meta_html($post_id);
    if (!$meta_html) return $block_content;
    return $meta_html . $block_content;
}, 10, 3);

/**
 * Event-Meta in Auszüge einfügen – für den core/latest-posts Block.
 * Dieser Block ruft get_the_excerpt() auf; wir fangen die Post-IDs via setup_postdata-Hook
 * und injizieren die Meta direkt in den gerenderten Block-HTML.
 */
add_filter('render_block_core/latest-posts', function(string $block_content, array $block): string {
    if (!site_enhancer_get_option('event_meta_in_excerpt')) return $block_content;
    // Block-Level-Toggle: nkShowEventMeta=false → komplett ausblenden
    if (isset($block['attrs']['nkShowEventMeta']) && $block['attrs']['nkShowEventMeta'] === false) {
        return $block_content;
    }

    // Jeden Listenpunkt (<li>…</li>) einzeln verarbeiten
    return preg_replace_callback(
        '/<li\b[^>]*>.*?<\/li>/s',
        function(array $match): string {
            $li = $match[0];
            // Post-ID aus dem Titel-Link ermitteln (class="...wp-block-latest-posts__post-title...")
            // Fallback: erster <a href="..."> im <li>
            if (preg_match('/<a[^>]+class="[^"]*wp-block-latest-posts__post-title[^"]*"[^>]+href="([^"]+)"/', $li, $m)
                || preg_match('/<a[^>]+href="([^"]+)"[^>]+class="[^"]*wp-block-latest-posts__post-title[^"]*"/', $li, $m)) {
                $url = $m[1];
            } elseif (preg_match('/<a\s[^>]*href="(https?:\/\/[^"]+)"/', $li, $m)) {
                $url = $m[1];
            } else {
                return $li;
            }
            $post_id = url_to_postid($url);
            if (!$post_id || get_post_type($post_id) !== 'post') return $li;

            $meta_html = nk_event_render_meta_html($post_id);
            if (!$meta_html) return $li;

            // Meta vor dem Auszug-Div einfügen (falls vorhanden), sonst vor </li>
            if (strpos($li, 'wp-block-latest-posts__post-excerpt') !== false) {
                return str_replace(
                    '<div class="wp-block-latest-posts__post-excerpt">',
                    '<div class="wp-block-latest-posts__post-excerpt">' . $meta_html,
                    $li
                );
            }
            return str_replace('</li>', $meta_html . '</li>', $li);
        },
        $block_content
    );
}, 10, 2);

/**
 * Event-Meta im Einzelbeitrag oben einfügen.
 */
function nk_event_content_prepend(string $content): string {
    if (!is_singular('post') || !site_enhancer_get_option('event_meta_in_single')) return $content;
    $post_id = get_the_ID();
    if (!$post_id) return $content;
    return nk_event_render_meta_html($post_id) . $content;
}
add_filter('the_content', 'nk_event_content_prepend');

/**
 * Shortcode [event_meta] – manuelle Platzierung im Inhalt.
 * Optional: [event_meta id="123"]
 */
function nk_event_meta_shortcode(array $atts): string {
    $atts = shortcode_atts(['id' => get_the_ID()], $atts, 'event_meta');
    return nk_event_render_meta_html((int) $atts['id']);
}
add_shortcode('event_meta', 'nk_event_meta_shortcode');
