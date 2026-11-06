<?php

/**
 * Invoices module class
 *
 * Business logic for invoices & payments management
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Database;
use RSD\FinancePanel\Core\Plugin;

class Invoices
{
    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        // Update invoice status when payments change
        add_action('rsd_fp_payment_added', [$this, 'update_invoice_balance'], 10, 2);

        // Admin-post handlers for PDF/print views
        add_action('admin_post_rsd_fp_print_invoice', [$this, 'handle_print_invoice']);
        add_action('admin_post_nopriv_rsd_fp_print_invoice', [$this, 'handle_print_invoice']);
    }

    /**
     * Get invoice statuses
     *
     * @return array
     */
    public static function get_statuses()
    {
        return [
            'paid'    => esc_html__('Paid', 'rsd-finance-panel'),
            'partial' => esc_html__('Partial Payment', 'rsd-finance-panel'),
            'unpaid'  => esc_html__('Unpaid', 'rsd-finance-panel'),
            'overdue' => esc_html__('Overdue', 'rsd-finance-panel'),
            'void'    => esc_html__('Void', 'rsd-finance-panel'),
        ];
    }

    /**
     * Get payment methods
     *
     * @return array
     */
    public static function get_payment_methods()
    {
        return [
            'cash'         => esc_html__('Cash', 'rsd-finance-panel'),
            'bank_transfer' => esc_html__('Bank Transfer', 'rsd-finance-panel'),
            'card'         => esc_html__('Card to Card', 'rsd-finance-panel'),
            'cheque'       => esc_html__('Cheque', 'rsd-finance-panel'),
            'online'       => esc_html__('Online Payment', 'rsd-finance-panel'),
            'other'        => esc_html__('Other', 'rsd-finance-panel'),
        ];
    }

    /**
     * Generate a unique invoice number
     *
     * @return string
     */
    public static function generate_invoice_number()
    {
        $prefix = 'INV-' . date('Ym');
        global $wpdb;
        $table = Database::table('invoices');
        $last = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)), 0) 
             FROM {$table} 
             WHERE invoice_number LIKE %s",
            $prefix . '%'
        ));
        $next = str_pad($last + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . '-' . $next;
    }

    /**
     * Create a new invoice
     *
     * @param array $data
     * @return int|false
     */
    public static function create_invoice($data)
    {
        $defaults = [
            'user_id'       => 0,
            'invoice_number' => self::generate_invoice_number(),
            'invoice_date'  => current_time('Y-m-d'),
            'due_date'      => null,
            'year'          => '',
            'month'         => '',
            'subtotal'      => 0,
            'tax'           => 0,
            'discount'      => 0,
            'total_amount'  => 0,
            'paid_amount'   => 0,
            'status'        => 'unpaid',
            'description'   => '',
            'created_by'    => get_current_user_id(),
        ];

        $data = wp_parse_args($data, $defaults);

        // Normalize empty due_date to null (avoids MySQL STRICT mode invalid DATE error)
        if ('' === $data['due_date']) {
            $data['due_date'] = null;
        }

        // Fallback for created_by: ensure we never pass 0 as creator id
        if (empty($data['created_by']) || (int) $data['created_by'] <= 0) {
            $data['created_by'] = get_current_user_id();
            if (empty($data['created_by']) || (int) $data['created_by'] <= 0) {
                $data['created_by'] = 1;
            }
        }

        // Recalculate totals if any component changed
        $subtotal = (float) $data['subtotal'];
        $tax      = (float) $data['tax'];
        $discount = (float) $data['discount'];
        $data['total_amount'] = max(0, $subtotal + $tax - $discount);

        if (empty($data['user_id']) || (int) $data['user_id'] <= 0) {
            return false;
        }
        if ($data['total_amount'] <= 0) {
            return false;
        }

        $insert_data = [
            'user_id'        => (int) $data['user_id'],
            'invoice_number' => sanitize_text_field($data['invoice_number']),
            'invoice_date'   => $data['invoice_date'],
            'due_date'       => $data['due_date'],
            'year'           => sanitize_text_field($data['year']),
            'month'          => sanitize_text_field($data['month']),
            'subtotal'       => $subtotal,
            'tax'            => $tax,
            'discount'       => $discount,
            'total_amount'   => $data['total_amount'],
            'paid_amount'    => (float) $data['paid_amount'],
            'status'         => sanitize_key($data['status']),
            'description'    => sanitize_textarea_field($data['description']),
            'created_by'     => (int) $data['created_by'],
        ];

        $invoice_id = Database::insert('invoices', $insert_data);

        if ($invoice_id) {
            do_action('rsd_fp_invoice_created', $invoice_id, $insert_data);

            // Auto-fire balance update hook if there's an initial paid amount
            if ((float) $data['paid_amount'] > 0) {
                $payment_data = [
                    'user_id'    => (int) $data['user_id'],
                    'invoice_id' => $invoice_id,
                    'amount'     => (float) $data['paid_amount'],
                    'status'     => 'completed',
                ];
                do_action('rsd_fp_payment_added', 0, $payment_data);
            }
        }

        return $invoice_id;
    }

    /**
     * Update an existing invoice
     *
     * @param int $invoice_id
     * @param array $data
     * @return bool
     */
    public static function update_invoice($invoice_id, $data)
    {
        $invoice = Database::get_row('invoices', $invoice_id);
        if (!$invoice) {
            return false;
        }

        $update_data = [];

        $fields = [
            'user_id',
            'invoice_date',
            'due_date',
            'year',
            'month',
            'subtotal',
            'tax',
            'discount',
            'status',
            'description'
        ];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $update_data[$f] = $data[$f];
            }
        }

        // Recalculate totals
        $subtotal = isset($update_data['subtotal']) ? (float) $update_data['subtotal'] : (float) $invoice->subtotal;
        $tax      = isset($update_data['tax'])      ? (float) $update_data['tax']      : (float) $invoice->tax;
        $discount = isset($update_data['discount']) ? (float) $update_data['discount'] : (float) $invoice->discount;
        $update_data['total_amount'] = max(0, $subtotal + $tax - $discount);

        $sanitized = [];
        foreach ($update_data as $k => $v) {
            switch ($k) {
                case 'user_id':
                    $sanitized[$k] = (int) $v;
                    break;
                case 'subtotal':
                case 'tax':
                case 'discount':
                case 'total_amount':
                    $sanitized[$k] = (float) $v;
                    break;
                case 'status':
                    $sanitized[$k] = sanitize_key($v);
                    break;
                case 'description':
                    $sanitized[$k] = sanitize_textarea_field($v);
                    break;
                case 'invoice_date':
                case 'due_date':
                    $sanitized[$k] = ('' === $v) ? null : $v;
                    break;
                default:
                    $sanitized[$k] = sanitize_text_field($v);
            }
        }

        $updated = Database::update('invoices', $sanitized, ['id' => (int) $invoice_id]);

        if ($updated) {
            // Re-evaluate status/balance based on existing payments
            $invoices = new self();
            $invoices->update_invoice_balance(0, ['invoice_id' => $invoice_id]);
            do_action('rsd_fp_invoice_updated', $invoice_id, $sanitized);
        }

        return (bool) $updated;
    }

    /**
     * Delete an invoice (soft via void status option, hard default)
     *
     * @param int $invoice_id
     * @return bool
     */
    public static function delete_invoice($invoice_id)
    {
        $invoice = Database::get_row('invoices', $invoice_id);
        if (!$invoice) {
            return false;
        }

        // Delete linked payments (or keep them? we delete to keep DB clean)
        global $wpdb;
        $pay_table = Database::table('payments');
        $wpdb->delete($pay_table, ['invoice_id' => (int) $invoice_id], ['%d']);

        // Remove file if any
        if (!empty($invoice->file_path)) {
            $local = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $invoice->file_path);
            if (file_exists($local)) {
                wp_delete_file($local);
            }
        }

        $deleted = Database::delete('invoices', ['id' => (int) $invoice_id]);
        if ($deleted) {
            do_action('rsd_fp_invoice_deleted', $invoice_id, $invoice);
        }

        return (bool) $deleted;
    }

    /**
     * Add a payment record to an invoice
     *
     * @param array $data
     * @return int|false
     */
    public static function add_payment($data)
    {
        $defaults = [
            'user_id'        => 0,
            'invoice_id'     => 0,
            'transaction_id' => '',
            'amount'         => 0,
            'payment_method' => 'other',
            'payment_date'   => current_time('mysql'),
            'description'    => '',
            'status'         => 'completed',
            'created_by'     => get_current_user_id(),
        ];
        $data = wp_parse_args($data, $defaults);

        if ((int) $data['invoice_id'] <= 0) return false;
        if ((float) $data['amount'] <= 0) return false;

        $invoice = Database::get_row('invoices', (int) $data['invoice_id']);
        if (!$invoice) return false;

        $insert = [
            'user_id'        => (int) ($data['user_id'] ?: $invoice->user_id),
            'invoice_id'     => (int) $data['invoice_id'],
            'transaction_id' => sanitize_text_field($data['transaction_id']),
            'amount'         => (float) $data['amount'],
            'payment_method' => sanitize_key($data['payment_method']),
            'payment_date'   => $data['payment_date'],
            'description'    => sanitize_textarea_field($data['description']),
            'status'         => sanitize_key($data['status']),
            'created_by'     => (int) $data['created_by'],
        ];

        $payment_id = Database::insert('payments', $insert);
        if ($payment_id) {
            do_action('rsd_fp_payment_added', $payment_id, $insert);
        }

        return $payment_id;
    }

    /**
     * Get invoices for a client
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_client_invoices($user_id, $limit = 50)
    {
        return Database::get_rows(
            'invoices',
            ['user_id' => (int) $user_id],
            'created_at',
            'DESC',
            $limit
        );
    }

    /**
     * Get print/PDF URL for an invoice
     *
     * @param int $invoice_id
     * @return string
     */
    public static function get_print_url($invoice_id)
    {
        return wp_nonce_url(
            admin_url('admin-post.php?action=rsd_fp_print_invoice&id=' . (int) $invoice_id),
            'rsd_fp_print_invoice_' . $invoice_id
        );
    }

    /**
     * Handle admin-post request to render printable invoice (HTML → save as PDF via browser)
     *
     * @return void
     */
    public function handle_print_invoice()
    {
        $invoice_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($invoice_id <= 0) {
            wp_die(esc_html__('The invoice is invalid.', 'rsd-finance-panel'));
        }

        // Nonce check
        if (
            !isset($_GET['_wpnonce']) ||
            !wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'rsd_fp_print_invoice_' . $invoice_id)
        ) {
            wp_die(esc_html__('The security token is invalid.', 'rsd-finance-panel'));
        }

        // Permission check
        $invoice = Database::get_row('invoices', $invoice_id);
        if (!$invoice) {
            wp_die(esc_html__('Invoice not found.', 'rsd-finance-panel'));
        }

        $user_id = get_current_user_id();
        if (!current_user_can('manage_options') && (int) $invoice->user_id !== $user_id) {
            if (!current_user_can('rsd_fp_view_dashboard') || (int) $invoice->user_id !== $user_id) {
                wp_die(esc_html__('Access is not allowed.', 'rsd-finance-panel'));
            }
        }

        $client = get_user_by('id', (int) $invoice->user_id);
        $company_info = Company_Info::get((int) $invoice->user_id);
        $payments = Database::get_rows('payments', ['invoice_id' => $invoice_id], 'payment_date', 'DESC');

        // Render standalone print HTML
        $this->render_printable_invoice($invoice, $client, $company_info, $payments);
        exit;
    }

    /**
     * Render standalone printable invoice HTML page
     *
     * @param object $invoice
     * @param WP_User|false $client
     * @param object|null $company_info
     * @param array $payments
     * @return void
     */
    private function render_printable_invoice($invoice, $client, $company_info, $payments)
    {
        $statuses = self::get_statuses();
        $pay_methods = self::get_payment_methods();
        $status = $statuses[$invoice->status] ?? $invoice->status;
        $outstanding = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');

        $invoice_date_jalali = function_exists('rsd_fp_jalali_date')
            ? rsd_fp_jalali_date($invoice->invoice_date)
            : date_i18n('j F Y', strtotime($invoice->invoice_date));
        $due_date_jalali = '';
        if (!empty($invoice->due_date)) {
            $due_date_jalali = function_exists('rsd_fp_jalali_date')
                ? rsd_fp_jalali_date($invoice->due_date)
                : date_i18n('j F Y', strtotime($invoice->due_date));
        }
?>
        <!DOCTYPE html>
        <html dir="ltr" lang="en">

        <head>
            <meta charset="UTF-8">
            <title>Invoice #<?php echo esc_html($invoice->invoice_number); ?></title>
            <style>
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }

                body {
                    font-family: Inter, 'Segoe UI', system-ui, -apple-system, sans-serif;
                    font-size: 13px;
                    line-height: 1.7;
                    color: #111;
                    background: #fff;
                    padding: 40px;
                    direction: ltr;
                }

                .invoice-wrap {
                    max-width: 900px;
                    margin: 0 auto;
                    border: 1px solid #ddd;
                    padding: 40px;
                    background: #fff;
                }

                .invoice-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #0369a1;
                }

                .brand h1 {
                    color: #0369a1;
                    font-size: 24px;
                    margin-bottom: 6px;
                }

                .brand p {
                    color: #666;
                    font-size: 12px;
                }

                .invoice-meta {
                    text-align: left;
                    direction: ltr;
                }

                .invoice-meta .num {
                    font-size: 22px;
                    font-weight: bold;
                    color: #0f172a;
                    font-family: monospace;
                }

                .invoice-meta .meta-row {
                    margin-top: 6px;
                    font-size: 12px;
                    color: #475569;
                }

                .section-title {
                    font-size: 14px;
                    font-weight: bold;
                    color: #0369a1;
                    border-right: 3px solid #0369a1;
                    padding-right: 8px;
                    margin: 20px 0 10px;
                }

                .two-col {
                    display: flex;
                    justify-content: space-between;
                    gap: 30px;
                    margin-bottom: 20px;
                }

                .col {
                    width: 48%;
                }

                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 5px 0;
                    border-bottom: 1px dotted #e5e7eb;
                }

                .info-row .lbl {
                    color: #64748b;
                    font-weight: 600;
                }

                .info-row .val {
                    color: #0f172a;
                    direction: ltr;
                    text-align: left;
                    font-family: inherit;
                }

                .table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }

                .table th,
                .table td {
                    border: 1px solid #e5e7eb;
                    padding: 10px 12px;
                    text-align: center;
                }

                .table th {
                    background: #f1f5f9;
                    color: #0f172a;
                    font-weight: bold;
                }

                .table td.num {
                    direction: ltr;
                    font-family: monospace;
                    text-align: left;
                }

                .totals-box {
                    margin-top: 20px;
                    padding: 16px;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    max-width: 380px;
                    margin-left: auto;
                }

                .totals-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 4px 0;
                }

                .totals-row .lbl {
                    color: #475569;
                    font-weight: 600;
                }

                .totals-row .val {
                    direction: ltr;
                    font-family: monospace;
                }

                .totals-row.total {
                    border-top: 2px solid #0369a1;
                    padding-top: 10px;
                    margin-top: 6px;
                    font-size: 16px;
                    font-weight: bold;
                    color: #0369a1;
                }

                .status-badge {
                    display: inline-block;
                    padding: 3px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                }

                .status-paid {
                    background: #dcfce7;
                    color: #166534;
                }

                .status-partial {
                    background: #fef9c3;
                    color: #854d0e;
                }

                .status-unpaid {
                    background: #ffedd5;
                    color: #9a3412;
                }

                .status-overdue {
                    background: #fee2e2;
                    color: #991b1b;
                }

                .payments-box {
                    margin-top: 20px;
                }

                .empty-msg {
                    color: #94a3b8;
                    text-align: center;
                    padding: 15px;
                }

                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #e5e7eb;
                    text-align: center;
                    color: #64748b;
                    font-size: 11px;
                }

                @media print {
                    body {
                        padding: 0;
                        background: #fff;
                    }

                    .invoice-wrap {
                        border: none;
                        padding: 0;
                    }

                    .no-print {
                        display: none !important;
                    }
                }

                .toolbar {
                    position: fixed;
                    top: 10px;
                    left: 10px;
                    z-index: 9999;
                }

                .btn-print {
                    background: #0369a1;
                    color: #fff;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 6px;
                    font-size: 13px;
                    cursor: pointer;
                    font-weight: bold;
                }

                .btn-print:hover {
                    background: #075985;
                }
            </style>
        </head>

        <body>
            <div class="toolbar no-print">
                <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
            </div>
            <div class="invoice-wrap">
                <div class="invoice-header">
                    <div class="brand">
                        <h1><?php echo esc_html($site_name); ?></h1>
                        <p><?php echo esc_html($site_url); ?></p>
                        <p style="margin-top:10px;color:#0369a1;font-weight:bold;font-size:18px">Official Invoice</p>
                    </div>
                    <div class="invoice-meta">
                        <div class="num">#<?php echo esc_html($invoice->invoice_number); ?></div>
                        <div class="meta-row">Issue Date: <?php echo esc_html($invoice_date_jalali); ?></div>
                        <?php if (!empty($due_date_jalali)): ?>
                            <div class="meta-row">Due Date: <?php echo esc_html($due_date_jalali); ?></div>
                        <?php endif; ?>
                        <div class="meta-row" style="margin-top:10px">
                            <span class="status-badge status-<?php echo esc_attr($invoice->status); ?>">
                                <?php echo esc_html($status); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="two-col">
                    <div class="col">
                        <div class="section-title">Client Information</div>
                        <?php if ($client): ?>
                            <div class="info-row"><span class="lbl">Full Name:</span><span class="val" style="direction:ltr;font-family:inherit"><?php echo esc_html($client->display_name); ?></span></div>
                            <div class="info-row"><span class="lbl">Email:</span><span class="val" style="direction:ltr;font-family:inherit"><?php echo esc_html($client->user_email); ?></span></div>
                            <div class="info-row"><span class="lbl">Username:</span><span class="val" style="direction:ltr;font-family:inherit"><?php echo esc_html($client->user_login); ?></span></div>
                            <?php if ($company_info && !empty($company_info->company_name)): ?>
                                <div class="info-row"><span class="lbl">Company Name:</span><span class="val" style="direction:ltr;font-family:inherit"><?php echo esc_html($company_info->company_name); ?></span></div>
                            <?php endif; ?>
                            <?php if ($company_info && !empty($company_info->phone)): ?>
                                <div class="info-row"><span class="lbl">Phone:</span><span class="val" style="direction:ltr;font-family:inherit"><?php echo esc_html($company_info->phone); ?></span></div>
                            <?php endif; ?>
                            <?php if ($company_info && !empty($company_info->registration_number)): ?>
                                <div class="info-row"><span class="lbl">National ID / Registration:</span><span class="val"><?php echo esc_html($company_info->registration_number); ?></span></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color:#94a3b8">User not found.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <div class="section-title">Period Details</div>
                        <?php if (!empty($invoice->year) || !empty($invoice->month)): ?>
                            <div class="info-row"><span class="lbl">Fiscal Year:</span><span class="val"><?php echo esc_html($invoice->year ? 'Year ' . $invoice->year : '—'); ?></span></div>
                            <div class="info-row"><span class="lbl">Month / Period:</span><span class="val"><?php echo esc_html($invoice->month ?: '—'); ?></span></div>
                        <?php endif; ?>
                        <div class="info-row"><span class="lbl">Invoice ID:</span><span class="val"><?php echo (int)$invoice->id; ?></span></div>
                    </div>
                </div>

                <?php if (!empty($invoice->description)): ?>
                    <div class="section-title">Invoice Description</div>
                    <div style="padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;color:#0f172a">
                        <?php echo nl2br(esc_html($invoice->description)); ?>
                    </div>
                <?php endif; ?>

                <table class="table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Description</th>
                            <th>Amount (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td style="text-align:left"><?php echo esc_html($invoice->description ?: 'Financial services for period ' . ($invoice->month ? $invoice->month . ' ' : '') . ($invoice->year ? $invoice->year : '')); ?></td>
                            <td class="num"><?php echo esc_html(number_format_i18n((float) $invoice->subtotal, 0)); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="totals-box">
                    <div class="totals-row">
                        <span class="lbl">Subtotal:</span>
                        <span class="val"><?php echo esc_html(number_format_i18n((float) $invoice->subtotal, 0)); ?></span>
                    </div>
                    <?php if ((float) $invoice->tax > 0): ?>
                        <div class="totals-row">
                            <span class="lbl">Tax:</span>
                            <span class="val"><?php echo esc_html(number_format_i18n((float) $invoice->tax, 0)); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ((float) $invoice->discount > 0): ?>
                        <div class="totals-row">
                            <span class="lbl">Discount:</span>
                            <span class="val" style="color:#dc2626">- <?php echo esc_html(number_format_i18n((float) $invoice->discount, 0)); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="totals-row total">
                        <span class="lbl">Invoice Total:</span>
                        <span class="val"><?php echo esc_html(number_format_i18n((float) $invoice->total_amount, 0)); ?></span>
                    </div>
                    <div class="totals-row" style="color:#059669;margin-top:8px">
                        <span class="lbl">Paid:</span>
                        <span class="val"><?php echo esc_html(number_format_i18n((float) $invoice->paid_amount, 0)); ?></span>
                    </div>
                    <div class="totals-row" style="color:<?php echo $outstanding > 0 ? '#dc2626' : '#059669'; ?>;font-weight:bold">
                        <span class="lbl">Outstanding Balance:</span>
                        <span class="val"><?php echo esc_html(number_format_i18n($outstanding, 0)); ?></span>
                    </div>
                </div>

                <?php if (!empty($payments)): ?>
                    <div class="payments-box">
                        <div class="section-title">Payment History</div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Payment Method</th>
                                    <th>Transaction ID</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $idx => $p):
                                    $pd = function_exists('rsd_fp_jalali_date') ? rsd_fp_jalali_date($p->payment_date) : date_i18n('j F Y', strtotime($p->payment_date));
                                    $pm = $pay_methods[$p->payment_method] ?? $p->payment_method;
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($pd); ?></td>
                                        <td><?php echo esc_html($pm); ?></td>
                                        <td class="num"><?php echo esc_html($p->transaction_id ?: '—'); ?></td>
                                        <td class="num"><?php echo esc_html(number_format_i18n((float) $p->amount, 0)); ?></td>
                                        <td><?php echo esc_html($p->description ?: '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="footer">
                    <p><?php echo esc_html($site_name); ?> - This invoice was generated automatically. If you have any questions, please contact us.</p>
                    <p style="margin-top:4px">Printed on: <?php echo esc_html(date_i18n('j F Y H:i')); ?></p>
                </div>
            </div>
        </body>

        </html>
<?php
    }

    /**
     * Recalculate invoice paid amount and status
     *
     * @param int $payment_id
     * @param array $payment_data
     * @return void
     */
    public function update_invoice_balance($payment_id, $payment_data)
    {
        if (empty($payment_data['invoice_id'])) {
            return;
        }

        $invoice_id = (int) $payment_data['invoice_id'];

        global $wpdb;
        $payments_table = Database::table('payments');
        $total_paid = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$payments_table} WHERE invoice_id = %d AND status = 'completed'",
            $invoice_id
        ));

        $invoice = Database::get_row('invoices', $invoice_id);
        if (!$invoice) return;

        $status = 'unpaid';
        if ($total_paid >= (float) $invoice->total_amount && $total_paid > 0) {
            $status = 'paid';
        } elseif ($total_paid > 0) {
            $status = 'partial';
        }

        // Check overdue
        if ($status !== 'paid' && $invoice->due_date) {
            $today = strtotime(current_time('mysql'));
            if (strtotime($invoice->due_date) < $today) {
                $status = 'overdue';
            }
        }

        Database::update('invoices', [
            'paid_amount' => $total_paid,
            'status'      => $status,
        ], ['id' => $invoice_id]);
    }

    /**
     * Get client balance summary
     *
     * @param int $user_id
     * @return array
     */
    public static function get_balance($user_id)
    {
        global $wpdb;
        $table = Database::table('invoices');

        $invoiced = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_amount),0) FROM {$table} WHERE user_id = %d",
            $user_id
        ));
        $paid = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(paid_amount),0) FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        return [
            'total_invoiced' => $invoiced,
            'total_paid'     => $paid,
            'outstanding'    => max(0, $invoiced - $paid),
            'overpaid'       => max(0, $paid - $invoiced),
        ];
    }
}
