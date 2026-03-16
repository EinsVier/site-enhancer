<?php
/**
 * Neukalen Site Enhancer – Site Design & CSS
 *
 * Widgets/Sidebar-Stil und eigenes CSS.
 * Hintergrund, Header und Navigation werden über GeneratePress Premium verwaltet.
 *
 * @package neukalen-site-enhancer
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// ---------------------------------------------------------------------------
// Standard-Werte
// ---------------------------------------------------------------------------

function se_design_get_defaults(): array {
    return [
        // Widgets & Sidebar
        'widget_bg_color'      => '#ffffff',
        'widget_bg_opacity'    => '90',
        'widget_border_radius' => '9',
        'widget_spacing_v'     => '15',
        // Eigenes CSS
        'custom_css'           => '',
    ];
}

// ---------------------------------------------------------------------------
// Option laden
// ---------------------------------------------------------------------------

function se_design_get( string $key ): string {
    static $opts = null;
    if ( $opts === null ) {
        $opts = (array) get_option( 'se_design_options', [] );
    }
    $defaults = se_design_get_defaults();
    return (string) ( $opts[ $key ] ?? $defaults[ $key ] ?? '' );
}

// ---------------------------------------------------------------------------
// Einstellungen registrieren
// ---------------------------------------------------------------------------

add_action( 'admin_init', 'se_design_register_settings' );

function se_design_register_settings(): void {
    register_setting(
        'se_design_group',
        'se_design_options',
        [ 'sanitize_callback' => 'se_design_sanitize' ]
    );
}

function se_design_sanitize( $input ): array {
    $sanitized = [];

    $sanitized['widget_bg_color']    = isset( $input['widget_bg_color'] )
        ? sanitize_hex_color( $input['widget_bg_color'] ) ?? '#ffffff'
        : '#ffffff';

    $sanitized['widget_bg_opacity']    = se_design_clamp( $input['widget_bg_opacity']    ?? 90, 0, 100 );
    $sanitized['widget_border_radius'] = se_design_clamp( $input['widget_border_radius'] ?? 9,  0, 50  );
    $sanitized['widget_spacing_v']     = se_design_clamp( $input['widget_spacing_v']     ?? 15, 0, 60  );

    $sanitized['custom_css'] = isset( $input['custom_css'] )
        ? wp_strip_all_tags( $input['custom_css'] )
        : '';

    add_settings_error( 'se_design_options', 'saved', 'Design-Einstellungen gespeichert.', 'success' );

    return $sanitized;
}

function se_design_clamp( $value, int $min, int $max ): int {
    return max( $min, min( $max, (int) $value ) );
}

// ---------------------------------------------------------------------------
// Admin-Seite
// ---------------------------------------------------------------------------

add_action( 'admin_menu', 'se_design_admin_menu' );

function se_design_admin_menu(): void {
    add_options_page(
        'Neukalen Site Design',
        'Site Design',
        'manage_options',
        'se-site-design',
        'se_design_settings_page'
    );
}

function se_design_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $opts = array_merge( se_design_get_defaults(), (array) get_option( 'se_design_options', [] ) );
    ?>
    <div class="wrap">
        <h1>Neukalen Site Design</h1>
        <p>
            Hintergrund, Header und Navigation werden über
            <strong>GeneratePress Premium → Customizer</strong> verwaltet.
        </p>
        <?php settings_errors( 'se_design_options' ); ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'se_design_group' ); ?>

            <!-- ==================== WIDGETS & SIDEBAR ==================== -->
            <h2>Widgets &amp; Seitenleiste</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Hintergrundfarbe</th>
                    <td>
                        <input type="color" name="se_design_options[widget_bg_color]"
                               value="<?php echo esc_attr( $opts['widget_bg_color'] ); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Deckkraft</th>
                    <td>
                        <input type="range" name="se_design_options[widget_bg_opacity]"
                               value="<?php echo esc_attr( $opts['widget_bg_opacity'] ); ?>"
                               min="0" max="100" step="5"
                               oninput="document.getElementById('se_widget_opacity_val').textContent = this.value + '%'">
                        <span id="se_widget_opacity_val"><?php echo esc_html( $opts['widget_bg_opacity'] ); ?>%</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ecken-Radius (px)</th>
                    <td>
                        <input type="number" name="se_design_options[widget_border_radius]"
                               value="<?php echo esc_attr( $opts['widget_border_radius'] ); ?>"
                               min="0" max="50" class="small-text">
                        <p class="description">Abrundung der Widget-Karten.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Abstand zwischen Widgets (px)</th>
                    <td>
                        <input type="number" name="se_design_options[widget_spacing_v]"
                               value="<?php echo esc_attr( $opts['widget_spacing_v'] ); ?>"
                               min="0" max="60" class="small-text">
                    </td>
                </tr>
            </table>

            <!-- ==================== EIGENES CSS ==================== -->
            <h2>Eigenes CSS</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Zusätzliches CSS</th>
                    <td>
                        <textarea name="se_design_options[custom_css]"
                                  rows="14"
                                  class="large-text code"
                                  style="font-family: monospace; font-size: 13px; line-height: 1.5;"><?php echo esc_textarea( $opts['custom_css'] ); ?></textarea>
                        <p class="description">
                            Beliebige CSS-Regeln – theme-unabhängig und versionierbar.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Design-Einstellungen speichern' ); ?>
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// CSS generieren und einbinden
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'se_design_enqueue_css', 30 );

function se_design_enqueue_css(): void {
    $css = se_design_generate_css();
    if ( $css ) {
        wp_add_inline_style( 'site-enhancer-styles', $css );
    }
}

function se_design_generate_css(): string {
    $css = '';

    // --- Widgets & Sidebar ---
    $widget_color   = se_design_get( 'widget_bg_color' ) ?: '#ffffff';
    $widget_opacity = (int) se_design_get( 'widget_bg_opacity' );
    $widget_radius  = (int) se_design_get( 'widget_border_radius' );
    $widget_spacing = (int) se_design_get( 'widget_spacing_v' );
    $widget_rgba    = se_design_hex_to_rgba( $widget_color, $widget_opacity / 100 );

    $css .= ".c-sidebar .widget, .site-main {\n"
          . "    padding: 14px;\n"
          . "    background: {$widget_rgba};\n"
          . "    border-radius: {$widget_radius}px;\n"
          . "}\n";

    $css .= ".sidebar .widget {\n"
          . "    border-radius: {$widget_radius}px;\n"
          . "    overflow: hidden;\n"
          . "}\n";

    if ( $widget_spacing > 0 ) {
        $css .= ".widget-area .widget {\n"
              . "    padding-top: {$widget_spacing}px;\n"
              . "    padding-bottom: {$widget_spacing}px;\n"
              . "    margin-bottom: 10px;\n"
              . "}\n";
        $css .= ".widget-area .widget_block { margin-bottom: 10px !important; }\n";
    }

    // --- Eigenes CSS ---
    $custom = se_design_get( 'custom_css' );
    if ( $custom ) {
        $css .= "\n/* Eigenes CSS */\n" . $custom . "\n";
    }

    return $css;
}

/**
 * Hex-Farbe + Deckkraft → rgba()-String
 */
function se_design_hex_to_rgba( string $hex, float $opacity ): string {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    [ $r, $g, $b ] = array_map( 'hexdec', str_split( $hex, 2 ) );
    $a = round( max( 0.0, min( 1.0, $opacity ) ), 2 );
    return "rgba({$r},{$g},{$b},{$a})";
}
