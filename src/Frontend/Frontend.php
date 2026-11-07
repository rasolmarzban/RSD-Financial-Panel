<?php

/**
 * Frontend class
 *
 * Handles all user-side (frontend) portal functionality including shortcodes
 *
 * @package RSD_Finance_Panel
 * @subpackage Frontend
 */

namespace RSD\FinancePanel\Frontend;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Plugin;
use RSD\FinancePanel\Core\Database;

class Frontend
{
    /**
     * Current user ID (client ID for this request)
     *
     * @var int
     */
    private $client_id = 0;

    /**
     * Initialize frontend hooks
     *
     * @return void
     */
    public function init()
    {
        // Get client ID once for this request
        add_action('template_redirect', [$this, 'resolve_client_id']);

        // Register all shortcodes
        add_action('init', [$this, 'register_shortcodes']);

        // Add body class for portal pages
        add_filter('body_class', [$this, 'body_class']);

        // Maybe redirect non-authorized users from portal pages
        add_action('template_redirect', [$this, 'maybe_restrict_portal']);

        // Filter page template for portal pages (optional)
        add_filter('template_include', [$this, 'filter_portal_template']);
    }

    /**
     * Resolve the current client ID
     *
     * @return void
     */
    public function resolve_client_id()
    {
        $this->client_id = Plugin::get_current_client_id();
    }

    /**
     * Register all frontend shortcodes
     *
     * @return void
     */
    public function register_shortcodes()
    {
        add_shortcode('rsd_fp_dashboard',    [$this, 'render_dashboard']);
        add_shortcode('rsd_fp_documents',    [$this, 'render_documents']);
        add_shortcode('rsd_fp_tickets',      [$this, 'render_tickets']);
        add_shortcode('rsd_fp_company_info', [$this, 'render_company_info']);
        add_shortcode('rsd_fp_reports',      [$this, 'render_reports']);
        add_shortcode('rsd_fp_invoices',     [$this, 'render_invoices']);
        add_shortcode('rsd_fp_services',     [$this, 'render_services']);
        add_shortcode('rsd_fp_calendar',     [$this, 'render_calendar']);
        add_shortcode('rsd_fp_profile',      [$this, 'render_profile']);
        add_shortcode('rsd_fp_auth',         [$this, 'render_auth']);
    }

    /**
     * Add custom body classes to portal pages
     *
     * @param array $classes
     * @return array
     */
    public function body_class($classes)
    {
        if (is_singular('page')) {
            $page_ids = get_option('rsd_fp_page_ids', []);
            $pid = get_the_ID();
            $match = false;
            if (in_array($pid, $page_ids, true)) {
                $match = true;
            } else {
                $content = get_post_field('post_content', $pid);
                if (strpos($content, '[rsd_fp_auth]') !== false) {
                    $match = true;
                }
            }
            if ($match) {
                $classes[] = 'rsd-fp-portal';
            }
        }
        return $classes;
    }

    /**
     * Redirect non-authorized users attempting to access portal pages.
     *
     * — Auth page itself is ALWAYS exempted so guests can log in.
     * — Guests: when auth_enabled → custom auth page with redirect_to.
     *            otherwise → core wp_login_url.
     * — Logged-in but non-client: wp_die() (403).
     *
     * @return void
     */
    public function maybe_restrict_portal()
    {
        if (!is_singular('page')) {
            return;
        }

        $pid = get_the_ID();
        $page_ids = get_option('rsd_fp_page_ids', []);
        $is_portal = in_array($pid, $page_ids, true);
        if (!$is_portal) {
            $content = get_post_field('post_content', $pid);
            $shortcodes = [
                'rsd_fp_dashboard',
                'rsd_fp_documents',
                'rsd_fp_tickets',
                'rsd_fp_company_info',
                'rsd_fp_reports',
                'rsd_fp_invoices',
                'rsd_fp_services',
                'rsd_fp_calendar',
                'rsd_fp_profile',
                'rsd_fp_auth'
            ];
            foreach ($shortcodes as $s) {
                if (strpos($content, '[' . $s . ']') !== false) {
                    $is_portal = true;
                    break;
                }
            }
        }
        if (!$is_portal) {
            return;
        }

        $is_auth_page = (!empty($page_ids['auth']) && (int)$page_ids['auth'] === $pid)
            || (strpos(get_post_field('post_content', $pid), '[rsd_fp_auth]') !== false);

        $auth_component = rsd_fp_setting('auth_enabled', '1')
            ? (rsd_fp() ? rsd_fp()->get_component('auth') : null)
            : null;
        $auth_enabled = $auth_component && $auth_component->is_auth_enabled();

        if ($is_auth_page) {
            return;
        }

        if (!is_user_logged_in()) {
            if ($auth_enabled) {
                $auth_url = Plugin::get_page_url('auth');
                if ($auth_url) {
                    $auth_url = add_query_arg('redirect_to', urlencode(get_permalink()), $auth_url);
                    wp_redirect($auth_url);
                    exit;
                }
            }
            $login_url = wp_login_url(get_permalink());
            wp_redirect($login_url);
            exit;
        }

        if (!Plugin::is_client_user()) {
            wp_die(
                esc_html__('You do not have permission to view this page.', 'rsd-finance-panel'),
                esc_html__('Access Denied', 'rsd-finance-panel'),
                ['response' => 403, 'back_link' => true]
            );
        }
    }

    /**
     * Optionally filter the page template for portal pages
     *
     * @param string $template
     * @return string
     */
    public function filter_portal_template($template)
    {
        if (!is_singular('page')) {
            return $template;
        }

        $page_ids = get_option('rsd_fp_page_ids', []);
        if (!in_array(get_the_ID(), $page_ids, true)) {
            return $template;
        }

        // Check if theme overrides exist or custom template provided
        $custom = RSD_FP_PLUGIN_DIR . 'templates/frontend/portal-wrapper.php';
        if (file_exists($custom) && apply_filters('rsd_fp_use_custom_template', false)) {
            return $custom;
        }

        return $template;
    }

    /**
     * Build the JS snippet that assigns documentsById to the existing window.RSD_FP object.
     * Used via wp_add_inline_script() so it runs AFTER the base RSD_FP object is created
     * by wp_localize_script, preventing the race condition where an inline <script>
     * in the page content first creates RSD_FP = {} (only containing documentsById),
     * then wp_localize_script's `var RSD_FP = {...}` overwrites it completely.
     *
     * Uses a simple manual for-in merge instead of Object.assign to stay compatible
     * with older browsers (e.g. IE11 / older Safari on mobiles).
     *
     * @param array $map   [ int docId => doc ]
     * @return string
     */
    private function docs_by_id_js($map)
    {
        $json = wp_json_encode($map, JSON_UNESCAPED_UNICODE);
        return "(function(){\n"
            . "  window.RSD_FP = window.RSD_FP || {};\n"
            . "  var existing = window.RSD_FP.documentsById || {};\n"
            . "  var incoming = " . $json . ";\n"
            . "  var merged = {};\n"
            . "  var k;\n"
            . "  for (k in existing) { if (existing.hasOwnProperty(k)) merged[k] = existing[k]; }\n"
            . "  for (k in incoming) { if (incoming.hasOwnProperty(k)) merged[k] = incoming[k]; }\n"
            . "  window.RSD_FP.documentsById = merged;\n"
            . "})();\n";
    }

    // =============== SHORTCODE RENDERERS ===============

    /**
     * Render dashboard shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_dashboard($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;
        global $wpdb;

        // Stats
        $invoices_table = Database::table('invoices');
        $payments_table = Database::table('payments');
        $docs_table = Database::table('documents');
        $tickets_table = Database::table('tickets');
        $reports_table = Database::table('reports');
        $calendar_table = Database::table('calendar');
        $notifications_table = Database::table('notifications');

        $stats = [
            'total_invoiced'  => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total_amount),0) FROM {$invoices_table} WHERE user_id = %d",
                $client_id
            )),
            'total_paid'      => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(paid_amount),0) FROM {$invoices_table} WHERE user_id = %d",
                $client_id
            )),
            'outstanding'     => 0,
            'last_invoice'    => $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$invoices_table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
                $client_id
            )),
            'last_payment'    => $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$payments_table} WHERE user_id = %d ORDER BY payment_date DESC LIMIT 1",
                $client_id
            )),
            'incomplete_docs' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$docs_table} WHERE user_id = %d AND is_incomplete = 1",
                $client_id
            )),
            'unread_tickets'  => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT t.id) FROM {$tickets_table} t 
                 INNER JOIN " . Database::table('ticket_messages') . " m ON m.ticket_id = t.id 
                 WHERE t.user_id = %d AND m.user_id != %d AND m.is_read = 0",
                $client_id,
                $client_id
            )),
        ];

        // Previous month MoM calculations (Gregorian previous calendar month)
        $stats['total_paid_prev'] = (float) $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(amount),0) FROM {$payments_table}
            WHERE user_id = %d
              AND YEAR(payment_date)  = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
              AND MONTH(payment_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
        ", $client_id));
        $stats['total_invoiced_prev'] = (float) $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(total_amount),0) FROM {$invoices_table}
            WHERE user_id = %d
              AND YEAR(invoice_date)  = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
              AND MONTH(invoice_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
        ", $client_id));

        $prev_paid    = (float) $stats['total_paid_prev'];
        $prev_inv     = (float) $stats['total_invoiced_prev'];
        $cur_paid     = (float) $stats['total_paid'];
        $cur_inv      = (float) $stats['total_invoiced'];

        $stats['paid_change_pct'] = null;
        $stats['invoiced_change_pct'] = null;
        if ($prev_paid > 0) {
            $stats['paid_change_pct'] = round((($cur_paid - $prev_paid) / $prev_paid) * 100);
        } elseif ($cur_paid > 0) {
            $stats['paid_change_pct'] = 100;
        }
        if ($prev_inv > 0) {
            $stats['invoiced_change_pct'] = round((($cur_inv - $prev_inv) / $prev_inv) * 100);
        } elseif ($cur_inv > 0) {
            $stats['invoiced_change_pct'] = 100;
        }

        $stats['up_down_paid'] = ($stats['paid_change_pct'] === null) ? '' : ($stats['paid_change_pct'] >= 0 ? '↑' : '↓');
        $stats['up_down_inv']  = ($stats['invoiced_change_pct'] === null) ? '' : ($stats['invoiced_change_pct'] >= 0 ? '↑' : '↓');
        $stats['paid_color_class'] = ($stats['paid_change_pct'] === null) ? '' : ($stats['paid_change_pct'] >= 0 ? 'stat-positive' : 'stat-negative');
        $stats['inv_color_class']  = ($stats['invoiced_change_pct'] === null) ? '' : ($stats['invoiced_change_pct'] >= 0 ? 'stat-positive' : 'stat-negative');

        // Invoice status buckets for donut chart (paid / partial / unpaid / overdue)
        $all_invoices = $wpdb->get_results($wpdb->prepare("
            SELECT total_amount, paid_amount, status, due_date FROM {$invoices_table} WHERE user_id = %d
        ", $client_id));
        $today_ts = strtotime(current_time('Y-m-d'));
        $paid_sum = 0;
        $partial_sum = 0;
        $unpaid_sum = 0;
        $overdue_sum = 0;
        foreach ((array) $all_invoices as $iv) {
            $t = (float) $iv->total_amount;
            $p = (float) $iv->paid_amount;
            $s = $iv->status;
            $due = $iv->due_date ? strtotime($iv->due_date) : false;
            $is_overdue = ($s !== 'paid') && $due && ($due < $today_ts);
            if ($s === 'paid') {
                $paid_sum += $t;
            } elseif ($s === 'partial') {
                $partial_sum += $t;
            } elseif ($is_overdue) {
                $overdue_sum += $t;
            } else {
                $unpaid_sum += $t;
            }
        }
        $stats['invoice_buckets'] = [
            'paid'    => $paid_sum,
            'partial' => $partial_sum,
            'unpaid'  => $unpaid_sum,
            'overdue' => $overdue_sum,
        ];

        $stats['incomplete_list'] = Database::get_rows('documents', [
            'user_id'       => $client_id,
            'is_incomplete' => 1,
        ], 'created_at', 'DESC', 10);
        $stats['all_client_docs'] = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$docs_table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 500",
            $client_id
        ));
        $stats['latest_msgs'] = $wpdb->get_results($wpdb->prepare("
            SELECT m.*, t.subject, t.ticket_number,
                   CASE WHEN m.user_id = %d THEN 0 ELSE 1 END AS is_incoming
             FROM " . Database::table('ticket_messages') . " m
             INNER JOIN {$tickets_table} t ON t.id = m.ticket_id
             WHERE t.user_id = %d
             ORDER BY m.created_at DESC
             LIMIT 5
        ", $client_id, $client_id));
        $stats['upcoming_events'] = rsd_fp_merge_calendar_and_doc_events(
            $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$calendar_table} 
                 WHERE user_id = %d AND status != 'rejected' AND start_date >= %s 
                 ORDER BY start_date ASC 
                 LIMIT 5",
                $client_id,
                current_time('mysql')
            )) ?: [],
            rsd_fp_documents_as_events($client_id, ['only_upcoming' => true, 'limit' => 5]),
            5
        );
        $stats['mini_calendar_events'] = rsd_fp_merge_calendar_and_doc_events(
            $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$calendar_table} 
                 WHERE user_id = %d AND status != 'rejected'
                 ORDER BY start_date ASC 
                 LIMIT 200",
                $client_id
            )) ?: [],
            rsd_fp_documents_as_events($client_id, ['limit' => 200]),
            null
        );
        $stats['recent_reports'] = Database::get_rows('reports', ['user_id' => $client_id], 'created_at', 'DESC', 5);
        $stats['notifications'] = Database::get_rows('notifications', [
            'user_id' => $client_id,
            'is_read' => 0,
        ], 'created_at', 'DESC', 10);
        $stats['all_notif_count'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$notifications_table} WHERE user_id = %d AND is_read = 0",
            $client_id
        ));

        $stats['outstanding'] = $stats['total_invoiced'] - $stats['total_paid'];
        $stats['debt'] = $stats['outstanding'] > 0 ? $stats['outstanding'] : 0;

        $current_user = wp_get_current_user();

        // Build document-id -> full row map; attach to JS via wp_add_inline_script
        // so it runs AFTER wp_localize_script has assigned the base RSD_FP object
        // (otherwise the inline <script> in content would create a partial RSD_FP
        // that var RSD_FP = { ... } later overwrites, losing documentsById).
        $cats_map = \RSD\FinancePanel\Modules\Documents::get_categories();
        $docs_for_js = [];
        foreach (($stats['all_client_docs'] ?? []) as $d) {
            $docs_for_js[(int)$d->id] = [
                'id'             => (int)$d->id,
                'title'          => $d->title ?? '',
                'category'       => $d->category ?? '',
                'category_label' => $cats_map[$d->category] ?? ($d->category ?? ''),
                'year'           => $d->year ?? '',
                'month'          => $d->month ?? '',
                'period'         => $d->period ?? '',
                'description'    => $d->description ?? '',
                'expiry_date'    => $d->expiry_date ?? '',
                'file_path'      => $d->file_path ?? '',
                'file_name'      => $d->file_name ?? basename($d->file_path ?? ''),
                'file_size'      => isset($d->file_size) ? (int)$d->file_size : 0,
                'created_at'     => $d->created_at ?? '',
            ];
        }
        wp_add_inline_script('rsd-fp-dashboard', $this->docs_by_id_js($docs_for_js));

        return rsd_fp_get_template('frontend/dashboard', [
            'stats'        => $stats,
            'current_user' => $current_user,
            'client_id'    => $client_id,
        ], false);
    }

    /**
     * Render documents shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_documents($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;
        global $wpdb;
        $docs_table = Database::table('documents');

        $tab = $_GET['tab'] ?? 'all';

        $where = [$wpdb->prepare('user_id = %d', $client_id)];
        if ($tab === 'incomplete') {
            $where[] = 'is_incomplete = 1';
        } elseif ($tab === 'tax') {
            $where[] = "category IN ('tax_document','other')";
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where);
        $documents = $wpdb->get_results("SELECT * FROM {$docs_table} {$where_sql} ORDER BY created_at DESC LIMIT 200");

        // Build id -> full row map and attach it AFTER the localized RSD_FP base object
        // is printed, so we MERGE with existing ajax_url/nonce instead of overwriting.
        $cats_map = \RSD\FinancePanel\Modules\Documents::get_categories();
        $docs_for_js = [];
        foreach ($documents as $d) {
            $docs_for_js[(int)$d->id] = [
                'id'            => (int)$d->id,
                'title'         => $d->title ?? '',
                'category'      => $d->category ?? '',
                'category_label' => $cats_map[$d->category] ?? ($d->category ?? ''),
                'year'          => $d->year ?? '',
                'month'         => $d->month ?? '',
                'period'        => $d->period ?? '',
                'description'   => $d->description ?? '',
                'expiry_date'   => $d->expiry_date ?? '',
                'file_path'     => $d->file_path ?? '',
                'file_name'     => $d->file_name ?? basename($d->file_path ?? ''),
                'file_size'     => isset($d->file_size) ? (int)$d->file_size : 0,
                'created_at'    => $d->created_at ?? '',
            ];
        }
        wp_add_inline_script('rsd-fp-dashboard', $this->docs_by_id_js($docs_for_js));

        return rsd_fp_get_template('frontend/documents', [
            'documents'  => $documents,
            'client_id'  => $client_id,
            'active_tab' => $tab,
        ], false);
    }

    /**
     * Render tickets/messages shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_tickets($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;
        $view = $_GET['view'] ?? 'list';
        $ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($view === 'single' && $ticket_id) {
            global $wpdb;
            $ticket = Database::get_row('tickets', $ticket_id);

            if (!$ticket || (int) $ticket->user_id !== $client_id) {
                return '<div class="rsd-alert rsd-alert-danger">' . esc_html__('Ticket not found.', 'rsd-finance-panel') . '</div>';
            }

            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, u.display_name as user_name 
                 FROM " . Database::table('ticket_messages') . " m
                 LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id
                 WHERE m.ticket_id = %d ORDER BY m.created_at ASC",
                $ticket_id
            ));

            return rsd_fp_get_template('frontend/ticket-single', [
                'ticket'   => $ticket,
                'messages' => $messages,
            ], false);
        }

        $tickets = Database::get_rows('tickets', ['user_id' => $client_id], 'created_at', 'DESC', 100);

        return rsd_fp_get_template('frontend/tickets', [
            'tickets'   => $tickets,
            'client_id' => $client_id,
        ], false);
    }

    /**
     * Render company info shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_company_info($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;
        global $wpdb;
        $table = Database::table('company_info');
        $info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $client_id));

        return rsd_fp_get_template('frontend/company-info', [
            'info'      => $info,
            'client_id' => $client_id,
        ], false);
    }

    /**
     * Render reports shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_reports($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;

        $year   = $_GET['year'] ?? '';
        $type   = $_GET['type'] ?? '';
        $month  = $_GET['month'] ?? '';

        $where = ['user_id' => $client_id];
        if ($year)  $where['year'] = $year;
        if ($type)  $where['report_type'] = $type;
        if ($month) $where['month'] = $month;

        $reports = Database::get_rows('reports', $where, 'created_at', 'DESC', 200);

        return rsd_fp_get_template('frontend/reports', [
            'reports'   => $reports,
            'client_id' => $client_id,
            'filters'   => compact('year', 'type', 'month'),
        ], false);
    }

    /**
     * Render invoices / billing shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_invoices($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;
        global $wpdb;

        $invoices_table = Database::table('invoices');
        $payments_table = Database::table('payments');

        $stats = [
            'total_invoiced' => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total_amount),0) FROM {$invoices_table} WHERE user_id = %d",
                $client_id
            )),
            'total_paid'     => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(paid_amount),0) FROM {$invoices_table} WHERE user_id = %d",
                $client_id
            )),
            'outstanding'    => 0,
        ];
        $stats['outstanding'] = $stats['total_invoiced'] - $stats['total_paid'];
        $stats['debt'] = $stats['outstanding'] > 0 ? $stats['outstanding'] : 0;

        $invoices = Database::get_rows('invoices', ['user_id' => $client_id], 'invoice_date', 'DESC', 200);
        $payments = Database::get_rows('payments', ['user_id' => $client_id], 'payment_date', 'DESC', 200);

        return rsd_fp_get_template('frontend/invoices', [
            'invoices'  => $invoices,
            'payments'  => $payments,
            'stats'     => $stats,
            'client_id' => $client_id,
        ], false);
    }

    /**
     * Render new services shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_services($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;
        $view = $_GET['svc'] ?? 'list';

        $my_requests = Database::get_rows('services', ['user_id' => $client_id], 'created_at', 'DESC', 100);

        $service_types = [
            'company_registration' => esc_html__('Company Registration', 'rsd-finance-panel'),
            'brand_registration'   => esc_html__('Brand Registration', 'rsd-finance-panel'),
            'tax_id_application'   => esc_html__('Tax ID Application', 'rsd-finance-panel'),
            'new_personnel_payroll' => esc_html__('Payroll Slip for New Personnel', 'rsd-finance-panel'),
            'employee_termination' => esc_html__('Employee Termination', 'rsd-finance-panel'),
            'other'                => esc_html__('Other Services', 'rsd-finance-panel'),
        ];

        return rsd_fp_get_template('frontend/services', [
            'client_id'     => $client_id,
            'my_requests'   => $my_requests,
            'active_service' => $view,
            'service_types' => $service_types,
        ], false);
    }

    /**
     * Render calendar shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_calendar($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        $client_id = $this->client_id;
        global $wpdb;
        $calendar_table = Database::table('calendar');

        $calendar_events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$calendar_table} 
             WHERE user_id = %d AND status != 'rejected'
             ORDER BY start_date ASC",
            $client_id
        )) ?: [];

        $doc_events = rsd_fp_documents_as_events($client_id);
        $events = rsd_fp_merge_calendar_and_doc_events($calendar_events, $doc_events);

        return rsd_fp_get_template('frontend/calendar', [
            'events'    => $events,
            'client_id' => $client_id,
        ], false);
    }

    /**
     * Render the profile / account settings shortcode.
     *
     * @param array $atts
     * @return string
     */
    public function render_profile($atts)
    {
        if (!$this->can_view()) return $this->no_permission_msg();

        return rsd_fp_get_template('frontend/profile', [
            'current_user' => wp_get_current_user(),
            'client_id'    => $this->client_id,
        ], false);
    }

    /**
     * Render the custom auth page (login / register / lost / reset).
     * Shortcode: [rsd_fp_auth]
     *
     * — If a client user is already logged in, redirect to dashboard.
     * — If admin is logged in, still show the page (so admin can preview / debug).
     * — Otherwise, include the auth template with the correct action + flash messages.
     *
     * @param array $atts  Optional shortcode attributes (unused currently).
     * @return string
     */
    public function render_auth($atts)
    {
        $auth_component = rsd_fp_setting('auth_enabled', '1')
            ? (rsd_fp() ? rsd_fp()->get_component('auth') : null)
            : null;

        $is_enabled = $auth_component && $auth_component->is_auth_enabled();

        if ($is_enabled && is_user_logged_in()) {
            $uid = get_current_user_id();
            $is_admin    = Plugin::is_admin_user($uid);
            $is_client   = Plugin::is_client_user($uid);
            if (!$is_admin && $is_client) {
                $dashboard = Plugin::get_page_url('dashboard');
                if ($dashboard) {
                    if (!headers_sent()) {
                        wp_redirect($dashboard);
                        exit;
                    }
                    $esc_dash = esc_url($dashboard);
                    return sprintf(
                        '<meta http-equiv="refresh" content="0; url=%s">'
                            . '<script type="text/javascript">if(window.location && window.location.replace){window.location.replace(%s);}else{window.location.href=%s;}</script>'
                            . '<div style="padding:24px;text-align:center;font-family:system-ui,-apple-system,sans-serif;">'
                            . '<p style="margin:0 0 8px;font-weight:600;">%s</p>'
                            . '<a href="%s" style="color:#2563eb;text-decoration:underline;">%s</a>'
                            . '</div>',
                        $esc_dash,
                        wp_json_encode($dashboard),
                        wp_json_encode($dashboard),
                        esc_html__('Redirecting to the user panel...', 'rsd-finance-panel'),
                        $esc_dash,
                        esc_html__('If you were not redirected, click here', 'rsd-finance-panel')
                    );
                }
            }
        }

        if (!$is_enabled) {
            $login = wp_login_url(get_permalink());
            return sprintf(
                '<div class="rsd-alert rsd-alert-info" style="padding:18px;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0;text-align:center;">
                    <p style="margin:0 0 8px;font-weight:600;color:#0f172a">%s</p>
                    <a href="%s" class="rsd-btn rsd-btn-primary" style="display:inline-flex;margin-top:4px">%s</a>
                </div>',
                esc_html__('The custom login and registration system is disabled. Please use the standard WordPress page.', 'rsd-finance-panel'),
                esc_url($login),
                esc_html__('Log in to your account', 'rsd-finance-panel')
            );
        }

        $action            = isset($_GET['action']) ? trim((string)$_GET['action']) : 'login';
        $allowed_actions   = ['login', 'register', 'lostpassword', 'lostpassword', 'resetpass', 'retrievepassword', 'rp'];
        if (!in_array($action, $allowed_actions, true)) {
            $action = 'login';
        }
        if ($action === 'retrievepassword') {
            $action = 'lostpassword';
        }
        if ($action === 'rp') {
            $action = 'resetpass';
        }

        $otp_enabled         = $auth_component ? $auth_component->is_otp_enabled()         : false;
        $public_registration = $auth_component ? $auth_component->is_public_registration() : true;
        $password_min_length = $auth_component ? $auth_component->get_password_min_length() : 8;

        $redirect_to = '';
        if (!empty($_GET['redirect_to'])) {
            $redirect_to = esc_url_raw(trim((string)$_GET['redirect_to']));
        } elseif (!empty($_REQUEST['redirect_to'])) {
            $redirect_to = esc_url_raw(trim((string)$_REQUEST['redirect_to']));
        } else {
            $redirect_to = Plugin::get_page_url('dashboard');
        }

        $reset_key   = isset($_GET['key'])   ? trim((string)$_GET['key'])   : '';
        $reset_login = isset($_GET['login']) ? trim((string)$_GET['login']) : '';

        $flash_message = '';
        $flash_type    = 'success';

        if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
            $flash_message = esc_html__('You have been logged out successfully.', 'rsd-finance-panel');
            $flash_type    = 'success';
        } elseif (isset($_GET['passwordreset']) && $_GET['passwordreset'] === 'true') {
            $flash_message = esc_html__('Your password was changed successfully. You can now log in.', 'rsd-finance-panel');
            $flash_type    = 'success';
        } elseif (isset($_GET['error']) && $_GET['error'] === 'expiredkey') {
            $flash_message = esc_html__('The password reset link has expired. Please request a new one.', 'rsd-finance-panel');
            $flash_type    = 'error';
            if ($action === 'resetpass') {
                $action = 'lostpassword';
            }
        } elseif (isset($_GET['error']) && $_GET['error'] === 'invalidkey') {
            $flash_message = esc_html__('The password reset link is not valid.', 'rsd-finance-panel');
            $flash_type    = 'error';
            if ($action === 'resetpass') {
                $action = 'lostpassword';
            }
        }

        $template_args = [
            'action'              => $action,
            'otp_enabled'         => (bool)$otp_enabled,
            'public_registration' => (bool)$public_registration,
            'password_min_length' => (int)$password_min_length,
            'redirect_to'         => (string)$redirect_to,
            'reset_key'           => (string)$reset_key,
            'reset_login'         => (string)$reset_login,
            'flash_message'       => (string)$flash_message,
            'flash_type'          => (string)$flash_type,
        ];

        return rsd_fp_get_template('frontend/auth', $template_args, false);
    }

    // =============== HELPERS ===============

    /**
     * Check if user can view the portal
     *
     * @return bool
     */
    private function can_view()
    {
        if (!is_user_logged_in()) return false;
        return Plugin::is_client_user();
    }

    /**
     * No permission message
     *
     * @return string
     */
    private function no_permission_msg()
    {
        if (!is_user_logged_in()) {
            $auth_component = rsd_fp_setting('auth_enabled', '1')
                ? (rsd_fp() ? rsd_fp()->get_component('auth') : null)
                : null;
            $auth_enabled = $auth_component && $auth_component->is_auth_enabled();
            if ($auth_enabled) {
                $login = Plugin::get_page_url('auth');
                if ($login) {
                    $login = add_query_arg('redirect_to', urlencode(get_permalink()), $login);
                }
            }
            if (empty($login)) {
                $login = wp_login_url(get_permalink());
            }
            return sprintf(
                '<div class="rsd-alert rsd-alert-info" style="padding:16px 18px;border-radius:14px;background:#dbeafe;border:1px solid #bfdbfe;color:#1e3a8a;">
                    %s
                    <a href="%s" style="font-weight:700;margin-inline-end:6px">%s</a>
                </div>',
                esc_html__('To access the client panel, you must first log in to your account.', 'rsd-finance-panel'),
                esc_url($login),
                esc_html__('Log in to your account', 'rsd-finance-panel')
            );
        }

        return sprintf(
            '<div class="rsd-alert rsd-alert-danger" style="padding:16px 18px;border-radius:14px;background:#fee2e2;border:1px solid #fecaca;color:#7f1d1d;">
                %s
            </div>',
            esc_html__('You do not have permission to access this section.', 'rsd-finance-panel')
        );
    }

    /**
     * Get the client ID for this request
     *
     * @return int
     */
    public function get_client_id()
    {
        return $this->client_id;
    }
}
