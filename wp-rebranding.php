<?php
/**
 * Plugin Name: WP Rebranding
 * Description: White-label your WordPress login page. Hide the WordPress logo or replace it with a custom logo using simple settings.
 * Version: 1.1.0
 * Author: STORZ
 */

if (!defined('ABSPATH')) exit;

/**
 * Login page rebranding CSS
 */
add_action('login_enqueue_scripts', function () {
    $hide_logo       = get_option('wprb_hide_logo', '0');
    $use_custom      = get_option('wprb_use_custom_logo', '0');
    $custom_logo_url = trim((string) get_option('wprb_custom_logo_url', ''));

    // Default WordPress logo is shown if both settings are off
    if ($hide_logo !== '1' && !($use_custom === '1' && $custom_logo_url !== '')) {
        return;
    }
    ?>
    <style>
        <?php if ($hide_logo === '1' && !($use_custom === '1' && $custom_logo_url !== '')): ?>
            /* Hide WordPress logo completely on login */
            body.login h1 a {
                display: none !important;
                visibility: hidden !important;
            }
        <?php elseif ($use_custom === '1' && $custom_logo_url !== ''): ?>
            /* Custom logo on login */
            body.login h1 a {
                background-image: url('<?php echo esc_url($custom_logo_url); ?>') !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center center !important;
                width: 220px !important;
                height: 90px !important;
                display: block;
                text-indent: -9999px;
                outline: none !important;
                box-shadow: none !important;
            }
        <?php endif; ?>
    </style>
    <?php
});

/**
 * Hide WP logo in admin bar when "Hide WordPress Logo" is enabled
 */
add_action('admin_bar_menu', function ($wp_admin_bar) {
    $hide_logo = get_option('wprb_hide_logo', '0');
    if ($hide_logo === '1') {
        $wp_admin_bar->remove_node('wp-logo'); // removes top-left WP logo
    }
}, 999);

/**
 * Settings page under "Settings → WP Rebranding"
 */
add_action('admin_menu', function () {
    add_options_page(
        'WP Rebranding Settings',
        'WP Rebranding',
        'manage_options',
        'wprb-settings',
        'wprb_render_settings_page'
    );
});

/**
 * Settings page HTML
 */
function wprb_render_settings_page() {
    if (!current_user_can('manage_options')) return;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wprb_save_settings'])) {
        check_admin_referer('wprb_settings_nonce');

        $hide_logo  = isset($_POST['wprb_hide_logo']) ? '1' : '0';
        $use_custom = isset($_POST['wprb_use_custom_logo']) ? '1' : '0';
        $logo_url   = isset($_POST['wprb_custom_logo_url']) ? esc_url_raw(trim($_POST['wprb_custom_logo_url'])) : '';

        update_option('wprb_hide_logo', $hide_logo);
        update_option('wprb_use_custom_logo', $use_custom);
        update_option('wprb_custom_logo_url', $logo_url);

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $hide_logo       = get_option('wprb_hide_logo', '0');
    $use_custom      = get_option('wprb_use_custom_logo', '0');
    $custom_logo_url = esc_url(get_option('wprb_custom_logo_url', ''));
    ?>

    <div class="wrap">
        <h1>WP Rebranding – Settings</h1>

        <form method="post">
            <?php wp_nonce_field('wprb_settings_nonce'); ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">Hide WordPress Logo</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wprb_hide_logo" value="1"
                                <?php checked($hide_logo, '1'); ?>>
                            Remove default WordPress logo from <strong>login page</strong> and <strong>admin bar</strong>.
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Use Custom Login Logo</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wprb_use_custom_logo" value="1"
                                <?php checked($use_custom, '1'); ?>>
                            Replace the login logo with a custom image.
                        </label>
                        <p class="description">
                            This affects the <strong>login page only</strong>. Admin bar uses text/title, not an image.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Custom Logo URL</th>
                    <td>
                        <input type="url" name="wprb_custom_logo_url" class="regular-text"
                               placeholder="https://yourdomain.com/logo.png"
                               value="<?php echo $custom_logo_url; ?>">
                        <p class="description">Direct URL to your logo (PNG/SVG recommended, ~220×90px).</p>
                    </td>
                </tr>

            </table>

            <p class="submit">
                <button type="submit" name="wprb_save_settings" class="button button-primary">Save Settings</button>
            </p>
        </form>

        <h2>Logic</h2>
        <ul>
            <li><strong>Login page</strong>:
                <ul>
                    <li>If "Use Custom Login Logo" is ON and URL is set → show custom logo.</li>
                    <li>Else if "Hide WordPress Logo" is ON → hide logo completely.</li>
                    <li>Else → show default WP logo.</li>
                </ul>
            </li>
            <li><strong>Admin bar</strong>:
                <ul>
                    <li>If "Hide WordPress Logo" is ON → remove WP logo from admin bar.</li>
                    <li>Else → show default WP logo.</li>
                </ul>
            </li>
        </ul>
    </div>

    <?php
}
