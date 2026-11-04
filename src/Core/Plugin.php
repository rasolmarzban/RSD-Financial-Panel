<?php

/**
 * Main Plugin class
 *
 * Orchestrates the plugin components and hooks them into WordPress
 *
 * @package RSD_Finance_Panel
 * @subpackage Core
 */

namespace RSD\FinancePanel\Core;

defined('ABSPATH') || exit;

class Plugin
{
    /**
     * Plugin components container
     *
     * @var array
     */
    private $components = [];

    /**
     * Run the plugin
     *
     * @return void
     */
    public function run()
    {
        // Check database version on admin
        add_action('plugins_loaded', [$this, 'check_database_version'], 10);

        // Load text domain
        add_action('init', [$this, 'load_textdomain']);

        // Register all components
        $this->register_components();

        // Initialize all components
        foreach ($this->components as $component) {
            if (method_exists($component, 'init')) {
                $component->init();
            }
        }
        // NOTE: User profile phone hooks (render/save/search) are registered
        // directly in the main plugin file rsd-finance-panel.php at global
        // PHP scope to ensure they fire regardless of is_admin() timing.
    }

    /**
     * Register all plugin components
     *
     * @return void
     */
    private function register_components()
    {
        // Assets manager
        $this->components['assets'] = new \RSD\FinancePanel\Assets\Assets();

        // AJAX handler
        $this->components['ajax'] = new \RSD\FinancePanel\Core\Ajax();

        // Admin side
        if (is_admin()) {
            $this->components['admin'] = new \RSD\FinancePanel\Admin\Admin();
        }

        // Frontend side
        if (!is_admin() || defined('DOING_AJAX')) {
            $this->components['frontend'] = new \RSD\FinancePanel\Frontend\Frontend();
        }

        // Module classes
        $this->components['documents'] = new \RSD\FinancePanel\Modules\Documents();
        $this->components['tickets'] = new \RSD\FinancePanel\Modules\Tickets();
        $this->components['invoices'] = new \RSD\FinancePanel\Modules\Invoices();
        $this->components['reports'] = new \RSD\FinancePanel\Modules\Reports();
        $this->components['services'] = new \RSD\FinancePanel\Modules\Services();
        $this->components['calendar'] = new \RSD\FinancePanel\Modules\Calendar();
        $this->components['company_info'] = new \RSD\FinancePanel\Modules\Company_Info();
        $this->components['auth'] = new \RSD\FinancePanel\Modules\Auth();
    }

    /**
     * Check if database is up to date
     *
     * @return void
     */
    public function check_database_version()
    {
        if (Database::needs_update()) {
            Database::create_tables();
        }
    }

    /**
     * Load plugin text domain
     *
     * @return void
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'rsd-finance-panel',
            false,
            dirname(RSD_FP_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Get a component from the container
     *
     * @param string $name
     * @return object|null
     */
    public function get_component($name)
    {
        return $this->components[$name] ?? null;
    }

    /**
     * Get plugin setting
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get_setting($key, $default = null)
    {
        $settings = get_option('rsd_fp_settings', []);
        return $settings[$key] ?? $default;
    }

    /**
     * Get current user's client ID (or user ID if already a client)
     *
     * @return int
     */
    public static function get_current_client_id()
    {
        $user_id = get_current_user_id();

        // You can add logic here to map a user to their parent client
        return (int) apply_filters('rsd_fp_current_client_id', $user_id);
    }

    /**
     * Check if user has admin access to finance panel
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_admin_user($user_id = null)
    {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return false;
        }

        return user_can($user_id, 'rsd_fp_manage_all') || user_can($user_id, 'manage_options');
    }

    /**
     * Check if user has client access to finance panel
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_client_user($user_id = null)
    {
        $user_id = $user_id ?? get_current_user_id();

        if (!$user_id) {
            return false;
        }

        return user_can($user_id, 'rsd_fp_view_dashboard') || self::is_admin_user($user_id);
    }

    /**
     * Get page URL for a portal page
     *
     * @param string $type
     * @return string
     */
    public static function get_page_url($type = 'dashboard')
    {
        $page_ids = get_option('rsd_fp_page_ids', []);
        $page_id = $page_ids[$type] ?? 0;

        if ($page_id && get_post($page_id)) {
            return get_permalink($page_id);
        }

        // Fallback to home
        return home_url('/');
    }

    /**
     * Format currency
     *
     * @param float $amount
     * @return string
     */
    public static function format_currency($amount)
    {
        $symbol = self::get_setting('currency_symbol', 'تومان');
        $position = self::get_setting('currency_position', 'after');
        $formatted = number_format_i18n((float) $amount, 0);

        if ($position === 'before') {
            return sprintf('%s %s', $symbol, $formatted);
        }

        return sprintf('%s %s', $formatted, $symbol);
    }

    /**
     * Add flash notice
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public static function add_notice($message, $type = 'success')
    {
        $notices = get_option('rsd_fp_notices', []);
        $notices[] = [
            'message' => $message,
            'type'    => $type,
            'time'    => time(),
        ];
        update_option('rsd_fp_notices', $notices);
    }

    /**
     * Get and clear flash notices
     *
     * @return array
     */
    public static function get_notices()
    {
        $notices = get_option('rsd_fp_notices', []);
        delete_option('rsd_fp_notices');
        return $notices;
    }
}
