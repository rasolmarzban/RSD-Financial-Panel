<?php

/**
 * Admin class
 *
 * Handles all WordPress admin-side functionality
 *
 * @package RSD_Finance_Panel
 * @subpackage Admin
 */

namespace RSD\FinancePanel\Admin;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Plugin;
use RSD\FinancePanel\Core\Database;

class Admin
{
    /**
     * Admin page hook suffixes
     *
     * @var array
     */
    private $page_hooks = [];

    /**
     * Initialize admin hooks
     *
     * @return void
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'show_notices']);
        add_filter('plugin_action_links_' . RSD_FP_PLUGIN_BASENAME, [$this, 'add_plugin_links']);

        // Admin-side user column additions
        add_filter('manage_users_columns', [$this, 'add_users_columns']);
        add_filter('manage_users_custom_column', [$this, 'render_users_columns'], 10, 3);

        // NOTE: User profile phone field (render/save) and users search by
        // phone are registered at global PHP scope in the main plugin file
        // (rsd-finance-panel.php) to guarantee hook capture regardless of
        // is_admin() timing during nested plugins_loaded bootstrapping.
    }

    /**
     * Add admin menu pages
     *
     * @return void
     */
    public function add_admin_menu()
    {
        $capability = 'rsd_fp_manage_all';
        if (!current_user_can($capability) && current_user_can('manage_options')) {
            $capability = 'manage_options';
        }

        // Main menu
        $this->page_hooks['dashboard'] = add_menu_page(
            esc_html__('RSD Finance Panel', 'rsd-finance-panel'),
            esc_html__('Finance Portal', 'rsd-finance-panel'),
            $capability,
            'rsd-finance-panel',
            [$this, 'render_dashboard_page'],
            'dashicons-chart-bar',
            25
        );

        // Clients submenu
        $this->page_hooks['clients'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Manage Clients', 'rsd-finance-panel'),
            esc_html__('Clients', 'rsd-finance-panel'),
            $capability,
            'rsd-fp-clients',
            [$this, 'render_clients_page']
        );

        // Documents submenu
        $this->page_hooks['documents'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Manage Documents', 'rsd-finance-panel'),
            esc_html__('Documents', 'rsd-finance-panel'),
            $capability,
            'rsd-fp-documents',
            [$this, 'render_documents_page']
        );

        // Tickets submenu
        $this->page_hooks['tickets'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Manage Tickets', 'rsd-finance-panel'),
            esc_html__('Tickets', 'rsd-finance-panel'),
            $capability,
            'rsd-fp-tickets',
            [$this, 'render_tickets_page']
        );

        // Invoices submenu
        $this->page_hooks['invoices'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Manage Invoices', 'rsd-finance-panel'),
            esc_html__('Invoices', 'rsd-finance-panel'),
            $capability,
            'rsd-fp-invoices',
            [$this, 'render_invoices_page']
        );

        // Reports submenu
        $this->page_hooks['reports'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Manage Reports', 'rsd-finance-panel'),
            esc_html__('Reports', 'rsd-finance-panel'),
            $capability,
            'rsd-fp-reports',
            [$this, 'render_reports_page']
        );

        // Services submenu
        $this->page_hooks['services'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Manage Service Requests', 'rsd-finance-panel'),
            esc_html__('Requests', 'rsd-finance-panel'),
            $capability,
            'rsd-fp-services',
            [$this, 'render_services_page']
        );

        // Calendar submenu
        $this->page_hooks['calendar'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Work Calendar', 'rsd-finance-panel'),
            esc_html__('Calendar', 'rsd-finance-panel'),
            $capability,
            'rsd-fp-calendar',
            [$this, 'render_calendar_page']
        );

        // Settings submenu
        $this->page_hooks['settings'] = add_submenu_page(
            'rsd-finance-panel',
            esc_html__('Plugin Settings', 'rsd-finance-panel'),
            esc_html__('Settings', 'rsd-finance-panel'),
            'manage_options',
            'rsd-fp-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     *
     * @return void
     */
    public function register_settings()
    {
        register_setting('rsd_fp_settings_group', 'rsd_fp_settings', [$this, 'sanitize_settings']);
    }

    /**
     * Sanitize settings on save
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input)
    {
        $clean = [];
        $existing = get_option('rsd_fp_settings', []);

        $clean['currency_symbol'] = sanitize_text_field($input['currency_symbol'] ?? 'Toman');
        $clean['currency_position'] = in_array($input['currency_position'] ?? 'after', ['before', 'after']) ? $input['currency_position'] : 'after';
        $clean['date_format'] = in_array($input['date_format'] ?? 'jalali', ['jalali', 'gregorian']) ? $input['date_format'] : 'jalali';
        $clean['items_per_page'] = absint($input['items_per_page'] ?? 20);

        // Auth settings
        $clean['auth_enabled']             = isset($input['auth_enabled'])            ? '1' : '';
        $clean['auth_otp_enabled']         = isset($input['auth_otp_enabled'])        ? '1' : '';
        $clean['auth_public_registration'] = isset($input['auth_public_registration']) ? '1' : '';
        $clean['auth_page_id']             = absint($input['auth_page_id'] ?? 0);
        $clean['auth_sms_provider']        = in_array($input['auth_sms_provider'] ?? 'faraz_sms', ['faraz_sms', 'none']) ? $input['auth_sms_provider'] : 'faraz_sms';
        $clean['auth_faraz_username']      = sanitize_text_field($input['auth_faraz_username'] ?? '');
        $clean['auth_faraz_sender']        = sanitize_text_field($input['auth_faraz_sender']   ?? '');
        $min_len = absint($input['auth_password_min_length'] ?? 8);
        if ($min_len < 6)  $min_len = 6;
        if ($min_len > 32) $min_len = 32;
        $clean['auth_password_min_length'] = $min_len;

        // Preserve existing password if field submitted empty
        $new_pw = isset($input['auth_faraz_password']) ? trim((string)$input['auth_faraz_password']) : '';
        if ($new_pw !== '') {
            $clean['auth_faraz_password'] = sanitize_text_field($new_pw);
        } else {
            $clean['auth_faraz_password'] = $existing['auth_faraz_password'] ?? '';
        }

        if (isset($input['document_categories']) && is_array($input['document_categories'])) {
            foreach ($input['document_categories'] as $k => $v) {
                $clean['document_categories'][sanitize_key($k)] = sanitize_text_field($v);
            }
        }

        if (isset($input['ticket_categories']) && is_array($input['ticket_categories'])) {
            foreach ($input['ticket_categories'] as $k => $v) {
                $clean['ticket_categories'][sanitize_key($k)] = sanitize_text_field($v);
            }
        }

        if (isset($input['report_types']) && is_array($input['report_types'])) {
            foreach ($input['report_types'] as $k => $v) {
                $clean['report_types'][sanitize_key($k)] = sanitize_text_field($v);
            }
        }

        if (isset($input['calendar_categories']) && is_array($input['calendar_categories'])) {
            foreach ($input['calendar_categories'] as $k => $v) {
                $clean['calendar_categories'][sanitize_key($k)] = sanitize_text_field($v);
            }
        }

        return $clean;
    }

    /**
     * Add links to plugin row on plugins page
     *
     * @param array $links
     * @return array
     */
    public function add_plugin_links($links)
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=rsd-fp-settings'),
            esc_html__('Settings', 'rsd-finance-panel')
        );
        $dashboard_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=rsd-finance-panel'),
            esc_html__('Dashboard', 'rsd-finance-panel')
        );
        array_unshift($links, $settings_link, $dashboard_link);
        return $links;
    }

    /**
     * Display admin notices from flash
     *
     * @return void
     */
    public function show_notices()
    {
        $notices = Plugin::get_notices();
        foreach ($notices as $notice) {
            $class = $notice['type'] === 'error' ? 'notice-error' : ($notice['type'] === 'warning' ? 'notice-warning' : ($notice['type'] === 'info' ? 'notice-info' : 'notice-success'));

            printf(
                '<div class="notice %s is-dismissible"><p>%s</p></div>',
                esc_attr($class),
                esc_html($notice['message'])
            );
        }
    }

    /**
     * Add client-related columns to Users list table
     *
     * @param array $columns
     * @return array
     */
    public function add_users_columns($columns)
    {
        $new_cols = [];
        foreach ($columns as $k => $v) {
            $new_cols[$k] = $v;
            if ($k === 'email') {
                $new_cols['rsd_fp_phone'] = esc_html__('Mobile Number', 'rsd-finance-panel');
            }
        }
        $new_cols['rsd_fp_client'] = esc_html__('Finance Portal', 'rsd-finance-panel');
        return $new_cols;
    }

    /**
     * Render custom user column
     *
     * @param string $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function render_users_columns($value, $column_name, $user_id)
    {
        if ($column_name === 'rsd_fp_phone') {
            $phone = (string) get_user_meta((int)$user_id, 'rsd_fp_phone', true);
            if ($phone !== '') {
                $edit_url = admin_url('user-edit.php?user_id=' . (int)$user_id . '#rsd-fp-phone');
                return '<a href="' . esc_url($edit_url) . '" style="direction:ltr;unicode-bidi:plain-text;display:inline-block;color:#2271b1;">' . esc_html($phone) . '</a>';
            }
            return '<span style="color:#999">—</span>';
        }
        if ($column_name === 'rsd_fp_client') {
            if (user_can($user_id, 'rsd_fp_view_dashboard')) {
                $url = admin_url('admin.php?page=rsd-fp-clients&view=client&id=' . $user_id);
                return '<a href="' . esc_url($url) . '" class="button-link">' . esc_html__('View Portal', 'rsd-finance-panel') . '</a>';
            }
            return '<span style="color:#999">' . esc_html__('No Access', 'rsd-finance-panel') . '</span>';
        }
        return $value;
    }

    /**
     * Render the mobile phone field on user profile / user-edit screens.
     * Visible only for users who can edit other users (or their own profile if
     * they have the client cap, though typically clients should not see the
     * backend at all thanks to the admin_init block in Auth).
     *
     * @param WP_User $user
     * @return void
     */
    public function render_user_phone_field($user)
    {
        if (is_numeric($user)) {
            $user = \get_user_by('ID', (int)$user);
        }
        if (!($user instanceof \WP_User)) {
            return;
        }

        $current_user_id = \get_current_user_id();
        $is_self = ((int)$current_user_id === (int)$user->ID);
        $can_manage = \current_user_can('manage_options') || \current_user_can('rsd_fp_manage_all');
        if (!$can_manage && !$is_self) {
            return;
        }

        $phone = (string) \get_user_meta((int)$user->ID, 'rsd_fp_phone', true);
        $nonce_action = 'rsd_fp_save_phone_' . (int)$user->ID;
?>
        <div id="rsd-fp-phone" style="margin-top:24px;">
            <h3><?php \esc_html_e('Finance Portal Information', 'rsd-finance-panel'); ?></h3>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="rsd_fp_phone"><?php \esc_html_e('Mobile Number', 'rsd-finance-panel'); ?></label></th>
                    <td>
                        <input type="tel"
                            id="rsd_fp_phone"
                            name="rsd_fp_phone"
                            class="regular-text ltr"
                            style="direction:ltr;text-align:left;"
                            maxlength="20"
                            value="<?php echo \esc_attr($phone); ?>"
                            placeholder="Example: 09123456789">
                        <p class="description">
                            <?php \esc_html_e('An 11-digit mobile number with the 09 prefix. This number is used for client login to the portal.', 'rsd-finance-panel'); ?>
                        </p>
                        <?php \wp_nonce_field($nonce_action, 'rsd_fp_phone_nonce'); ?>
                    </td>
                </tr>
            </table>
        </div>
<?php
    }

    /**
     * Persist the mobile phone field submitted from the user profile screen.
     * Applies the same canonical normalisation as the auth registration
     * (Persian/Arabic digits -> ASCII, +98/0098/9 -> 09xxxxxxxxx).
     *
     * @param int $user_id
     * @return void
     */
    public function save_user_phone_field($user_id)
    {
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return;
        }

        $nonce = isset($_POST['rsd_fp_phone_nonce']) ? trim((string)$_POST['rsd_fp_phone_nonce']) : '';
        if ($nonce === '' || !\wp_verify_nonce($nonce, 'rsd_fp_save_phone_' . $user_id)) {
            return;
        }

        $current_user_id = \get_current_user_id();
        $is_self = ((int)$current_user_id === $user_id);
        $can_manage = \current_user_can('manage_options') || \current_user_can('rsd_fp_manage_all');
        if (!$can_manage && !$is_self) {
            return;
        }
        if (!$can_manage && !\current_user_can('edit_user', $user_id)) {
            return;
        }

        $raw = isset($_POST['rsd_fp_phone']) ? trim((string)$_POST['rsd_fp_phone']) : '';
        $raw = preg_replace('/\s+/', '', $raw);
        if ($raw === '') {
            \delete_user_meta($user_id, 'rsd_fp_phone');
            return;
        }

        if (class_exists('\\RSD\\FinancePanel\\SMS\\FarazSMS')) {
            $phone = \RSD\FinancePanel\SMS\FarazSMS::normalise_phone($raw);
        } else {
            $map = [
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
            ];
            $phone = \strtr($raw, $map);
            if (\strpos($phone, '+98') === 0) {
                $phone = '0' . \substr($phone, 3);
            } elseif (\strpos($phone, '0098') === 0) {
                $phone = '0' . \substr($phone, 4);
            } elseif (\strpos($phone, '98') === 0 && \strlen($phone) === 12) {
                $phone = '0' . \substr($phone, 2);
            } elseif (\strpos($phone, '9') === 0 && \strlen($phone) === 10) {
                $phone = '0' . $phone;
            }
        }

        $dup_user = \get_users([
            'meta_key'   => 'rsd_fp_phone',
            'meta_value' => $phone,
            'number'     => 2,
            'fields'     => 'ids',
        ]);
        $conflict = false;
        foreach ((array)$dup_user as $uid) {
            if ((int)$uid !== $user_id) {
                $conflict = true;
                break;
            }
        }
        if ($conflict) {
            Plugin::add_notice(
                \sprintf(
                    \esc_html__('The mobile number "%s" is already registered by another user. The mobile number was not saved.', 'rsd-finance-panel'),
                    \esc_html($phone)
                ),
                'error'
            );
            return;
        }

        \update_user_meta($user_id, 'rsd_fp_phone', $phone);
    }

    /**
     * Extend the WP_User_Query search on the Users admin screen so typing a
     * partial mobile number (e.g. "99988" or "09129998877") finds the user
     * based on the rsd_fp_phone meta field, in addition to WP's default
     * email/username/display_name matching.
     *
     * @param WP_User_Query $query
     * @return void
     */
    public function extend_user_search_by_phone($query)
    {
        if (!\is_admin()) {
            return;
        }

        global $pagenow;
        if ($pagenow !== 'users.php') {
            return;
        }

        if (empty($query->query_vars['search'])) {
            return;
        }

        $search = $query->query_vars['search'];
        if (\is_array($search)) {
            $search = !empty($search) ? \trim((string)\reset($search)) : '';
        } else {
            $search = \trim((string)$search);
        }
        if ($search === '') {
            return;
        }

        $stripped = \preg_replace('/[^0-9+]/', '', $search);
        if ($stripped === '') {
            return;
        }

        global $wpdb;
        $likes = [];
        $likes[] = '%' . $wpdb->esc_like($stripped) . '%';
        if (\strpos($stripped, '0') === 0 && \strlen($stripped) >= 7) {
            $likes[] = '%' . $wpdb->esc_like(\substr($stripped, 1)) . '%';
        }

        $meta_sql = '';
        foreach ($likes as $i => $like) {
            if ($i > 0) {
                $meta_sql .= ' OR ';
            }
            $meta_sql .= $wpdb->prepare(
                "(m.meta_key = %s AND m.meta_value LIKE %s)",
                'rsd_fp_phone',
                $like
            );
        }

        if ($meta_sql === '') {
            return;
        }

        $query->query_from  .= " LEFT JOIN {$wpdb->usermeta} m ON ({$wpdb->users}.ID = m.user_id)";
        $query->query_where .= " OR ({$meta_sql})";
        $query->query_orderby = "GROUP BY {$wpdb->users}.ID " . $query->query_orderby;
    }

    // =============== RENDER METHODS ===============

    /**
     * Render admin dashboard page
     *
     * @return void
     */
    public function render_dashboard_page()
    {
        rsd_fp_get_template('admin/dashboard', [
            'stats' => $this->get_dashboard_stats(),
        ]);
    }

    /**
     * Render clients management page
     *
     * @return void
     */
    public function render_clients_page()
    {
        $view = $_GET['view'] ?? 'list';
        $client_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($view === 'client' && $client_id) {
            rsd_fp_get_template('admin/client-single', [
                'client' => get_user_by('id', $client_id),
            ]);
        } else {
            $clients = get_users([
                'role__in' => ['rsd_client', 'subscriber', 'administrator'],
                'meta_query' => [
                    'relation' => 'OR',
                    ['key' => 'wp_capabilities', 'value' => 'rsd_client', 'compare' => 'LIKE'],
                    ['key' => 'wp_capabilities', 'value' => 'administrator', 'compare' => 'LIKE'],
                ],
            ]);
            rsd_fp_get_template('admin/clients', [
                'clients' => $clients,
            ]);
        }
    }

    /**
     * Render documents management page
     *
     * @return void
     */
    public function render_documents_page()
    {
        global $wpdb;
        $table = Database::table('documents');

        $status = $_GET['status'] ?? 'all';
        $client_id = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;

        $where = [];
        $args = [];

        if ($status !== 'all') {
            $where[] = 'd.status = %s';
            $args[] = $status;
        }
        if ($client_id) {
            $where[] = 'd.user_id = %d';
            $args[] = $client_id;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, u.display_name as client_name 
             FROM {$table} d 
             LEFT JOIN {$wpdb->users} u ON u.ID = d.user_id 
             {$where_sql} 
             ORDER BY d.created_at DESC 
             LIMIT 200",
            ...$args
        ));

        rsd_fp_get_template('admin/documents', [
            'documents' => $documents,
            'status'    => $status,
            'client_id' => $client_id,
        ]);
    }

    /**
     * Render tickets management page
     *
     * @return void
     */
    public function render_tickets_page()
    {
        global $wpdb;
        $table = Database::table('tickets');
        $view = $_GET['view'] ?? 'list';
        $ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($view === 'single' && $ticket_id) {
            $ticket = Database::get_row('tickets', $ticket_id);
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, u.display_name as user_name, u.user_email 
                 FROM " . Database::table('ticket_messages') . " m 
                 LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id 
                 WHERE m.ticket_id = %d 
                 ORDER BY m.created_at ASC",
                $ticket_id
            ));
            rsd_fp_get_template('admin/ticket-single', [
                'ticket'   => $ticket,
                'messages' => $messages,
                'client'   => $ticket ? get_user_by('id', $ticket->user_id) : null,
            ]);
        } else {
            $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
            $category = isset($_GET['category']) ? sanitize_key($_GET['category']) : 'all';
            $priority = isset($_GET['priority']) ? sanitize_key($_GET['priority']) : 'all';
            $client_id = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;
            $s = isset($_GET['s']) ? trim(wp_unslash($_GET['s'])) : '';

            $where = [];
            $args = [];

            if ($status !== 'all') {
                $where[] = 't.status = %s';
                $args[] = $status;
            }
            if ($category !== 'all') {
                $where[] = 't.category = %s';
                $args[] = $category;
            }
            if ($priority !== 'all') {
                $where[] = 't.priority = %s';
                $args[] = $priority;
            }
            if ($client_id) {
                $where[] = 't.user_id = %d';
                $args[] = $client_id;
            }
            if ($s !== '') {
                $where[] = '(t.subject LIKE %s OR t.ticket_number LIKE %s OR u.display_name LIKE %s)';
                $like = '%' . $wpdb->esc_like($s) . '%';
                $args[] = $like;
                $args[] = $like;
                $args[] = $like;
            }

            $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $tickets = $wpdb->get_results($wpdb->prepare(
                "SELECT t.*, u.display_name as client_name 
                 FROM {$table} t 
                 LEFT JOIN {$wpdb->users} u ON u.ID = t.user_id 
                 {$where_sql} 
                 ORDER BY 
                     CASE t.status 
                         WHEN 'new' THEN 1 
                         WHEN 'in_progress' THEN 2 
                         WHEN 'waiting' THEN 3 
                         ELSE 4 
                     END,
                     t.created_at DESC 
                 LIMIT 300",
                ...$args
            ));

            $summary = [
                'all'         => 0,
                'new'         => 0,
                'in_progress' => 0,
                'waiting'     => 0,
                'completed'   => 0,
                'closed'      => 0,
            ];
            $all_counts = $wpdb->get_results(
                "SELECT status, COUNT(*) as c FROM {$table} GROUP BY status"
            );
            if (!empty($all_counts)) {
                $summary['all'] = 0;
                foreach ($all_counts as $row) {
                    $summary[$row->status] = (int) $row->c;
                    $summary['all'] += (int) $row->c;
                }
            }

            rsd_fp_get_template('admin/tickets', [
                'tickets'  => $tickets,
                'status'   => $status,
                'category' => $category,
                'priority' => $priority,
                'client_id' => $client_id,
                's'        => $s,
                'summary'  => $summary,
            ]);
        }
    }

    /**
     * Render invoices management page
     *
     * @return void
     */
    public function render_invoices_page()
    {
        global $wpdb;
        $table = Database::table('invoices');
        $view = $_GET['view'] ?? 'list';
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($view === 'single' && $invoice_id) {
            $invoice = Database::get_row('invoices', $invoice_id);
            $payments = Database::get_rows('payments', ['invoice_id' => $invoice_id], 'payment_date', 'DESC');
            rsd_fp_get_template('admin/invoice-single', [
                'invoice'  => $invoice,
                'payments' => $payments,
                'client'   => $invoice ? get_user_by('id', $invoice->user_id) : null,
            ]);
        } else {
            $invoices = $wpdb->get_results(
                "SELECT i.*, u.display_name as client_name 
                 FROM {$table} i 
                 LEFT JOIN {$wpdb->users} u ON u.ID = i.user_id 
                 ORDER BY i.created_at DESC 
                 LIMIT 200"
            );
            $clients = get_users([
                'role__in' => ['rsd_client', 'subscriber', 'administrator'],
                'meta_query' => [
                    'relation' => 'OR',
                    ['key' => 'wp_capabilities', 'value' => 'rsd_client', 'compare' => 'LIKE'],
                    ['key' => 'wp_capabilities', 'value' => 'administrator', 'compare' => 'LIKE'],
                ],
                'orderby' => 'display_name',
                'order'   => 'ASC',
                'number'  => 500,
            ]);
            rsd_fp_get_template('admin/invoices', [
                'invoices' => $invoices,
                'clients'  => $clients,
            ]);
        }
    }

    /**
     * Render reports management page
     *
     * @return void
     */
    public function render_reports_page()
    {
        global $wpdb;
        $table = Database::table('reports');

        $filter_client = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;

        $where = '';
        $args = [];
        if ($filter_client > 0) {
            $where = " WHERE r.user_id = %d ";
            $args[] = $filter_client;
        }

        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name as client_name 
             FROM {$table} r 
             LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id 
             {$where}
             ORDER BY r.created_at DESC 
             LIMIT 300",
            $args
        ));

        $clients = $wpdb->get_results(
            "SELECT u.ID, u.display_name, u.user_email 
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = 'wp_capabilities'
             WHERE (um.meta_value LIKE '%rsd_client%' OR um.meta_value LIKE '%administrator%')
             ORDER BY u.display_name ASC"
        );

        $reports_meta = [];
        foreach ($reports as $r) {
            $r->reports_count = null;
        }

        rsd_fp_get_template('admin/reports', [
            'reports'       => $reports,
            'clients'       => $clients,
            'filter_client' => $filter_client,
        ]);
    }

    /**
     * Render service requests management page
     *
     * @return void
     */
    public function render_services_page()
    {
        global $wpdb;
        $table = Database::table('services');
        $view = $_GET['view'] ?? 'list';
        $service_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($view === 'single' && $service_id) {
            $service = Database::get_row('services', $service_id);
            rsd_fp_get_template('admin/service-single', [
                'service' => $service,
                'client'  => $service ? get_user_by('id', $service->user_id) : null,
            ]);
        } else {
            $services = $wpdb->get_results(
                "SELECT s.*, u.display_name as client_name 
                 FROM {$table} s 
                 LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id 
                 ORDER BY 
                     CASE s.status 
                         WHEN 'pending' THEN 1 
                         WHEN 'in_progress' THEN 2 
                         WHEN 'completed' THEN 3 
                         ELSE 4 
                     END,
                     s.created_at DESC 
                 LIMIT 200"
            );
            rsd_fp_get_template('admin/services', [
                'services' => $services,
            ]);
        }
    }

    /**
     * Render calendar management page
     *
     * @return void
     */
    public function render_calendar_page()
    {
        global $wpdb;
        $table = Database::table('calendar');

        $calendar_events = $wpdb->get_results(
            "SELECT c.*, u.display_name as client_name 
             FROM {$table} c 
             LEFT JOIN {$wpdb->users} u ON u.ID = c.user_id 
             ORDER BY c.start_date DESC 
             LIMIT 500"
        ) ?: [];

        $doc_events = rsd_fp_documents_as_events(null);
        foreach ($doc_events as $ev) {
            $uid = (int) ($ev->user_id ?? 0);
            if ($uid > 0 && empty($ev->client_name)) {
                $u = get_userdata($uid);
                if ($u) $ev->client_name = $u->display_name;
            }
        }
        $events = rsd_fp_merge_calendar_and_doc_events($calendar_events, $doc_events);

        rsd_fp_get_template('admin/calendar', [
            'events' => $events,
        ]);
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page()
    {
        $settings = get_option('rsd_fp_settings', []);
        rsd_fp_get_template('admin/settings', [
            'settings' => $settings,
        ]);
    }

    // =============== HELPERS ===============

    /**
     * Get dashboard statistics
     *
     * @return array
     */
    private function get_dashboard_stats()
    {
        global $wpdb;

        $stats = [];

        // Total clients
        $stats['total_clients'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE '%rsd_client%'"
        );

        // Total invoices
        $invoices_table = Database::table('invoices');
        $stats['total_invoices'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$invoices_table}");
        $stats['unpaid_invoices'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$invoices_table} WHERE status IN ('unpaid','overdue','partial')");
        $stats['total_invoiced'] = (float) $wpdb->get_var("SELECT COALESCE(SUM(total_amount),0) FROM {$invoices_table}");
        $stats['total_paid'] = (float) $wpdb->get_var("SELECT COALESCE(SUM(paid_amount),0) FROM {$invoices_table}");
        $stats['total_outstanding'] = (float) $stats['total_invoiced'] - (float) $stats['total_paid'];

        // Tickets
        $tickets_table = Database::table('tickets');
        $stats['total_tickets'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tickets_table}");
        $stats['open_tickets'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tickets_table} WHERE status IN ('new','in_progress','waiting')");

        // Documents
        $docs_table = Database::table('documents');
        $stats['total_documents'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$docs_table}");
        $stats['incomplete_docs'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$docs_table} WHERE is_incomplete = 1");

        // Recent
        $stats['recent_tickets'] = Database::get_rows('tickets', [], 'created_at', 'DESC', 5);
        $stats['recent_invoices'] = Database::get_rows('invoices', [], 'created_at', 'DESC', 5);

        return $stats;
    }
}
