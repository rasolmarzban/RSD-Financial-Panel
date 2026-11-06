<?php

/**
 * Admin Template: Client Single View
 *
 * @var WP_User $client
 */
defined('ABSPATH') || exit;

use RSD\FinancePanel\Modules\Invoices;

$client_id = $client ? $client->ID : 0;
$balance = Invoices::get_balance($client_id);
$incomplete_count = \RSD\FinancePanel\Modules\Documents::count_incomplete($client_id);
$unread_tickets = \RSD\FinancePanel\Modules\Tickets::count_unread($client_id);
$client_invoices = Invoices::get_client_invoices($client_id, 100);
$statuses = Invoices::get_statuses();
$pay_methods = Invoices::get_payment_methods();
$status_colors = [
    'paid'    => 'green',
    'partial' => 'yellow',
    'unpaid'  => 'orange',
    'overdue' => 'red',
    'void'    => 'gray',
];
$today_jalali = function_exists('rsd_fp_current_jalali_date') ? rsd_fp_current_jalali_date() : '';

/* Helper (duplicate-safe since defined in invoices.php too via function_exists): Build jalali triple-select */
if (!function_exists('rsd_fp_render_jalali_triple')) {
    function rsd_fp_render_jalali_triple($target_name, $prefix, $required = false, $default_value = '')
    {
        $persian_months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
        $current_jalali_y = (int) date('Y');
        if (function_exists('rsd_fp_current_jalali_year')) {
            $current_jalali_y = (int) rsd_fp_current_jalali_year();
        }
        $years = range($current_jalali_y - 20, $current_jalali_y + 5);
        $days  = range(1, 31);

        $sel_y = '';
        $sel_m = '';
        $sel_d = '';
        $dv = trim((string)$default_value);
        if ($dv !== '' && preg_match('#^([1-9]\d{3})[\-\/](0?[1-9]|1[0-2])[\-\/](0?[1-9]|[12]\d|3[01])$#', $dv, $mm)) {
            $yy = (int)$mm[1];
            if ($yy >= 1900 && $yy <= 2100) {
                $sel_y = (string)$yy;
                $sel_m = (int)$mm[2];
                $sel_d = (int)$mm[3];
            }
        }
        $req = $required ? ' required' : '';
        $hidden_req = $required ? ' required' : '';
        $safe_default = '';
        if ($sel_y !== '' && $sel_m !== '' && $sel_d !== '') {
            $pn2 = function ($n) {
                return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
            };
            $safe_default = $sel_y . '/' . $pn2($sel_m) . '/' . $pn2($sel_d);
        }
?>
        <input type="hidden" name="<?php echo esc_attr($target_name); ?>" id="<?php echo esc_attr($prefix . '-hidden'); ?>"
            class="rsd-triple-date-hidden" value="<?php echo esc_attr($safe_default); ?>" <?php echo $hidden_req; ?>>
        <div class="rsd-date-triple" data-rsd-triple-target="<?php echo esc_attr($prefix . '-hidden'); ?>">
            <select class="rsd-triple-year" data-rsd-part="y" <?php echo $req; ?>>
                <option value="">Year</option>
                <?php foreach (array_reverse($years, true) as $y) :
                    $y = (string)$y;
                    $s = selected($sel_y, $y, false);
                    echo '<option value="' . esc_attr($y) . '"' . $s . '>' . esc_html($y) . '</option>';
                endforeach; ?>
            </select>
            <select class="rsd-triple-month" data-rsd-part="m" <?php echo $req; ?>>
                <option value="">Month</option>
                <?php foreach ($persian_months as $mn => $ml) :
                    $s = selected((string)$sel_m, (string)$mn, false);
                    echo '<option value="' . esc_attr($mn) . '"' . $s . '>' . esc_html($ml) . '</option>';
                endforeach; ?>
            </select>
            <select class="rsd-triple-day" data-rsd-part="d" <?php echo $req; ?>>
                <option value="">Day</option>
                <?php foreach ($days as $d) :
                    $s = selected((string)$sel_d, (string)$d, false);
                    echo '<option value="' . esc_attr($d) . '"' . $s . '>' . esc_html($d) . '</option>';
                endforeach; ?>
            </select>
        </div>
<?php
    }
}
if (!function_exists('rsd_fp_today_jalali_ymd')) {
    function rsd_fp_today_jalali_ymd()
    {
        $pn = function ($n) {
            return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
        };
        if (function_exists('rsd_fp_current_jalali_date')) {
            $t = rsd_fp_current_jalali_date();
            if (is_string($t)) {
                $t = trim($t);
                if (preg_match('#^([1-9]\d{3})[\-\/](0?[1-9]|1[0-2])[\-\/](0?[1-9]|[12]\d|3[01])$#', $t, $mm)) {
                    $yy = (int)$mm[1];
                    if ($yy >= 1900 && $yy <= 2100) {
                        return $mm[1] . '/' . $pn($mm[2]) . '/' . $pn($mm[3]);
                    }
                }
            }
        }
        // Hard fallback: use the jalali parts algorithm directly
        if (function_exists('rsd_fp_gregorian_to_jalali_parts')) {
            [$gy, $gm, $gd] = [(int)date('Y'), (int)date('n'), (int)date('j')];
            [$jy, $jm, $jd] = rsd_fp_gregorian_to_jalali_parts($gy, $gm, $gd);
            if ($jy >= 1900 && $jy <= 2100) {
                return $jy . '/' . $pn($jm) . '/' . $pn($jd);
            }
        }
        return '';
    }
}
$today_j = rsd_fp_today_jalali_ymd();
?>
<div class="rsd-admin-wrap" id="rsd-admin-client-page">
    <div class="rsd-admin-header">
        <div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-clients')); ?>"
                class="rsd-btn-admin small secondary" style="margin-bottom:10px">← Back to list</a>
            <h1>👤 <?php echo esc_html($client ? $client->display_name : '#' . $client_id); ?></h1>
            <p><?php echo esc_html($client ? $client->user_email : ' Unknown user'); ?></p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="rsd-btn-admin primary" data-rsd-modal="rsd-client-invoice-modal" data-rsd-invoice-mode="create">
                🧾 Issue Invoice for this Client
            </button>
            <a href="<?php echo esc_url(add_query_arg(['client_id' => $client_id], admin_url('admin.php?page=rsd-fp-documents'))); ?>"
                class="rsd-btn-admin secondary">📂 Client Documents</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-invoices')); ?>"
                class="rsd-btn-admin secondary">📑 All Invoices</a>
        </div>
    </div>

    <!-- Client summary -->
    <div class="rsd-admin-stats-row">
        <div class="rsd-admin-stat-card purple">
            <div class="stat-top">
                <div class="stat-label">Total Invoiced</div>
                <div class="stat-icon">📑</div>
            </div>
            <div class="stat-value"><?php echo esc_html(rsd_fp_currency($balance['total_invoiced'] ?? 0)); ?></div>
        </div>
        <div class="rsd-admin-stat-card green">
            <div class="stat-top">
                <div class="stat-label">Paid</div>
                <div class="stat-icon">💳</div>
            </div>
            <div class="stat-value"><?php echo esc_html(rsd_fp_currency($balance['total_paid'] ?? 0)); ?></div>
        </div>
        <div class="rsd-admin-stat-card red">
            <div class="stat-top">
                <div class="stat-label">Outstanding Balance</div>
                <div class="stat-icon">⚠️</div>
            </div>
            <div class="stat-value"><?php echo esc_html(rsd_fp_currency($balance['outstanding'] ?? 0)); ?></div>
        </div>
        <div class="rsd-admin-stat-card orange">
            <div class="stat-top">
                <div class="stat-label">Incomplete Documents</div>
                <div class="stat-icon">📄</div>
            </div>
            <div class="stat-value"><?php echo esc_html($incomplete_count); ?></div>
        </div>
        <div class="rsd-admin-stat-card teal">
            <div class="stat-top">
                <div class="stat-label">Unread Tickets</div>
                <div class="stat-icon">💬</div>
            </div>
            <div class="stat-value"><?php echo esc_html($unread_tickets); ?></div>
        </div>
    </div>

    <div class="rsd-admin-grid cols-2">
        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:16px">🏢 Company Information</div>
            <?php
            $info = \RSD\FinancePanel\Modules\Company_Info::get($client_id);
            $fields = \RSD\FinancePanel\Modules\Company_Info::get_fields();
            if (!$info) : ?>
                <div class="rsd-empty">
                    <p>No company information has been registered for this client.</p>
                </div>
            <?php else : ?>
                <table class="rsd-admin-table">
                    <tbody>
                        <?php
                        foreach ($fields as $k => $f) :
                            $val = $info->$k ?? '';
                            if ($val === '' || $val === null) continue;
                            if (is_string($val) && ($val[0] === '{')) $val = json_decode($val, true);
                        ?>
                            <tr>
                                <td style="width:200px;font-weight:600;color:#475569"><?php echo esc_html($f['label']); ?></td>
                                <td><?php echo is_array($val) ? esc_html(implode(', ', $val)) : nl2br(esc_html($val)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!empty($info->pending_changes)) :
                    $pending = json_decode($info->pending_changes, true);
                    if (!is_array($pending)) $pending = [];
                ?>
                    <div class="rsd-alert rsd-alert-warning" style="margin-top:16px;padding:18px">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
                            <strong style="font-size:14px">⏳ Changes awaiting expert approval</strong>
                            <div style="display:flex;gap:8px">
                                <button type="button"
                                    class="rsd-btn-admin small primary"
                                    data-rsd-company-approve
                                    data-rsd-user-id="<?php echo (int)$client_id; ?>">
                                    ✅ Approve and Save Information
                                </button>
                                <button type="button"
                                    class="rsd-btn-admin small"
                                    style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca"
                                    data-rsd-company-reject
                                    data-rsd-user-id="<?php echo (int)$client_id; ?>">
                                    ❌ Reject Changes
                                </button>
                            </div>
                        </div>
                        <table class="rsd-admin-table widefat" style="margin-top:6px">
                            <thead>
                                <tr>
                                    <th style="width:180px">Field</th>
                                    <th style="width:50%">Current Value</th>
                                    <th style="width:50%;background:#ecfdf5;color:#065f46">Requested New Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $has_row = false;
                                foreach ($fields as $k => $f) :
                                    if (!array_key_exists($k, $pending)) continue;
                                    $cur = $info->$k ?? '';
                                    if (is_string($cur) && $cur !== '' && ($cur[0] ?? '') === '{') $cur = json_decode($cur, true);
                                    $new = $pending[$k] ?? '';
                                    $cur_str = is_array($cur) ? implode(', ', $cur) : (string)$cur;
                                    $new_str = is_array($new) ? implode(', ', $new) : (string)$new;
                                    if ($cur_str === '' && $new_str === '') continue;
                                    $has_row = true;
                                ?>
                                    <tr>
                                        <td style="font-weight:600;color:#475569"><?php echo esc_html($f['label']); ?></td>
                                        <td><?php echo $cur_str === '' ? '<span style="color:#94a3b8;font-style:italic">— Empty —</span>' : esc_html($cur_str); ?></td>
                                        <td style="background:#ecfdf5;color:#065f46;font-weight:600"><?php echo $new_str === '' ? '<span style="color:#94a3b8;font-style:italic">— Empty —</span>' : esc_html($new_str); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$has_row) : ?>
                                    <tr>
                                        <td colspan="3" style="color:#94a3b8;text-align:center;padding:14px">There are no changes to compare.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:16px">📝 Request Document from Client</div>
            <form method="post" id="rsd-admin-req-doc" data-rsd-ajax-form="request_document">
                <input type="hidden" name="user_id" value="<?php echo (int)$client_id; ?>">
                <div class="rsd-admin-form-row">
                    <label>Document Title <span style="color:#ef4444">*</span></label>
                    <input type="text" name="title" required placeholder="e.g.: Send a photo of the property advertisement bills">
                </div>
                <div class="rsd-admin-form-row rsd-admin-cols-2">
                    <div>
                        <label>Relevant period</label>
                        <input type="text" name="period" placeholder="e.g.: First two months of 2024">
                    </div>
                    <div>
                        <label>Expiry date / submission deadline</label>
                        <?php rsd_fp_render_jalali_triple('expiry_date', 'rsd-client-doc-expiry', false, ''); ?>
                        <span style="color:#94a3b8;font-size:11px;margin-inline-end:4px">Select the deadline year, month and day (optional)</span>
                    </div>
                </div>
                <div class="rsd-admin-form-row">
                    <label>Description / more details</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button class="rsd-btn-admin primary" type="submit">📨 Send Request</button>
            </form>
        </div>
    </div>

    <!-- Client invoices section -->
    <div class="rsd-admin-card" style="margin-top:24px">
        <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
            <span>📑 This client's invoices</span>
            <button type="button" class="rsd-btn-admin small primary" data-rsd-modal="rsd-client-invoice-modal" data-rsd-invoice-mode="create">
                ➕ New Invoice for this Client
            </button>
        </div>
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table widefat">
                <thead>
                    <tr>
                        <th style="width:130px">Invoice Number</th>
                        <th>Description / Period</th>
                        <th style="text-align:left;width:150px">Total Amount (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</th>
                        <th style="text-align:left;width:150px">Paid (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</th>
                        <th style="text-align:left;width:150px">Balance (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th style="width:220px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($client_invoices)) : foreach ($client_invoices as $i) :
                            $sc = $status_colors[$i->status] ?? 'gray';
                            $outstanding = max(0, (float)($i->total_amount ?? 0) - (float)($i->paid_amount ?? 0));
                            $single_url = admin_url('admin.php?page=rsd-fp-invoices&view=single&id=' . (int)$i->id);
                            $print_url  = Invoices::get_print_url((int)$i->id);
                            $due_date_display = '';
                            $is_overdue = false;
                            if (!empty($i->due_date)) {
                                $due_date_display = function_exists('rsd_fp_jalali_date')
                                    ? rsd_fp_jalali_date($i->due_date)
                                    : date_i18n('j F Y', strtotime($i->due_date));
                                $is_overdue = strtotime($i->due_date) < time() && $outstanding > 0;
                            }
                    ?>
                            <tr>
                                <td><span style="font-family:monospace;font-weight:700;color:#0369a1">#<?php echo esc_html($i->invoice_number ?? $i->id); ?></span></td>
                                <td style="color:#0f172a;font-weight:600">
                                    <?php
                                    $period = [];
                                    if (!empty($i->year))   $period[] = 'Year ' . $i->year;
                                    if (!empty($i->month))  $period[] = $i->month;
                                    echo !empty($i->description) ? esc_html($i->description) : esc_html(implode(' - ', $period) ?: '—');
                                    ?>
                                </td>
                                <td style="text-align:left;direction:ltr;font-family:monospace;color:#0f172a;font-weight:700">
                                    <?php echo esc_html(rsd_fp_currency($i->total_amount ?? 0)); ?>
                                </td>
                                <td style="text-align:left;direction:ltr;font-family:monospace;color:#059669;font-weight:700">
                                    <?php echo esc_html(rsd_fp_currency($i->paid_amount ?? 0)); ?>
                                </td>
                                <td style="text-align:left;direction:ltr;font-family:monospace;color:<?php echo $outstanding > 0 ? '#dc2626' : '#059669'; ?>;font-weight:700">
                                    <?php echo esc_html(rsd_fp_currency($outstanding)); ?>
                                </td>
                                <td style="color:#64748b;font-size:12px">
                                    <?php if ($due_date_display !== '') : ?>
                                        <?php echo esc_html($due_date_display); ?>
                                        <?php if ($is_overdue) : ?>
                                            <br><span class="rsd-admin-badge red" style="margin-top:4px;font-size:11px">Overdue</span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span style="color:#cbd5e1">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="rsd-admin-badge <?php echo $sc; ?>">
                                        <?php echo esc_html($statuses[$i->status] ?? $i->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                                        <a href="<?php echo esc_url($single_url); ?>"
                                            class="rsd-btn-admin small primary" title="Invoice details">Details</a>
                                        <a href="<?php echo esc_url($print_url); ?>" target="_blank" rel="noopener"
                                            class="rsd-btn-admin small teal" title="Download / Print PDF">📄 PDF</a>
                                        <button type="button" class="rsd-btn-admin small secondary rsd-invoice-edit-from-client"
                                            data-rsd-invoice-id="<?php echo (int)$i->id; ?>"
                                            data-rsd-modal="rsd-client-invoice-modal"
                                            data-rsd-invoice-mode="edit"
                                            data-rsd-user-id="<?php echo (int)$client_id; ?>"
                                            data-rsd-year="<?php echo esc_attr($i->year ?? ''); ?>"
                                            data-rsd-month="<?php echo esc_attr($i->month ?? ''); ?>"
                                            data-rsd-description="<?php echo esc_attr($i->description ?? ''); ?>"
                                            data-rsd-subtotal="<?php echo esc_attr($i->subtotal ?? 0); ?>"
                                            data-rsd-tax="<?php echo esc_attr($i->tax ?? 0); ?>"
                                            data-rsd-discount="<?php echo esc_attr($i->discount ?? 0); ?>"
                                            data-rsd-status="<?php echo esc_attr($i->status ?? 'unpaid'); ?>"
                                            data-rsd-invoice-date="<?php echo esc_attr($i->invoice_date ?? ''); ?>"
                                            data-rsd-due-date="<?php echo esc_attr($i->due_date ?? ''); ?>"
                                            title="Edit">✏️</button>
                                        <button type="button" class="rsd-btn-admin small danger rsd-invoice-del"
                                            data-rsd-invoice-id="<?php echo (int)$i->id; ?>"
                                            title="Delete invoice">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="8">
                                <div class="rsd-empty" style="padding:30px 10px">
                                    <div class="rsd-empty-icon">📑</div>
                                    <h3>No invoices have been recorded for this client yet</h3>
                                    <p>Click the button below to issue the first invoice.</p>
                                    <p style="margin-top:10px">
                                        <button type="button" class="rsd-btn-admin primary" data-rsd-modal="rsd-client-invoice-modal" data-rsd-invoice-mode="create">
                                            ➕ Issue First Invoice
                                        </button>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Client-scoped Invoice Modal (user_id is fixed; hidden) -->
<div id="rsd-client-invoice-modal" class="rsd-modal rsd-admin-modal" aria-hidden="true">
    <div class="rsd-modal-dialog rsd-modal-lg" role="dialog" aria-modal="true">
        <div class="rsd-modal-content">
            <div class="rsd-modal-header">
                <h3 class="rsd-modal-title" id="rsd-client-invoice-modal-title">🧾 Issue Invoice for <?php echo esc_html($client ? $client->display_name : 'client'); ?></h3>
                <button type="button" class="rsd-modal-close" data-rsd-modal-close aria-label="Close">&times;</button>
            </div>
            <form method="post" id="rsd-client-invoice-form" data-rsd-ajax-form="client_invoice_form">
                <input type="hidden" name="user_id" value="<?php echo (int)$client_id; ?>">
                <input type="hidden" name="invoice_id" id="rsd-client-invoice-id" value="0">
                <div class="rsd-modal-body">
                    <div class="rsd-admin-grid cols-2">
                        <div class="rsd-admin-card" style="box-shadow:none;border:1px solid #e2e8f0">
                            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:14px">👤 Client and Tax Period</div>
                            <div class="rsd-alert rsd-alert-info" style="margin-bottom:14px">
                                👤 Issuing invoice for: <strong><?php echo esc_html($client ? sprintf('%s (%s)', $client->display_name, $client->user_email) : 'current client'); ?></strong>
                            </div>

                            <div class="rsd-admin-form-row rsd-admin-cols-2">
                                <div>
                                    <label>Fiscal Year</label>
                                    <input type="text" name="year" id="rsd-client-invoice-year" maxlength="4"
                                        inputmode="numeric" dir="ltr"
                                        placeholder="2024"
                                        title="4-digit Gregorian year, e.g. 2024">
                                </div>
                                <div>
                                    <label>Month / Period</label>
                                    <input type="text" name="month" id="rsd-client-invoice-month" maxlength="50"
                                        placeholder="e.g.: January or first two months">
                                </div>
                            </div>

                            <div class="rsd-admin-form-row rsd-admin-cols-2">
                                <div>
                                    <label>Invoice Issue Date <span style="color:#ef4444">*</span></label>
                                    <?php rsd_fp_render_jalali_triple('invoice_date', 'rsd-client-invoice-invdate', true, $today_j); ?>
                                </div>
                                <div>
                                    <label>Due Date <span style="color:#64748b">(optional)</span></label>
                                    <?php rsd_fp_render_jalali_triple('due_date', 'rsd-client-invoice-duedate', false, ''); ?>
                                </div>
                            </div>

                            <div class="rsd-admin-form-row">
                                <label>Initial Status</label>
                                <select name="status" id="rsd-client-invoice-status">
                                    <?php foreach ($statuses as $k => $lbl) :
                                        if ($k === 'overdue' || $k === 'void') continue;
                                        echo '<option value="' . esc_attr($k) . '"' . selected($k, 'unpaid', false) . '>' . esc_html($lbl) . '</option>';
                                    endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="rsd-admin-card" style="box-shadow:none;border:1px solid #e2e8f0">
                            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:14px">💰 Amounts and Description (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</div>

                            <div class="rsd-admin-form-row">
                                <label>Invoice description / notes</label>
                                <textarea name="description" id="rsd-client-invoice-description" rows="3"
                                    placeholder="e.g.: Accounting services invoice for 2024 - Q1"></textarea>
                            </div>

                            <div class="rsd-admin-form-row rsd-admin-cols-3">
                                <div>
                                    <label>Subtotal (<?php echo esc_html(rsd_fp_currency_symbol()); ?>, before tax) <span style="color:#ef4444">*</span></label>
                                    <input type="number" min="0" step="1" name="subtotal" id="rsd-client-invoice-subtotal"
                                        dir="ltr" inputmode="numeric" value="0" required>
                                </div>
                                <div>
                                    <label>Tax (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</label>
                                    <input type="number" min="0" step="1" name="tax" id="rsd-client-invoice-tax"
                                        dir="ltr" inputmode="numeric" value="0">
                                </div>
                                <div>
                                    <label>Discount (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</label>
                                    <input type="number" min="0" step="1" name="discount" id="rsd-client-invoice-discount"
                                        dir="ltr" inputmode="numeric" value="0">
                                </div>
                            </div>

                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-top:10px">
                                <div class="rsd-admin-form-row rsd-admin-cols-2">
                                    <div>
                                        <label style="font-weight:700;color:#0369a1">Invoice Total (<?php echo esc_html(rsd_fp_currency_symbol()); ?>, automatic)</label>
                                        <div id="rsd-client-invoice-total-preview"
                                            style="font-family:monospace;direction:ltr;font-size:20px;font-weight:800;color:#0369a1;text-align:left;padding:6px 0">0 <span style="font-size:13px;color:#64748b;font-weight:600"><?php echo esc_html(rsd_fp_currency_symbol()); ?></span></div>
                                    </div>
                                    <div>
                                        <label>Initial Payment at Issue (<?php echo esc_html(rsd_fp_currency_symbol()); ?>)</label>
                                        <input type="number" min="0" step="1" name="paid_amount" id="rsd-client-invoice-paid"
                                            dir="ltr" inputmode="numeric" value="0">
                                        <select name="payment_method" id="rsd-client-invoice-pm" style="margin-top:6px">
                                            <option value="">Initial payment method (optional)</option>
                                            <?php foreach ($pay_methods as $k => $lbl) : ?>
                                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($lbl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="rsd-admin-form-row" id="rsd-client-invoice-edit-hint" style="display:none">
                                <div class="rsd-alert rsd-alert-info">
                                    ℹ️ In edit mode, use the "Record Payment" button on the invoice details page to record a new payment.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rsd-modal-footer" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                    <div id="rsd-client-invoice-form-msg" style="flex:1;color:#64748b;font-size:13px;align-self:center"></div>
                    <div style="display:flex;gap:8px">
                        <button type="button" class="rsd-btn-admin secondary" data-rsd-modal-close>Cancel</button>
                        <button type="submit" class="rsd-btn-admin primary" id="rsd-client-invoice-submit-btn">💾 Save Invoice</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    /* Live recalc for client invoice modal + wire all three amount inputs */
    (function() {
        window.RSD_FP_ADMIN_INV = window.RSD_FP_ADMIN_INV || {};
        var fmt = function(n) {
            n = Number(n) || 0;
            try {
                return n.toLocaleString('en-US');
            } catch (e) {
                return n.toLocaleString('en-US');
            }
        };
        window.RSD_FP_ADMIN_INV.recalc_client = function() {
            var ids = ['rsd-client-invoice-subtotal', 'rsd-client-invoice-tax', 'rsd-client-invoice-discount'];
            var v = [0, 0, 0];
            for (var i = 0; i < 3; i++) {
                var el = document.getElementById(ids[i]);
                v[i] = el ? (Number(el.value) || 0) : 0;
            }
            var total = Math.max(0, v[0] + v[1] - v[2]);
            var out = document.getElementById('rsd-client-invoice-total-preview');
            if (out) out.innerHTML = fmt(total) + ' <span style="font-size:13px;color:#64748b;font-weight:600"><?php echo esc_html(rsd_fp_currency_symbol()); ?></span>';
        };
        ['rsd-client-invoice-subtotal', 'rsd-client-invoice-tax', 'rsd-client-invoice-discount'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', window.RSD_FP_ADMIN_INV.recalc_client);
        });
        if (window.RSD_FP_ADMIN_INV && typeof window.RSD_FP_ADMIN_INV.recalc_client === 'function') {
            window.RSD_FP_ADMIN_INV.recalc_client();
        }
    })();
</script>