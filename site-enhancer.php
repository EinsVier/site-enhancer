<?php
/**
 * Plugin Name: Site Enhancer Widgets
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
    $sanitized = array();
    $defaults = site_enhancer_get_default_options();

    // API-Key
    if (isset($input['api_key'])) {
        $api_key = sanitize_text_field($input['api_key']);
        if ($api_key !== '' && !preg_match('/^\*+$/', $api_key)) {
            $sanitized['api_key'] = $api_key;
        } else {
            $old_options = get_option('site_enhancer_options', array());
            $sanitized['api_key'] = isset($old_options['api_key']) ? $old_options['api_key'] : '';
        }
    }

    // Latitude
    if (isset($input['latitude'])) {
        $lat = floatval($input['latitude']);
        $sanitized['latitude'] = ($lat >= -90 && $lat <= 90) ? $lat : $defaults['latitude'];
    }

    // Longitude
    if (isset($input['longitude'])) {
        $lon = floatval($input['longitude']);
        $sanitized['longitude'] = ($lon >= -180 && $lon <= 180) ? $lon : $defaults['longitude'];
    }

    // Ortsname
    if (isset($input['location_name'])) {
        $sanitized['location_name'] = sanitize_text_field($input['location_name']);
    }

    // Vorschau-Tage
    if (isset($input['forecast_days'])) {
        $days = intval($input['forecast_days']);
        $sanitized['forecast_days'] = ($days >= 1 && $days <= 5) ? $days : $defaults['forecast_days'];
    }

    // Cache-Dauer
    if (isset($input['cache_duration'])) {
        $duration = intval($input['cache_duration']);
        $sanitized['cache_duration'] = ($duration >= 5 && $duration <= 1440) ? $duration : $defaults['cache_duration'];
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
                        <code>[news_feed]</code>
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
 * Wetter-Widget Shortcode [site_weather]
 */
function site_enhancer_weather_display($atts) {
    $atts = shortcode_atts(array(
        'city' => ''
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

        <div class="se-footer">
            <small>
                <a href="https://openweathermap.org/" target="_blank" rel="noopener">OpenWeatherMap</a>
                • <?php echo esc_html(date_i18n('H:i', current_time('timestamp'))); ?>
            </small>
        </div>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('site_weather', 'site_enhancer_weather_display');

/**
 * News-Feed Shortcode [news_feed]
 */
function site_enhancer_news_feed($atts) {
    $atts = shortcode_atts(array(
        'height' => '1000px'
    ), $atts, 'news_feed');

    return '<iframe src="https://baltsch.de/news/relativ_neukalen-news-feed.html"
                    width="100%"
                    height="' . esc_attr($atts['height']) . '"
                    style="border:none;"
                    title="Neukalen News Feed"></iframe>';
}
add_shortcode('news_feed', 'site_enhancer_news_feed');

/**
 * ============================================================================
 * CSS & ASSETS
 * ============================================================================
 */

/**
 * Styles enqueuen
 */
function site_enhancer_enqueue_styles() {
    global $post;

    if (is_a($post, 'WP_Post') &&
        (has_shortcode($post->post_content, 'site_weather') ||
         has_shortcode($post->post_content, 'news_feed'))) {

        wp_enqueue_style(
            'site-enhancer-styles',
            SITE_ENHANCER_URL . 'css/style.css',
            array(),
            SITE_ENHANCER_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'site_enhancer_enqueue_styles', 999);

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
