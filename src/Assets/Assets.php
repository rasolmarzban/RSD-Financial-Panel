<?php

/**
 * Assets class
 *
 * Manages all CSS and JavaScript enqueueing
 *
 * @package RSD_Finance_Panel
 * @subpackage Assets
 */

namespace RSD\FinancePanel\Assets;

defined('ABSPATH') || exit;

class Assets
{
    /**
     * Initialize asset hooks
     *
     * @return void
     */
    public function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
    }

    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function frontend_assets()
    {
        if (!$this->is_portal_page()) {
            return;
        }

        // Main dashboard CSS (LTR)
        wp_enqueue_style(
            'rsd-fp-dashboard',
            RSD_FP_PLUGIN_URL . 'assets/css/dashboard.css',
            [],
            RSD_FP_VERSION
        );

        // Main JS
        wp_enqueue_script(
            'rsd-fp-dashboard',
            RSD_FP_PLUGIN_URL . 'assets/js/dashboard.js',
            ['jquery'],
            RSD_FP_VERSION,
            true
        );

        wp_localize_script('rsd-fp-dashboard', 'RSD_FP', [
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('rsd_fp_nonce'),
            'dashboard_url' => rsd_fp_page_url('dashboard'),
            'i18n' => [
                'confirm'          => esc_html__('Are you sure?', 'rsd-finance-panel'),
                'upload_document'  => esc_html__('Upload Document', 'rsd-finance-panel'),
                'processing'       => esc_html__('Processing...', 'rsd-finance-panel'),
                'success'          => esc_html__('Success', 'rsd-finance-panel'),
                'error'            => esc_html__('Error', 'rsd-finance-panel'),
                'close'            => esc_html__('Close', 'rsd-finance-panel'),
                'save'             => esc_html__('Save', 'rsd-finance-panel'),
                'cancel'           => esc_html__('Cancel', 'rsd-finance-panel'),
                'delete'           => esc_html__('Delete', 'rsd-finance-panel'),
                'submit'           => esc_html__('Submit', 'rsd-finance-panel'),
                'select_file'      => esc_html__('Select File', 'rsd-finance-panel'),
            ],
        ]);

        if ($this->is_auth_page()) {
            $auth = rsd_fp_setting('auth_enabled', '1') ? new \RSD\FinancePanel\Modules\Auth() : null;
            $otp_enabled      = $auth ? $auth->is_otp_enabled()      : false;
            $public_reg       = $auth ? $auth->is_public_registration() : true;
            $password_min_len = $auth ? $auth->get_password_min_length() : 8;

            wp_enqueue_script(
                'rsd-fp-auth',
                RSD_FP_PLUGIN_URL . 'assets/js/auth.js',
                ['jquery'],
                RSD_FP_VERSION,
                true
            );

            wp_localize_script('rsd-fp-auth', 'RSD_FP_AUTH', [
                'ajax_url'              => admin_url('admin-ajax.php'),
                'nonce'                 => wp_create_nonce('rsd_fp_nonce'),
                'dashboard_url'         => rsd_fp_page_url('dashboard'),
                'auth_url'              => rsd_fp_page_url('auth'),
                'otp_enabled'           => $otp_enabled,
                'public_registration'   => $public_reg,
                'password_min_length'   => $password_min_len,
                'i18n' => [
                    'processing'           => esc_html__('Processing...', 'rsd-finance-panel'),
                    'error'                => esc_html__('An error occurred.', 'rsd-finance-panel'),
                    'success'              => esc_html__('Operation successful', 'rsd-finance-panel'),
                    'network_error'        => esc_html__('Server connection error. Please try again.', 'rsd-finance-panel'),
                    'fill_required'        => esc_html__('Please fill in the required fields.', 'rsd-finance-panel'),
                    'phone_required'       => esc_html__('Phone number is required.', 'rsd-finance-panel'),
                    'invalid_email'        => esc_html__('The entered email is not valid.', 'rsd-finance-panel'),
                    'enter_id'             => esc_html__('Please enter your phone number or email.', 'rsd-finance-panel'),
                    'password_short'       => esc_html__('Password must be at least %d characters long.', 'rsd-finance-panel'),
                    'password_mismatch'    => esc_html__('Password and its confirmation do not match.', 'rsd-finance-panel'),
                    'enter_otp'            => esc_html__('Please enter the verification code.', 'rsd-finance-panel'),
                    'resend_code'          => esc_html__('Resend code', 'rsd-finance-panel'),
                    'resend_in'            => esc_html__('Resend in', 'rsd-finance-panel'),
                    'logged_out'           => esc_html__('You have been logged out successfully.', 'rsd-finance-panel'),
                    'pw_reset_success'     => esc_html__('Your password was changed successfully. You can now log in.', 'rsd-finance-panel'),
                    'key_expired'          => esc_html__('The reset link has expired. Please request a new one.', 'rsd-finance-panel'),
                    'key_invalid'          => esc_html__('The reset link is not valid.', 'rsd-finance-panel'),
                    'invalid_reset_request' => esc_html__('The reset request is not valid.', 'rsd-finance-panel'),
                ],
            ]);
        }
    }

    /**
     * Check if current page is the auth (login/register) portal page.
     *
     * @return bool
     */
    private function is_auth_page()
    {
        if (is_singular('page')) {
            $types = ['auth'];
            foreach ($types as $t) {
                if (strpos(get_the_content(), "[rsd_fp_{$t}]") !== false) {
                    return true;
                }
            }
            $page_ids = get_option('rsd_fp_page_ids', []);
            if (!empty($page_ids['auth']) && (int)$page_ids['auth'] === get_the_ID()) {
                return true;
            }
        }
        return apply_filters('rsd_fp_is_auth_page', false);
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook
     * @return void
     */
    public function admin_assets($hook)
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_plugin_page = $screen && strpos($screen->id, 'rsd-finance-panel') !== false;

        if (!$is_plugin_page && strpos($hook, 'rsd_finance_panel') === false) {
            // Still load on main pages list for screen options etc
            if (!isset($_GET['page']) || strpos($_GET['page'], 'rsd-') !== 0) {
                return;
            }
        }

        wp_enqueue_style(
            'rsd-fp-admin',
            RSD_FP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            RSD_FP_VERSION
        );

        wp_enqueue_script(
            'rsd-fp-admin',
            RSD_FP_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-color-picker'],
            RSD_FP_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');

        wp_localize_script('rsd-fp-admin', 'RSD_FP_ADMIN', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('rsd_fp_nonce'),
            'i18n' => [
                'confirm'  => esc_html__('Are you sure?', 'rsd-finance-panel'),
                'saving'   => esc_html__('Saving...', 'rsd-finance-panel'),
                'saved'    => esc_html__('Saved successfully.', 'rsd-finance-panel'),
                'error'    => esc_html__('An error occurred.', 'rsd-finance-panel'),
            ],
        ]);
    }

    /**
     * Check if current page is a finance portal page
     *
     * @return bool
     */
    private function is_portal_page()
    {
        if (is_singular('page')) {
            $types = ['dashboard', 'documents', 'tickets', 'company_info', 'reports', 'invoices', 'services', 'calendar', 'auth'];
            foreach ($types as $t) {
                if (strpos(get_the_content(), "[rsd_fp_{$t}]") !== false) {
                    return true;
                }
            }

            $page_ids = get_option('rsd_fp_page_ids', []);
            if (in_array(get_the_ID(), $page_ids, true)) {
                return true;
            }
        }

        // Allow developers to override
        return apply_filters('rsd_fp_is_portal_page', false);
    }
}
