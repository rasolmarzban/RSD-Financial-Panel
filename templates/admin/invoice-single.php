<?php

/**
 * Admin Template: Single Invoice View
 *
 * @var object $invoice
 * @var array $payments
 * @var WP_User|null $client
 */
defined('ABSPATH') || exit;

use RSD\FinancePanel\Modules\Invoices;

$statuses    = Invoices::get_statuses();
$pay_methods = Invoices::get_payment_methods();
$status_colors = [
    'paid'    => 'green',
    'partial' => 'yellow',
    'unpaid'  => 'orange',
    'overdue' => 'red',
    'void'    => 'gray',
];
$back_url = admin_url('admin.php?page=rsd-fp-invoices');
?>
<div class="rsd-admin-wrap">
    <?php if (!$invoice) : ?>
        <div class="rsd-admin-card">
            <div class="rsd-empty">
                <div class="rsd-empty-icon">📑</div>
                <h3>فاکتور پیدا نشد</h3>
                <a href="<?php echo esc_url($back_url); ?>" class="rsd-btn-admin primary">بازگشت به لیست فاکتورها</a>
            </div>
        </div>
</div>
<?php return;
    endif;

    $total = (float)($invoice->total_amount ?? 0);
    $paid  = (float)($invoice->paid_amount ?? 0);
    $outstanding = max(0, $total - $paid);
    $progress_pct = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;
    $sc = $status_colors[$invoice->status] ?? 'gray';
?>

<div class="rsd-admin-header">
    <div style="flex:1">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap">
            <span style="font-family:monospace;font-weight:700;color:#0369a1;font-size:18px">
                #<?php echo esc_html($invoice->invoice_number ?? $invoice->id); ?>
            </span>
            <span class="rsd-admin-badge <?php echo $sc; ?>" style="font-size:13px;padding:5px 12px">
                <?php echo esc_html($statuses[$invoice->status] ?? $invoice->status); ?>
            </span>
        </div>
        <h1 style="margin:0;font-size:24px;line-height:1.4;color:#0f172a">
            فاکتور <?php
                    $period = [];
                    if (!empty($invoice->year))   $period[] = 'سال ' . $invoice->year;
                    if (!empty($invoice->month))  $period[] = $invoice->month;
                    if (!empty($invoice->period)) $period[] = $invoice->period;
                    echo !empty($invoice->description) ? esc_html($invoice->description) : esc_html(implode(' - ', $period) ?: '#' . $invoice->id);
                    ?>
        </h1>
        <div style="margin-top:8px;display:flex;gap:18px;flex-wrap:wrap;color:#475569;font-size:13px">
            <span>👤 مشتری: <strong style="color:#0f172a"><?php echo esc_html($client ? $client->display_name : 'کاربر #' . $invoice->user_id); ?></strong></span>
            <span>📅 صدور: <strong><?php echo esc_html(date_i18n('j F Y', strtotime($invoice->issue_date ?? $invoice->created_at))); ?></strong></span>
            <?php if (!empty($invoice->due_date)) : ?>
                <span>⏰ سررسید: <strong><?php echo esc_html(date_i18n('j F Y', strtotime($invoice->due_date))); ?></strong></span>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
        <a href="<?php echo esc_url($back_url); ?>" class="rsd-btn-admin secondary">← بازگشت</a>
        <a href="<?php echo esc_url(get_edit_user_link($invoice->user_id)); ?>" target="_blank" rel="noopener"
            class="rsd-btn-admin secondary">👥 پروفایل مشتری</a>
        <a href="<?php echo esc_url(add_query_arg(['client_id' => $invoice->user_id], admin_url('admin.php?page=rsd-fp-tickets'))); ?>"
            class="rsd-btn-admin secondary">🎟️ تیکت‌ها</a>
    </div>
</div>

<!-- Amount Summary -->
<div class="rsd-admin-stats-row" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
    <div class="rsd-admin-stat-card purple">
        <div class="stat-top">
            <div class="stat-label">مبلغ کل فاکتور</div>
            <div class="stat-icon">📑</div>
        </div>
        <div class="stat-value" style="direction:ltr"><?php echo esc_html(rsd_fp_currency($total)); ?></div>
    </div>
    <div class="rsd-admin-stat-card green">
        <div class="stat-top">
            <div class="stat-label">مبلغ پرداخت‌شده</div>
            <div class="stat-icon">✅</div>
        </div>
        <div class="stat-value" style="direction:ltr"><?php echo esc_html(rsd_fp_currency($paid)); ?></div>
        <div class="stat-sub"><?php echo (int)$progress_pct; ?>٪ پرداخت‌شده</div>
    </div>
    <div class="rsd-admin-stat-card red">
        <div class="stat-top">
            <div class="stat-label">مانده قابل پرداخت</div>
            <div class="stat-icon">💳</div>
        </div>
        <div class="stat-value" style="direction:ltr"><?php echo esc_html(rsd_fp_currency($outstanding)); ?></div>
    </div>
    <div class="rsd-admin-stat-card blue">
        <div class="stat-top">
            <div class="stat-label">تعداد پرداخت‌ها</div>
            <div class="stat-icon">📋</div>
        </div>
        <div class="stat-value"><?php echo (int)count($payments); ?> پرداخت</div>
    </div>
</div>

<!-- Progress bar -->
<div class="rsd-admin-card" style="padding:18px 24px;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:14px;justify-content:space-between;margin-bottom:8px">
        <span style="font-weight:700;color:#0f172a">پیشرفت تسویه فاکتور</span>
        <span style="font-weight:700;color:#6366f1"><?php echo (int)$progress_pct; ?>٪</span>
    </div>
    <div style="width:100%;height:14px;background:#e2e8f0;border-radius:99px;overflow:hidden">
        <div style="height:100%;width:<?php echo (int)$progress_pct; ?>%;background:linear-gradient(90deg,#22c55e 0%, #6366f1 100%);border-radius:99px;transition:width .3s ease"></div>
    </div>
</div>

<div class="rsd-admin-grid cols-2">
    <!-- Invoice Details -->
    <div class="rsd-admin-card">
        <div class="rsd-admin-card-header">
            <div class="rsd-admin-card-title"><span class="rsd-icon">📄</span> اطلاعات فاکتور</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;padding:4px">
            <div class="rsd-info-row"><span>🆔 شناسه فاکتور</span><strong>#<?php echo (int)$invoice->id; ?></strong></div>
            <div class="rsd-info-row"><span>📂 سال مالی</span><strong><?php echo !empty($invoice->year) ? esc_html($invoice->year) : '<span style="color:#cbd5e1">—</span>'; ?></strong></div>
            <div class="rsd-info-row"><span>🗓️ دوره</span><strong><?php echo !empty($invoice->month) ? esc_html($invoice->month) : (!empty($invoice->period) ? esc_html($invoice->period) : '<span style="color:#cbd5e1">—</span>'); ?></strong></div>
            <div class="rsd-info-row"><span>📝 توضیحات</span><strong style="max-width:320px;word-break:break-word"><?php echo !empty($invoice->description) ? esc_html($invoice->description) : '<span style="color:#cbd5e1">—</span>'; ?></strong></div>
            <div class="rsd-info-row"><span>📅 تاریخ صدور</span><strong><?php echo esc_html(date_i18n('j F Y', strtotime($invoice->issue_date ?? $invoice->created_at))); ?></strong></div>
            <div class="rsd-info-row"><span>⏰ تاریخ سررسید</span><strong><?php echo !empty($invoice->due_date) ? esc_html(date_i18n('j F Y', strtotime($invoice->due_date))) : '<span style="color:#cbd5e1">—</span>'; ?></strong></div>
            <div class="rsd-info-row"><span>🏷️ وضعیت</span><strong><span class="rsd-admin-badge <?php echo $sc; ?>"><?php echo esc_html($statuses[$invoice->status] ?? $invoice->status); ?></span></strong></div>
            <?php if (!empty($invoice->notes)) : ?>
                <div class="rsd-info-row" style="align-items:flex-start;gap:12px"><span>📝 یادداشت‌ها</span>
                    <div style="flex:1;background:#f8fafc;border:1px solid #e2e8f0;padding:10px 12px;border-radius:8px;color:#475569;line-height:1.8"><?php echo wp_kses_post(nl2br(make_clickable($invoice->notes))); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Client & payments -->
    <div class="rsd-admin-card">
        <div class="rsd-admin-card-header">
            <div class="rsd-admin-card-title"><span class="rsd-icon">👤</span> اطلاعات مشتری</div>
        </div>
        <?php if ($client) : ?>
            <div style="display:flex;gap:14px;align-items:center;padding:6px 0 14px;border-bottom:1px dashed #e2e8f0;margin-bottom:14px">
                <?php echo get_avatar($client->ID, 60); ?>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:800;color:#0f172a;font-size:16px"><?php echo esc_html($client->display_name); ?></div>
                    <div style="color:#64748b;font-size:13px">@<?php echo esc_html($client->user_login); ?> · 📧 <?php echo esc_html($client->user_email); ?></div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
                <a href="<?php echo esc_url(add_query_arg(['view' => 'client', 'id' => $client->ID], admin_url('admin.php?page=rsd-fp-clients'))); ?>"
                    class="rsd-btn-admin small primary" style="flex:1;justify-content:center">💼 پرتفویل مالی</a>
                <a href="<?php echo esc_url(get_edit_user_link($client->ID)); ?>" target="_blank" rel="noopener"
                    class="rsd-btn-admin small secondary" style="flex:1;justify-content:center">✏️ ویرایش کاربر</a>
            </div>
        <?php endif; ?>

        <div class="rsd-admin-card-title" style="margin-bottom:10px">
            <span class="rsd-icon">💰</span> ثبت پرداخت جدید
        </div>
        <form id="rsd-new-payment-form" method="post" data-rsd-ajax-form="admin_add_payment" style="display:flex;flex-direction:column;gap:10px"
            <?php wp_nonce_field('rsd_fp_nonce', 'nonce_placeholder'); ?>>
            <?php wp_nonce_field('rsd_fp_nonce', 'nonce'); ?>
            <input type="hidden" name="invoice_id" value="<?php echo (int)$invoice->id; ?>">
            <input type="hidden" name="user_id" value="<?php echo (int)$invoice->user_id; ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;display:block">مبلغ (تومان)</label>
                    <input type="number" name="amount" step="100" min="0" max="<?php echo esc_attr($outstanding); ?>"
                        value="<?php echo esc_attr($outstanding); ?>" required style="width:100%;direction:ltr">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;display:block">تاریخ پرداخت</label>
                    <?php
                    $today_gy = (int)date('Y');
                    $today_gm = (int)date('n');
                    $today_gd = (int)date('j');
                    $t_jparts = function_exists('rsd_fp_gregorian_to_jalali_parts')
                        ? rsd_fp_gregorian_to_jalali_parts($today_gy, $today_gm, $today_gd)
                        : [$today_gy, $today_gm, $today_gd];
                    $pay_default = sprintf('%04d/%02d/%02d', (int)($t_jparts[0] ?? $today_gy), (int)($t_jparts[1] ?? $today_gm), (int)($t_jparts[2] ?? $today_gd));
                    rsd_fp_render_jalali_triple('payment_date', 'rsd-pay-date', true, $pay_default);
                    ?>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;display:block">روش پرداخت</label>
                    <select name="payment_method" style="width:100%">
                        <?php foreach ($pay_methods as $k => $v) : ?>
                            <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;display:block">وضعیت</label>
                    <select name="status" style="width:100%">
                        <option value="completed">پرداخت شده</option>
                        <option value="pending">در حال انتظار</option>
                        <option value="failed">ناموفق</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#475569;margin-bottom:4px;display:block">شماره پیگیری / یادداشت</label>
                <input type="text" name="reference" placeholder="مثلاً شماره رسید / چک / تراکنش بانکی" style="width:100%">
            </div>
            <button type="submit" class="rsd-btn-admin primary" style="align-self:flex-start">
                💰 ثبت پرداخت
            </button>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="rsd-admin-card">
    <div class="rsd-admin-card-header">
        <div class="rsd-admin-card-title">
            <span class="rsd-icon">💳</span> سابقه پرداخت‌ها
        </div>
        <strong style="color:#64748b;font-size:13px"><?php echo (int) count($payments); ?> رکورد</strong>
    </div>
    <div class="rsd-admin-table-wrap">
        <table class="rsd-admin-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>تاریخ پرداخت</th>
                    <th>مبلغ</th>
                    <th>روش پرداخت</th>
                    <th>شماره پیگیری</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)) : foreach ($payments as $idx => $p) :
                        $pm_color = ['completed' => 'green', 'pending' => 'yellow', 'failed' => 'red', 'void' => 'gray'];
                        $pm_labels = ['completed' => 'پرداخت شده', 'pending' => 'در انتظار', 'failed' => 'ناموفق', 'void' => 'لغوشده'];
                        $c = $pm_color[$p->status] ?? 'gray';
                ?>
                        <tr>
                            <td style="font-family:monospace;color:#64748b"><?php echo (int)($idx + 1); ?></td>
                            <td style="color:#0f172a;font-weight:600"><?php echo !empty($p->payment_date) ? esc_html(date_i18n('j F Y', strtotime($p->payment_date))) : '<span style="color:#cbd5e1">—</span>'; ?></td>
                            <td style="direction:ltr;font-family:monospace;color:#059669;font-weight:700"><?php echo esc_html(rsd_fp_currency($p->amount ?? 0)); ?></td>
                            <td><?php echo isset($pay_methods[$p->payment_method]) ? esc_html($pay_methods[$p->payment_method]) : esc_html($p->payment_method); ?></td>
                            <td style="font-family:monospace;color:#475569"><?php echo !empty($p->reference) ? esc_html($p->reference) : '<span style="color:#cbd5e1">—</span>'; ?></td>
                            <td><span class="rsd-admin-badge <?php echo $c; ?>"><?php echo esc_html($pm_labels[$p->status] ?? $p->status); ?></span></td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="6">
                            <div class="rsd-empty">
                                <p>هنوز پرداختی برای این فاکتور ثبت نشده است.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<script type="text/javascript">
    jQuery(function($) {
        // AJAX handler for payment form (falls back to default submit when no handler)
        $('#rsd-new-payment-form').on('submit', function(e) {
            e.preventDefault();
            alert('در نسخه فعلی، ثبت پرداخت از طریق بانک / دستی به درگاه توسعه پرداخت منتقل شده است. برای تست می‌توانید از طریق ابزار CLI پرداخت ثبت کنید یا مستقیماً از جدول payments در پایگاه‌داده استفاده کنید.');
        });
    });
</script>
<style type="text/css">
    .rsd-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        gap: 14px;
    }

    .rsd-info-row>span:first-child {
        color: #64748b;
        font-weight: 500;
        font-size: 13px
    }

    .rsd-info-row>strong {
        color: #0f172a;
        font-weight: 700
    }
</style>