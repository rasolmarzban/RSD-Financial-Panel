<?php

/**
 * Plugin Activation class
 *
 * Handles all tasks required during plugin activation
 *
 * @package RSD_Finance_Panel
 * @subpackage Core
 */

namespace RSD\FinancePanel\Core;

defined('ABSPATH') || exit;

class Activator
{
    /**
     * Run activation tasks
     *
     * @return void
     */
    public static function activate()
    {
        // Create custom database tables
        Database::create_tables();

        // Create required pages
        self::create_pages();

        // Set default options
        self::set_default_options();

        // Register user roles and capabilities
        self::register_roles();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Mark plugin as installed
        update_option('rsd_fp_installed', time());
        update_option('rsd_fp_version', RSD_FP_VERSION);
        update_option('rsd_fp_db_version', Database::DB_VERSION);
    }

    /**
     * Create required frontend pages for the user portal
     *
     * @return void
     */
    private static function create_pages()
    {
        $pages = [
            [
                'title'     => esc_html__('Finance Dashboard', 'rsd-finance-panel'),
                'slug'      => 'finance-dashboard',
                'content'   => '[rsd_fp_dashboard]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'dashboard',
            ],
            [
                'title'     => esc_html__('Documents', 'rsd-finance-panel'),
                'slug'      => 'finance-documents',
                'content'   => '[rsd_fp_documents]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'documents',
            ],
            [
                'title'     => esc_html__('Messages & Tickets', 'rsd-finance-panel'),
                'slug'      => 'finance-tickets',
                'content'   => '[rsd_fp_tickets]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'tickets',
            ],
            [
                'title'     => esc_html__('Company Information', 'rsd-finance-panel'),
                'slug'      => 'finance-company-info',
                'content'   => '[rsd_fp_company_info]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'company_info',
            ],
            [
                'title'     => esc_html__('Reports', 'rsd-finance-panel'),
                'slug'      => 'finance-reports',
                'content'   => '[rsd_fp_reports]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'reports',
            ],
            [
                'title'     => esc_html__('Invoices', 'rsd-finance-panel'),
                'slug'      => 'finance-invoices',
                'content'   => '[rsd_fp_invoices]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'invoices',
            ],
            [
                'title'     => esc_html__('New Services', 'rsd-finance-panel'),
                'slug'      => 'finance-services',
                'content'   => '[rsd_fp_services]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'services',
            ],
            [
                'title'     => esc_html__('Calendar & Deadlines', 'rsd-finance-panel'),
                'slug'      => 'finance-calendar',
                'content'   => '[rsd_fp_calendar]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'calendar',
            ],
            [
                'title'     => esc_html__('Finance Panel Login & Registration', 'rsd-finance-panel'),
                'slug'      => 'finance-auth',
                'content'   => '[rsd_fp_auth]',
                'meta_key'  => '_rsd_fp_page_type',
                'meta_val'  => 'auth',
            ],
        ];

        $page_ids = get_option('rsd_fp_page_ids', []);

        foreach ($pages as $page) {
            $existing = get_page_by_path($page['slug']);

            if (!$existing) {
                $page_id = wp_insert_post([
                    'post_title'     => $page['title'],
                    'post_name'      => $page['slug'],
                    'post_content'   => $page['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'post_author'    => 1,
                    'comment_status' => 'closed',
                ]);

                if ($page_id && !is_wp_error($page_id)) {
                    update_post_meta($page_id, $page['meta_key'], $page['meta_val']);
                    $page_ids[$page['meta_val']] = $page_id;
                }
            } else {
                $page_ids[$page['meta_val']] = $existing->ID;
            }
        }

        update_option('rsd_fp_page_ids', $page_ids);
    }

    /**
     * Set default plugin options
     *
     * @return void
     */
    private static function set_default_options()
    {
        $defaults = [
            'currency_symbol'   => '$',
            'currency_position' => 'before',
            'date_format'       => 'gregorian',
            'items_per_page'    => 20,
            'document_categories' => [
                'invoice'       => esc_html__('Invoice', 'rsd-finance-panel'),
                'receipt'       => esc_html__('Receipt', 'rsd-finance-panel'),
                'contract'      => esc_html__('Contract', 'rsd-finance-panel'),
                'tax_document'  => esc_html__('Tax Document', 'rsd-finance-panel'),
                'payroll'       => esc_html__('Payroll', 'rsd-finance-panel'),
                'other'         => esc_html__('Other', 'rsd-finance-panel'),
            ],
            'ticket_categories' => [
                'accounting'    => esc_html__('Accounting', 'rsd-finance-panel'),
                'tax'           => esc_html__('Tax', 'rsd-finance-panel'),
                'payroll'       => esc_html__('Payroll', 'rsd-finance-panel'),
                'employees'     => esc_html__('Employees', 'rsd-finance-panel'),
                'invoice'       => esc_html__('Invoice', 'rsd-finance-panel'),
                'documents'     => esc_html__('Documents', 'rsd-finance-panel'),
                'registration'  => esc_html__('Company Registration', 'rsd-finance-panel'),
                'other'         => esc_html__('Other', 'rsd-finance-panel'),
            ],
            'report_types' => [
                'accounting'    => esc_html__('Accounting Report', 'rsd-finance-panel'),
                'income_expense' => esc_html__('Income & Expense Report', 'rsd-finance-panel'),
                'tax'           => esc_html__('Tax Report', 'rsd-finance-panel'),
                'vat'           => esc_html__('VAT Report', 'rsd-finance-panel'),
                'payroll'       => esc_html__('Payroll Report', 'rsd-finance-panel'),
                'annual'        => esc_html__('Annual Reports', 'rsd-finance-panel'),
                'management'    => esc_html__('Management Reports', 'rsd-finance-panel'),
                'other'         => esc_html__('Other Reports', 'rsd-finance-panel'),
            ],
            'calendar_categories' => [
                'tax_deadline'      => esc_html__('Tax Deadline', 'rsd-finance-panel'),
                'document_due'      => esc_html__('Document Submission Deadline', 'rsd-finance-panel'),
                'payment_due'       => esc_html__('Payment Due', 'rsd-finance-panel'),
                'payroll'           => esc_html__('Payroll', 'rsd-finance-panel'),
                'meeting'           => esc_html__('Meetings', 'rsd-finance-panel'),
                'company_affairs'   => esc_html__('Company Affairs', 'rsd-finance-panel'),
                'client_request'    => esc_html__('Client Request', 'rsd-finance-panel'),
                'other'             => esc_html__('Other', 'rsd-finance-panel'),
            ],
            'auth_enabled'              => '1',
            'auth_otp_enabled'          => '',
            'auth_sms_provider'         => 'faraz_sms',
            'auth_faraz_username'       => '',
            'auth_faraz_password'       => '',
            'auth_faraz_sender'         => '',
            'auth_password_min_length'  => 8,
            'auth_public_registration'  => '1',
        ];

        $existing = get_option('rsd_fp_settings', []);
        $settings = wp_parse_args($existing, $defaults);

        update_option('rsd_fp_settings', $settings);
    }

    /**
     * Register custom user roles and capabilities
     *
     * @return void
     */
    private static function register_roles()
    {
        // Add RSD Client role (for portal users)
        add_role(
            'rsd_client',
            esc_html__('RSD Client', 'rsd-finance-panel'),
            [
                'read'                      => true,
                'rsd_fp_view_dashboard'     => true,
                'rsd_fp_upload_documents'   => true,
                'rsd_fp_create_tickets'     => true,
                'rsd_fp_view_own_tickets'   => true,
                'rsd_fp_view_own_invoices'  => true,
                'rsd_fp_view_own_reports'   => true,
                'rsd_fp_edit_own_profile'   => true,
                'rsd_fp_request_services'   => true,
                'rsd_fp_view_own_calendar'  => true,
            ]
        );

        // Add capabilities to admin
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('rsd_fp_manage_all');
            $admin->add_cap('rsd_fp_view_all_clients');
            $admin->add_cap('rsd_fp_manage_documents');
            $admin->add_cap('rsd_fp_manage_tickets');
            $admin->add_cap('rsd_fp_manage_invoices');
            $admin->add_cap('rsd_fp_manage_reports');
            $admin->add_cap('rsd_fp_manage_services');
            $admin->add_cap('rsd_fp_manage_calendar');
            $admin->add_cap('rsd_fp_manage_settings');
            $admin->add_cap('rsd_fp_view_dashboard');
        }
    }
}
