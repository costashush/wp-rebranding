<?php
/**
 * Plugin Name: 🦉 STORZ Rebranding Suite 🦊
 * Plugin URI: https://storz.co.il
 * Description: Complete white-label WordPress rebranding suite with login/admin branding, cleanup controls, custom login slug, dashboard widgets, footer editing, and simple avatar options via upload URL or RoboHash image URL.
 * Version: 2.3.0
 * Author: STORZ
 * Author URI: https://storz.co.il
 * License: GPL2+
 */

if (!defined('ABSPATH')) exit;

define('STORZ_RS_VERSION', '5.3.0');
define('STORZ_RS_OPTION_KEY', 'storz_rs_settings');

function storz_rs_defaults() {
    return array(
        'hide_lang_switcher'   => '1',
        'hide_posts'           => '0',
        'hide_pages'           => '0',
        'hide_comments'        => '1',
        'hide_plugins'         => '0',
        'hide_tools'           => '0',
        'hide_settings_menu'   => '0',
        'collapse_menu'        => '0',
        'hide_wp_version'      => '1',
        'disable_file_editor'  => '1',
        'login_bg_url'         => '',
        'footer_text'          => '© ' . date('Y') . ' STORZ',
        'footer_howdy_text'    => '',
        'dashboard_title'      => 'Welcome to STORZ',
        'dashboard_content'    => 'This site is managed by STORZ.',
        'custom_login_slug'    => '',
        'block_default_login'  => '0',
        'custom_login_logo'    => '',
        'custom_admin_logo'    => '',
        'hide_wp_logo'         => '1',
        'custom_login_css'     => '',
        'client_role_mode'     => '0',
        'hide_updates'         => '0',
        'custom_login_title'   => '',
        'custom_login_message' => '',
        'global_avatar_url'    => '',
        'robohash_image_url'   => '',
        'avatar_mode'          => 'none',
    );
}

function storz_rs_get_settings() {
    $saved = get_option(STORZ_RS_OPTION_KEY, array());
    return wp_parse_args(is_array($saved) ? $saved : array(), storz_rs_defaults());
}

function storz_rs_update_settings($new) {
    update_option(STORZ_RS_OPTION_KEY, wp_parse_args($new, storz_rs_defaults()));
}

register_activation_hook(__FILE__, function () {
    if (!get_option(STORZ_RS_OPTION_KEY)) {
        storz_rs_update_settings(storz_rs_defaults());
    }
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

function storz_rs_truthy($value) {
    return (string) $value === '1';
}

function storz_rs_is_client_user() {
    $settings = storz_rs_get_settings();
    if (!storz_rs_truthy($settings['client_role_mode'])) return false;

    $user = wp_get_current_user();
    if (!$user || empty($user->roles)) return false;

    $protected_roles = array('administrator', 'editor');
    foreach ($user->roles as $role) {
        if (in_array($role, $protected_roles, true)) {
            return false;
        }
    }
    return true;
}

function storz_rs_login_slug_url($slug = '') {
    $settings = storz_rs_get_settings();
    $slug = $slug !== '' ? $slug : $settings['custom_login_slug'];
    $slug = trim($slug, "/ \t\n\r\0\x0B");
    if ($slug === '') return wp_login_url();
    return home_url('/' . $slug . '/');
}

add_action('init', function () {
    $settings = storz_rs_get_settings();
    $slug = sanitize_title($settings['custom_login_slug']);
    if ($slug === '') return;
    add_rewrite_rule('^' . preg_quote($slug, '/') . '/?$', 'index.php?storz_rs_login=1', 'top');
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'storz_rs_login';
    return $vars;
});
add_action('template_redirect', function () {
    if (get_query_var('storz_rs_login')) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }
    $settings = storz_rs_get_settings();
    if (storz_rs_truthy($settings['block_default_login']) && !is_user_logged_in()) {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (strpos($request_uri, 'wp-login.php') !== false && !isset($_GET['action']) && !defined('XMLRPC_REQUEST')) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }
});

add_filter('login_display_language_dropdown', function ($show) {
    $settings = storz_rs_get_settings();
    return storz_rs_truthy($settings['hide_lang_switcher']) ? false : $show;
});

add_action('login_enqueue_scripts', function () {
    $settings = storz_rs_get_settings();
    $css = '';
    if (!empty($settings['login_bg_url'])) {
        $css .= "body.login{background-image:url('" . esc_url($settings['login_bg_url']) . "');background-size:cover;background-position:center;background-repeat:no-repeat;}";
    }
    if (!empty($settings['custom_login_logo'])) {
        $css .= "body.login h1 a{background-image:url('" . esc_url($settings['custom_login_logo']) . "') !important;background-size:contain !important;background-position:center center !important;background-repeat:no-repeat !important;width:440px !important;height:200px !important;}";
    } elseif (storz_rs_truthy($settings['hide_wp_logo'])) {
        $css .= "body.login h1 a{display:none !important;visibility:hidden !important;}";
    }
    $css .= "body.login #loginform,body.login #registerform,body.login #lostpasswordform{border-radius:18px;box-shadow:0 12px 35px rgba(0,0,0,.08);}";
    $css .= "body.login .button-primary{border-radius:999px;padding:0 18px;}";
    if (!empty($settings['custom_login_css'])) $css .= (string) $settings['custom_login_css'];
    if ($css) echo '<style>' . $css . '</style>';
});

add_filter('login_headerurl', function () { return home_url('/'); });
add_filter('login_headertext', function () {
    $settings = storz_rs_get_settings();
    return !empty($settings['custom_login_title']) ? $settings['custom_login_title'] : get_bloginfo('name');
});
add_filter('login_message', function ($message) {
    $settings = storz_rs_get_settings();
    if (!empty($settings['custom_login_message'])) {
        $message .= '<p class="message" style="border-left-color:#2271b1;">' . esc_html($settings['custom_login_message']) . '</p>';
    }
    return $message;
});

add_action('admin_bar_menu', function ($bar) {
    $settings = storz_rs_get_settings();
    if (storz_rs_truthy($settings['hide_wp_logo'])) $bar->remove_node('wp-logo');
    if (!empty($settings['custom_admin_logo'])) {
        $bar->add_node(array(
            'id'    => 'storz-rs-logo',
            'title' => '<img src="' . esc_url($settings['custom_admin_logo']) . '" alt="" style="height:20px;margin-top:6px;display:block;" />',
            'href'  => admin_url(),
        ));
    }
    if (!empty($settings['footer_howdy_text'])) {
        $my = wp_get_current_user();
        if ($my && $my->exists()) {
            $bar->add_node(array(
                'id'    => 'storz-rs-greeting',
                'title' => esc_html($settings['footer_howdy_text']),
                'parent'=> 'top-secondary',
                'href'  => admin_url('profile.php'),
            ));
        }
    }
}, 999);

add_action('admin_menu', function () {
    $settings = storz_rs_get_settings();
    $client_only = storz_rs_is_client_user();

    if (storz_rs_truthy($settings['hide_posts'])) remove_menu_page('edit.php');
    if (storz_rs_truthy($settings['hide_pages'])) remove_menu_page('edit.php?post_type=page');
    if (storz_rs_truthy($settings['hide_comments'])) remove_menu_page('edit-comments.php');

    if (storz_rs_truthy($settings['hide_plugins'])) {
        if (!storz_rs_truthy($settings['client_role_mode']) || $client_only) remove_menu_page('plugins.php');
    }
    if ($client_only && storz_rs_truthy($settings['hide_tools'])) remove_menu_page('tools.php');
    if ($client_only && storz_rs_truthy($settings['hide_settings_menu'])) remove_menu_page('options-general.php');
}, 999);

add_action('admin_bar_menu', function ($bar) {
    $settings = storz_rs_get_settings();
    if (storz_rs_truthy($settings['hide_posts'])) $bar->remove_node('new-post');
    if (storz_rs_truthy($settings['hide_pages'])) $bar->remove_node('new-page');
}, 999);

add_action('init', function () {
    $settings = storz_rs_get_settings();
    if (storz_rs_truthy($settings['hide_comments'])) {
        remove_post_type_support('post', 'comments');
        remove_post_type_support('page', 'comments');
    }
});

add_action('admin_init', function () {
    $settings = storz_rs_get_settings();
    if (storz_rs_truthy($settings['collapse_menu'])) {
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'wp_user-settings', 'mfold=o');
            update_user_meta($user_id, 'wp_user-settings-time', time());
        }
    }
});

add_action('admin_head', function () {
    $settings = storz_rs_get_settings();
    if (storz_rs_is_client_user() && storz_rs_truthy($settings['hide_updates'])) {
        remove_action('admin_notices', 'update_nag', 3);
        echo '<style>.update-nag,.notice-warning,.notice-info{display:none !important;}</style>';
    }
});

add_filter('the_generator', function ($gen) {
    $settings = storz_rs_get_settings();
    return storz_rs_truthy($settings['hide_wp_version']) ? '' : $gen;
}, 999);

add_action('init', function () {
    $settings = storz_rs_get_settings();
    if (storz_rs_truthy($settings['disable_file_editor']) && !defined('DISALLOW_FILE_EDIT')) {
        define('DISALLOW_FILE_EDIT', true);
    }
}, 1);

add_filter('admin_footer_text', function () {
    $settings = storz_rs_get_settings();
    return !empty($settings['footer_text']) ? esc_html($settings['footer_text']) : ('© ' . date('Y') . ' STORZ');
});

/**
 * Simple avatar logic:
 * 1. Per-user uploaded avatar URL
 * 2. Global uploaded avatar URL
 * 3. Global RoboHash image URL
 */
add_action('show_user_profile', 'storz_rs_avatar_field');
add_action('edit_user_profile', 'storz_rs_avatar_field');
function storz_rs_avatar_field($user) {
    $avatar = get_user_meta($user->ID, 'storz_rs_custom_avatar', true);
    ?>
    <h2>STORZ Avatar</h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="storz_rs_custom_avatar">Uploaded Avatar URL</label></th>
            <td>
                <input type="url" name="storz_rs_custom_avatar" id="storz_rs_custom_avatar" class="regular-text" value="<?php echo esc_attr($avatar); ?>" />
                <p class="description">Paste an uploaded image URL from the Media Library. This overrides the global avatar settings.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('personal_options_update', 'storz_rs_save_avatar');
add_action('edit_user_profile_update', 'storz_rs_save_avatar');
function storz_rs_save_avatar($user_id) {
    if (!current_user_can('edit_user', $user_id)) return;
    $value = isset($_POST['storz_rs_custom_avatar']) ? esc_url_raw(wp_unslash($_POST['storz_rs_custom_avatar'])) : '';
    update_user_meta($user_id, 'storz_rs_custom_avatar', $value);
}

add_filter('get_avatar', function ($avatar, $id_or_email, $size, $default, $alt) {
    $settings = storz_rs_get_settings();
    $user = false;

    if (is_numeric($id_or_email)) {
        $user = get_user_by('id', (int) $id_or_email);
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user = get_user_by('id', (int) $id_or_email->user_id);
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
    }

    $src = '';

    if ($user) {
        $custom = get_user_meta($user->ID, 'storz_rs_custom_avatar', true);
        if (!empty($custom)) {
            $src = $custom;
        }
    }

    if (empty($src) && $settings['avatar_mode'] === 'upload' && !empty($settings['global_avatar_url'])) {
        $src = $settings['global_avatar_url'];
    }

    if (empty($src) && $settings['avatar_mode'] === 'robohash' && !empty($settings['robohash_image_url'])) {
        $src = $settings['robohash_image_url'];
    }

    if (!empty($src)) {
        $avatar = sprintf(
            '<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" loading="lazy" decoding="async" style="border-radius:50%%;object-fit:cover;" />',
            esc_attr($alt), esc_url($src), (int) $size, (int) $size, (int) $size
        );
    }

    return $avatar;
}, 10, 5);

add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('storz_rs_dashboard_widget', 'STORZ Dashboard', 'storz_rs_dashboard_widget_render');
    wp_add_dashboard_widget('storz_rs_login_widget', 'STORZ Login Access', 'storz_rs_login_widget_render');
    wp_add_dashboard_widget('storz_rs_avatar_widget', 'STORZ Avatar Preview', 'storz_rs_avatar_widget_render');
});
function storz_rs_dashboard_widget_render() {
    $settings = storz_rs_get_settings();
    echo '<h3>' . esc_html($settings['dashboard_title']) . '</h3>';
    echo wpautop(wp_kses_post($settings['dashboard_content']));
    echo '<p><a class="button button-primary" href="' . esc_url(admin_url('options-general.php?page=storz-rs-settings')) . '">Open Rebranding Settings</a></p>';
}
function storz_rs_login_widget_render() {
    $settings = storz_rs_get_settings();
    echo '<p><strong>Custom login URL:</strong> ';
    echo !empty($settings['custom_login_slug']) ? '<code>' . esc_html(storz_rs_login_slug_url()) . '</code>' : '<code>' . esc_html(wp_login_url()) . '</code>';
    echo '</p>';
    if (storz_rs_truthy($settings['block_default_login'])) echo '<p>Default <code>wp-login.php</code> public access is redirected.</p>';
}
function storz_rs_avatar_widget_render() {
    $settings = storz_rs_get_settings();
    $src = '';

    if ($settings['avatar_mode'] === 'upload' && !empty($settings['global_avatar_url'])) {
        $src = $settings['global_avatar_url'];
        echo '<p>Using global uploaded avatar URL.</p>';
    } elseif ($settings['avatar_mode'] === 'robohash' && !empty($settings['robohash_image_url'])) {
        $src = $settings['robohash_image_url'];
        echo '<p>Using RoboHash image URL.</p>';
    } else {
        echo '<p>No global avatar selected.</p>';
    }

    if (!empty($src)) {
        echo '<p><img src="' . esc_url($src) . '" alt="" style="width:96px;height:96px;border-radius:50%;display:block;margin-bottom:10px;" /></p>';
        echo '<p><code>' . esc_html($src) . '</code></p>';
    }
}

add_action('admin_menu', function () {
    add_options_page('STORZ Rebranding Suite', 'STORZ Rebranding', 'manage_options', 'storz-rs-settings', 'storz_rs_render_settings_page');
});

function storz_rs_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    $settings = storz_rs_get_settings();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['storz_rs_save_settings'])) {
        check_admin_referer('storz_rs_settings_action', 'storz_rs_settings_nonce');
        $checkboxes = array('hide_lang_switcher','hide_posts','hide_pages','hide_comments','hide_plugins','hide_tools','hide_settings_menu','collapse_menu','hide_wp_version','disable_file_editor','block_default_login','hide_wp_logo','client_role_mode','hide_updates');
        foreach ($checkboxes as $key) $settings[$key] = isset($_POST[$key]) ? '1' : '0';

        $settings['login_bg_url']         = isset($_POST['login_bg_url']) ? esc_url_raw(wp_unslash($_POST['login_bg_url'])) : '';
        $settings['footer_text']          = isset($_POST['footer_text']) ? sanitize_text_field(wp_unslash($_POST['footer_text'])) : '';
        $settings['footer_howdy_text']    = isset($_POST['footer_howdy_text']) ? sanitize_text_field(wp_unslash($_POST['footer_howdy_text'])) : '';
        $settings['dashboard_title']      = isset($_POST['dashboard_title']) ? sanitize_text_field(wp_unslash($_POST['dashboard_title'])) : '';
        $settings['dashboard_content']    = isset($_POST['dashboard_content']) ? wp_kses_post(wp_unslash($_POST['dashboard_content'])) : '';
        $settings['custom_login_slug']    = isset($_POST['custom_login_slug']) ? sanitize_title(wp_unslash($_POST['custom_login_slug'])) : '';
        $settings['custom_login_logo']    = isset($_POST['custom_login_logo']) ? esc_url_raw(wp_unslash($_POST['custom_login_logo'])) : '';
        $settings['custom_admin_logo']    = isset($_POST['custom_admin_logo']) ? esc_url_raw(wp_unslash($_POST['custom_admin_logo'])) : '';
        $settings['custom_login_title']   = isset($_POST['custom_login_title']) ? sanitize_text_field(wp_unslash($_POST['custom_login_title'])) : '';
        $settings['custom_login_message'] = isset($_POST['custom_login_message']) ? sanitize_text_field(wp_unslash($_POST['custom_login_message'])) : '';
        $settings['custom_login_css']     = isset($_POST['custom_login_css']) ? wp_strip_all_tags(wp_unslash($_POST['custom_login_css'])) : '';
        $settings['global_avatar_url']    = isset($_POST['global_avatar_url']) ? esc_url_raw(wp_unslash($_POST['global_avatar_url'])) : '';
        $settings['robohash_image_url']   = isset($_POST['robohash_image_url']) ? esc_url_raw(wp_unslash($_POST['robohash_image_url'])) : '';
        $settings['avatar_mode']          = isset($_POST['avatar_mode']) ? sanitize_text_field(wp_unslash($_POST['avatar_mode'])) : 'none';

        storz_rs_update_settings($settings);
        flush_rewrite_rules();
        echo '<div class="updated notice is-dismissible"><p>STORZ Rebranding Suite settings saved.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>STORZ Rebranding Suite</h1>
        <p>Complete rebranding app for WordPress admin and login areas.</p>
        <form method="post">
            <?php wp_nonce_field('storz_rs_settings_action', 'storz_rs_settings_nonce'); ?>

            <h2>Branding</h2>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Hide WordPress Logo</th><td><label><input type="checkbox" name="hide_wp_logo" <?php checked($settings['hide_wp_logo'], '1'); ?> /> Remove default WordPress logos</label></td></tr>
                <tr><th scope="row"><label for="custom_login_logo">Custom Login Logo URL</label></th><td><input type="url" id="custom_login_logo" name="custom_login_logo" class="regular-text" value="<?php echo esc_attr($settings['custom_login_logo']); ?>" /></td></tr>
                <tr><th scope="row"><label for="custom_admin_logo">Custom Admin Bar Logo URL</label></th><td><input type="url" id="custom_admin_logo" name="custom_admin_logo" class="regular-text" value="<?php echo esc_attr($settings['custom_admin_logo']); ?>" /></td></tr>
                <tr><th scope="row"><label for="login_bg_url">Login Background URL</label></th><td><input type="url" id="login_bg_url" name="login_bg_url" class="regular-text" value="<?php echo esc_attr($settings['login_bg_url']); ?>" /></td></tr>
                <tr><th scope="row"><label for="footer_text">Footer Copyright Text</label></th><td><input type="text" id="footer_text" name="footer_text" class="regular-text" value="<?php echo esc_attr($settings['footer_text']); ?>" /></td></tr>
                <tr><th scope="row"><label for="footer_howdy_text">Admin Bar Greeting</label></th><td><input type="text" id="footer_howdy_text" name="footer_howdy_text" class="regular-text" value="<?php echo esc_attr($settings['footer_howdy_text']); ?>" placeholder="Hello from STORZ" /></td></tr>
            </table>

            <h2>Login Screen</h2>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Hide Language Switcher</th><td><label><input type="checkbox" name="hide_lang_switcher" <?php checked($settings['hide_lang_switcher'], '1'); ?> /> Hide language selector on login screen</label></td></tr>
                <tr><th scope="row"><label for="custom_login_title">Custom Login Header Text</label></th><td><input type="text" id="custom_login_title" name="custom_login_title" class="regular-text" value="<?php echo esc_attr($settings['custom_login_title']); ?>" /></td></tr>
                <tr><th scope="row"><label for="custom_login_message">Custom Login Message</label></th><td><input type="text" id="custom_login_message" name="custom_login_message" class="regular-text" value="<?php echo esc_attr($settings['custom_login_message']); ?>" /></td></tr>
                <tr><th scope="row"><label for="custom_login_css">Extra Login CSS</label></th><td><textarea id="custom_login_css" name="custom_login_css" class="large-text code" rows="5"><?php echo esc_textarea($settings['custom_login_css']); ?></textarea></td></tr>
            </table>

            <h2>Admin Cleanup</h2>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Hide Posts</th><td><label><input type="checkbox" name="hide_posts" <?php checked($settings['hide_posts'], '1'); ?> /> Remove Posts from admin and top bar</label></td></tr>
                <tr><th scope="row">Hide Pages</th><td><label><input type="checkbox" name="hide_pages" <?php checked($settings['hide_pages'], '1'); ?> /> Remove Pages menu</label></td></tr>
                <tr><th scope="row">Hide Comments</th><td><label><input type="checkbox" name="hide_comments" <?php checked($settings['hide_comments'], '1'); ?> /> Remove comments menu and support</label></td></tr>
                <tr><th scope="row">Hide Plugins</th><td><label><input type="checkbox" name="hide_plugins" <?php checked($settings['hide_plugins'], '1'); ?> /> Hide Plugins menu</label></td></tr>
                <tr><th scope="row">Hide Tools</th><td><label><input type="checkbox" name="hide_tools" <?php checked($settings['hide_tools'], '1'); ?> /> Hide Tools menu for client-role mode</label></td></tr>
                <tr><th scope="row">Hide Settings Menu</th><td><label><input type="checkbox" name="hide_settings_menu" <?php checked($settings['hide_settings_menu'], '1'); ?> /> Hide Settings menu for client-role mode</label></td></tr>
                <tr><th scope="row">Collapse Menu by Default</th><td><label><input type="checkbox" name="collapse_menu" <?php checked($settings['collapse_menu'], '1'); ?> /> Start admin sidebar collapsed</label></td></tr>
            </table>

            <h2>Client Mode</h2>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Client Role Mode</th><td><label><input type="checkbox" name="client_role_mode" <?php checked($settings['client_role_mode'], '1'); ?> /> Apply some hiding rules only to non-admin/editor roles</label></td></tr>
                <tr><th scope="row">Hide Update Notices</th><td><label><input type="checkbox" name="hide_updates" <?php checked($settings['hide_updates'], '1'); ?> /> Hide update notices for client-role users</label></td></tr>
            </table>

            <h2>Login Access</h2>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><label for="custom_login_slug">Custom Login Slug</label></th><td><input type="text" id="custom_login_slug" name="custom_login_slug" class="regular-text" value="<?php echo esc_attr($settings['custom_login_slug']); ?>" /><p class="description">Example: storz-login</p></td></tr>
                <tr><th scope="row">Block Default wp-login.php</th><td><label><input type="checkbox" name="block_default_login" <?php checked($settings['block_default_login'], '1'); ?> /> Redirect public access to default login page</label></td></tr>
            </table>

            <h2>Dashboard</h2>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><label for="dashboard_title">Dashboard Widget Title</label></th><td><input type="text" id="dashboard_title" name="dashboard_title" class="regular-text" value="<?php echo esc_attr($settings['dashboard_title']); ?>" /></td></tr>
                <tr><th scope="row"><label for="dashboard_content">Dashboard Widget Content</label></th><td><textarea id="dashboard_content" name="dashboard_content" class="large-text" rows="6"><?php echo esc_textarea($settings['dashboard_content']); ?></textarea></td></tr>
            </table>

            <h2>Avatar</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="avatar_mode">Avatar Source</label></th>
                    <td>
                        <select id="avatar_mode" name="avatar_mode">
                            <option value="none" <?php selected($settings['avatar_mode'], 'none'); ?>>None</option>
                            <option value="upload" <?php selected($settings['avatar_mode'], 'upload'); ?>>Uploaded Image URL</option>
                            <option value="robohash" <?php selected($settings['avatar_mode'], 'robohash'); ?>>RoboHash Image URL</option>
                        </select>
                    </td>
                </tr>
                <tr><th scope="row"><label for="global_avatar_url">Global Uploaded Avatar URL</label></th><td><input type="url" id="global_avatar_url" name="global_avatar_url" class="regular-text" value="<?php echo esc_attr($settings['global_avatar_url']); ?>" /></td></tr>
                <tr><th scope="row"><label for="robohash_image_url">RoboHash Image URL</label></th><td><input type="url" id="robohash_image_url" name="robohash_image_url" class="regular-text" value="<?php echo esc_attr($settings['robohash_image_url']); ?>" placeholder="https://robohash.org/storz.png?set=set1" /></td></tr>
                <tr><th scope="row">Priority</th><td><p class="description">Per-user uploaded avatar URL overrides the global setting. Then the selected global avatar source is used.</p></td></tr>
            </table>

            <h2>Security</h2>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Hide WordPress Version</th><td><label><input type="checkbox" name="hide_wp_version" <?php checked($settings['hide_wp_version'], '1'); ?> /> Remove generator meta output</label></td></tr>
                <tr><th scope="row">Disable Theme/Plugin Editor</th><td><label><input type="checkbox" name="disable_file_editor" <?php checked($settings['disable_file_editor'], '1'); ?> /> Best effort only, stronger in wp-config.php</label></td></tr>
            </table>

            <p class="submit"><button type="submit" name="storz_rs_save_settings" class="button button-primary">Save Settings</button></p>
        </form>
    </div>
    <?php
}
