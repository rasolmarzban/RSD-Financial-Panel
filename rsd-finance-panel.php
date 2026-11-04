<?php

/**
 * Plugin Name:       RSD Finance Panel
 * Plugin URI:        https://github.com/rasolmarzban/rsd-finance-panel
 * Description:       A comprehensive client portal and finance management plugin for WordPress. Provides separate admin and user dashboards for managing documents, tickets, invoices, reports, services, and calendar events.
 * Version:           1.0.0
 * Author:            Rasoul Marzban
 * Author URI:        https://github.com/rasolmarzban
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rsd-finance-panel
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('RSD_FP_VERSION', '1.0.0');
define('RSD_FP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RSD_FP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RSD_FP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('RSD_FP_TEXT_DOMAIN', 'rsd-finance-panel');

// Composer autoloader
if (file_exists(RSD_FP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once RSD_FP_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Plugin activation hook
 */
function rsd_fp_activate()
{
    RSD\FinancePanel\Core\Activator::activate();
}
register_activation_hook(__FILE__, 'rsd_fp_activate');

/**
 * Plugin deactivation hook
 */
function rsd_fp_deactivate()
{
    RSD\FinancePanel\Core\Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'rsd_fp_deactivate');

/**
 * Plugin uninstall hook (handled via uninstall.php)
 */

/**
 * Initialize the plugin
 *
 * @return RSD\FinancePanel\Core\Plugin
 */
function rsd_fp_init()
{
    static $instance = null;

    if ($instance === null) {
        $instance = new RSD\FinancePanel\Core\Plugin();
        $instance->run();
    }

    return $instance;
}
add_action('plugins_loaded', 'rsd_fp_init');

/**
 * Early admin-side user phone hooks (render/edit/save/search).
 *
 * Registered at global PHP scope to guarantee capture regardless of
 * is_admin() timing during nested plugins_loaded bootstrapping.
 * The closures resolve the Admin component at runtime, so by the time
 * WordPress fires these actions the plugin is fully initialised.
 */
add_action('show_user_profile', function ($user) {
    if (is_numeric($user)) $user = \get_user_by('ID', (int)$user);
    $p = rsd_fp_init();
    $a = $p->get_component('admin');
    if ($a && method_exists($a, 'render_user_phone_field')) {
        $a->render_user_phone_field($user);
    }
}, 20);

add_action('edit_user_profile', function ($user) {
    if (is_numeric($user)) $user = \get_user_by('ID', (int)$user);
    $p = rsd_fp_init();
    $a = $p->get_component('admin');
    if ($a && method_exists($a, 'render_user_phone_field')) {
        $a->render_user_phone_field($user);
    }
}, 20);

add_action('personal_options_update', function ($user_id) {
    $p = rsd_fp_init();
    $a = $p->get_component('admin');
    if ($a && method_exists($a, 'save_user_phone_field')) {
        $a->save_user_phone_field($user_id);
    }
}, 20);

add_action('edit_user_profile_update', function ($user_id) {
    $p = rsd_fp_init();
    $a = $p->get_component('admin');
    if ($a && method_exists($a, 'save_user_phone_field')) {
        $a->save_user_phone_field($user_id);
    }
}, 20);

add_action('pre_user_query', function ($query) {
    $p = rsd_fp_init();
    $a = $p->get_component('admin');
    if ($a && method_exists($a, 'extend_user_search_by_phone')) {
        $a->extend_user_search_by_phone($query);
    }
}, 20);
