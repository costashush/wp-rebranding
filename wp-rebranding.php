<?php
/**
 * Plugin Name: WP Rebranding 🦋
 * Plugin URI: https://github.com/costashush/wp-rebranding
 * Description: White-label WordPress login and admin area. Hide or replace logos, remove comments, and add custom branding.
 * Version: 1.2.1
 * Author: STORZ
 * Author URI: https://storz.co.il
 */

if (!defined('ABSPATH')) exit;

/**
 * Login page rebranding CSS
 */
add_action('login_enqueue_scripts', function () {
    $hide_logo       = get_option('wprb_hide_logo', '0');
    $use_custom      = get_option('wprb_use_custom_logo', '0');
    $custom_logo_url = trim((string) get_option('wprb_custom_logo_url', ''));

    if ($hide_logo !== '1' && !($use_custom === '1' && $custom_logo_url !== '')) {
        return;
    }
    ?>
    <style>
        <?php if ($use_custom === '1' && $custom_logo_url !== ''): ?>
            /* Custom login logo */
            body.login h1 a {
                background-image: url('<?php echo esc_url($custom_logo_url); ?>') !important;
                background-size: cover !important;
                pointer-events: none;
                background-repeat: no-repeat !important;
                background-position: center center !important;
                width: 220px !important;
                height: 90px !important;
                display: block;
                text-indent: -9999px;
                box-shadow: none !important;
            }
        <?php elseif ($hide_logo === '1'): ?>
            /* Hide WP login logo */
            body.login h1 a {
                display: none !important;
                visibility: hidden !important;
            }
        <?php endif; ?>
    </style>
    <?php
});

/**
 * Remove WP logo from admin bar
 */
add_action('admin_bar_menu', function ($wp_admin_bar) {
    $hide_logo = get_option('wprb_hide_logo', '0');
    if ($hide_logo === '1') {
        $wp_admin_bar->remove_node('wp-logo');
    }
}, 999);

/**
 * Add custom logo to admin bar (top-left)
 */
add_action('admin_bar_menu', function ($bar) {
    $hide_logo          = get_option('wprb_hide_logo', '0');
    $custom_admin_logo  = trim((string) get_option('wprb_custom_admin_logo_url', ''));

    if ($hide_logo === '1' && $custom_admin_logo !== '') {
        $bar->add_node([
            'id'    => 'wprb-admin-logo',
            'title' => '<img src="' . esc_url($custom_admin_logo) . '" style="height:20px; margin-top:6px;">',
            'href'  => admin_url(),
        ]);
    }
}, 1);

/**
 * Remove Comments system from WordPress
 */
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});

add_action('init', function () {
    // Disable comments for posts & pages
    remove_post_type_support('post', 'comments');
    remove_post_type_support('page', 'comments');
});

/**
 * Settings page
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

function wprb_render_settings_page() {
    if (!current_user_can('manage_options')) return;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wprb_save_settings'])) {
        check_admin_referer('wprb_settings_nonce');

        update_option('wprb_hide_logo', isset($_POST['wprb_hide_logo']) ? '1' : '0');
        update_option('wprb_use_custom_logo', isset($_POST['wprb_use_custom_logo']) ? '1' : '0');
        update_option('wprb_custom_logo_url', esc_url_raw(trim($_POST['wprb_custom_logo_url'] ?? '')));
        update_option('wprb_custom_admin_logo_url', esc_url_raw(trim($_POST['wprb_custom_admin_logo_url'] ?? '')));

        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $hide_logo       = get_option('wprb_hide_logo', '0');
    $use_custom      = get_option('wprb_use_custom_logo', '0');
    $custom_logo_url = esc_url(get_option('wprb_custom_logo_url', ''));
    $admin_logo_url  = esc_url(get_option('wprb_custom_admin_logo_url', ''));
    ?>

    <div class="wrap">
        <h1>WP Rebranding – Settings</h1>

        <form method="post">
            <?php wp_nonce_field('wprb_settings_nonce'); ?>

            <h2>Login Logo</h2>
            <table class="form-table">
                <tr>
                    <th>Hide WordPress Logo</th>
                    <td><input type="checkbox" name="wprb_hide_logo" <?php checked($hide_logo, '1'); ?>></td>
                </tr>
                <tr>
                    <th>Use Custom Login Logo</th>
                    <td><input type="checkbox" name="wprb_use_custom_logo" <?php checked($use_custom, '1'); ?>></td>
                </tr>
                <tr>
                    <th>Custom Login Logo URL</th>
                    <td>
                        <input type="url" name="wprb_custom_logo_url" class="regular-text" value="<?php echo $custom_logo_url; ?>">
                        <p class="description">220×90 recommended.</p>
                    </td>
                </tr>
            </table>

            <h2>Admin Logo</h2>
            <table class="form-table">
                <tr>
                    <th>Custom Admin Bar Logo URL</th>
                    <td>
                        <input type="url" name="wprb_custom_admin_logo_url" class="regular-text" value="<?php echo $admin_logo_url; ?>">
                        <p class="description">Suggested size: 20–30px tall.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="wprb_save_settings" class="button button-primary">Save Settings</button>
            </p>
        </form>
    </div>
    <?php
}
