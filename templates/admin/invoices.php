<?php

/**
 * Admin Template: Invoices Management
 *
 * @var array $invoices
 * @var array $clients
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
$client_sel = $clients ?? [];
$back_url   = admin_url('admin.php?page=rsd-fp-invoices');

/* Helper: Build jalali triple-select (year/month/day) that feeds a hidden target input with YYYY/MM/DD */
if (!function_exists('rsd_fp_render_jalali_triple')) {
    function rsd_fp_render_jalali_triple($target_name, $prefix, $required = false, $default_value = '')
    {
        $persian_months = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];
        $current_jalali_y = 1404;
        if (function_exists('rsd_fp_current_jalali_year')) {
            $current_jalali_y = (int) rsd_fp_current_jalali_year();
        }
        $years = range(max(1380, $current_jalali_y - 20), $current_jalali_y + 5);
        $days  = range(1, 31);

        $sel_y = '';
        $sel_m = '';
        $sel_d = '';
        $dv = trim((string)$default_value);
        if ($dv !== '' && preg_match('#^([1-9]\d{3})[\-\/](0?[1-9]|1[0-2])[\-\/](0?[1-9]|[12]\d|3[01])$#', $dv, $mm)) {
            $yy = (int)$mm[1];
            if ($yy >= 1300 && $yy <= 2000) {
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
                <option value="">سال</option>
                <?php foreach (array_reverse($years, true) as $y) :
                    $y = (string)$y;
                    $s = selected($sel_y, $y, false);
                    echo '<option value="' . esc_attr($y) . '"' . $s . '>' . esc_html($y) . '</option>';
                endforeach; ?>
            </select>
            <select class="rsd-triple-month" data-rsd-part="m" <?php echo $req; ?>>
                <option value="">ماه</option>
                <?php foreach ($persian_months as $mn => $ml) :
                    $s = selected((string)$sel_m, (string)$mn, false);
                    echo '<option value="' . esc_attr($mn) . '"' . $s . '>' . esc_html($ml) . '</option>';
                endforeach; ?>
            </select>
            <select class="rsd-triple-day" data-rsd-part="d" <?php echo $req; ?>>
                <option value="">روز</option>
                <?php foreach ($days as $d) :
                    $s = selected((string)$sel_d, (string)$d, false);
                    echo '<option value="' . esc_attr($d) . '"' . $s . '>' . esc_html($d) . '</option>';
                endforeach; ?>
            </select>
        </div>
<?php
    }
}
/* Helper: default today in jalali YYYY/MM/DD for invoice_date */
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
                if ($yy >= 1300 && $yy <= 2000) {
                    return $mm[1] . '/' . $pn($mm[2]) . '/' . $pn($mm[3]);
                }
            }
        }
    }
    // Hard fallback: use the jalali parts algorithm directly
    if (function_exists('rsd_fp_gregorian_to_jalali_parts')) {
        [$gy, $gm, $gd] = [(int)date('Y'), (int)date('n'), (int)date('j')];
        [$jy, $jm, $jd] = rsd_fp_gregorian_to_jalali_parts($gy, $gm, $gd);
        if ($jy >= 1300 && $jy <= 2000) {
            return $jy . '/' . $pn($jm) . '/' . $pn($jd);
        }
    }
    return '';
}
$today_j = rsd_fp_today_jalali_ymd();
?>
<div class="rsd-admin-wrap" id="rsd-admin-invoices-page">
    <div class="rsd-admin-header">
        <div>
            <h1>📑 مدیریت فاکتورها</h1>
            <p>صدور، پیگیری و ثبت پرداخت فاکتورهای مشتریان</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="rsd-btn-admin primary" data-rsd-modal="rsd-invoice-modal" data-rsd-invoice-mode="create">
                ➕ فاکتور جدید
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-clients')); ?>"
                class="rsd-btn-admin secondary">👥 لیست مشتریان</a>
        </div>
    </div>

    <div class="rsd-admin-card">
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table widefat">
                <thead>
                    <tr>
                        <th style="width:130px">شماره فاکتور</th>
                        <th>مشتری</th>
                        <th>توضیح / دوره</th>
                        <th style="text-align:left;width:160px">مبلغ کل (تومان)</th>
                        <th style="text-align:left;width:160px">پرداخت‌شده (تومان)</th>
                        <th style="text-align:left;width:160px">مانده (تومان)</th>
                        <th>سررسید</th>
                        <th>وضعیت</th>
                        <th style="width:220px">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)) : foreach ($invoices as $i) :
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
                            <tr data-rsd-invoice-row="<?php echo (int)$i->id; ?>">
                                <td><span style="font-family:monospace;font-weight:700;color:#0369a1">
                                        #<?php echo esc_html($i->invoice_number ?? $i->id); ?>
                                    </span></td>
                                <td>
                                    <div class="rsd-user-chip">
                                        <?php echo get_avatar($i->user_id, 28); ?>
                                        <div style="display:flex;flex-direction:column;gap:2px">
                                            <span style="font-weight:600"><?php echo esc_html($i->client_name ?? 'کاربر #' . $i->user_id); ?></span>
                                            <span style="color:#64748b;font-size:11px"><?php echo esc_html(get_userdata($i->user_id)->user_email ?? ''); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="color:#0f172a;font-weight:600">
                                    <?php
                                    $period = [];
                                    if (!empty($i->year))   $period[] = 'سال ' . $i->year;
                                    if (!empty($i->month))  $period[] = $i->month;
                                    if (!empty($i->period)) $period[] = $i->period;
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
                                            <br><span class="rsd-admin-badge red" style="margin-top:4px;font-size:11px">سررسید گذشته</span>
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
                                            class="rsd-btn-admin small primary" title="مشاهده جزئیات">جزئیات</a>
                                        <a href="<?php echo esc_url($print_url); ?>" target="_blank" rel="noopener"
                                            class="rsd-btn-admin small teal" title="دانلود / چاپ PDF">📄 PDF</a>
                                        <button type="button" class="rsd-btn-admin small secondary"
                                            data-rsd-modal="rsd-invoice-modal"
                                            data-rsd-invoice-mode="edit"
                                            data-rsd-invoice-id="<?php echo (int)$i->id; ?>"
                                            data-rsd-user-id="<?php echo (int)$i->user_id; ?>"
                                            data-rsd-year="<?php echo esc_attr($i->year ?? ''); ?>"
                                            data-rsd-month="<?php echo esc_attr($i->month ?? ''); ?>"
                                            data-rsd-description="<?php echo esc_attr($i->description ?? ''); ?>"
                                            data-rsd-subtotal="<?php echo esc_attr($i->subtotal ?? 0); ?>"
                                            data-rsd-tax="<?php echo esc_attr($i->tax ?? 0); ?>"
                                            data-rsd-discount="<?php echo esc_attr($i->discount ?? 0); ?>"
                                            data-rsd-status="<?php echo esc_attr($i->status ?? 'unpaid'); ?>"
                                            data-rsd-invoice-date="<?php echo esc_attr($i->invoice_date ?? ''); ?>"
                                            data-rsd-due-date="<?php echo esc_attr($i->due_date ?? ''); ?>"
                                            title="ویرایش فاکتور">✏️</button>
                                        <button type="button" class="rsd-btn-admin small danger rsd-invoice-del"
                                            data-rsd-invoice-id="<?php echo (int)$i->id; ?>"
                                            title="حذف فاکتور">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="9">
                                <div class="rsd-empty">
                                    <div class="rsd-empty-icon">📑</div>
                                    <h3>هنوز فاکتوری ثبت نشده</h3>
                                    <p>برای صدور فاکتور جدید روی دکمه «➕ فاکتور جدید» در بالا کلیک کنید.</p>
                                    <p style="margin-top:10px">
                                        <button type="button" class="rsd-btn-admin primary" data-rsd-modal="rsd-invoice-modal" data-rsd-invoice-mode="create">
                                            ➕ صدور اولین فاکتور
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

<!-- Invoice Create / Edit Modal -->
<div id="rsd-invoice-modal" class="rsd-modal rsd-admin-modal" aria-hidden="true">
    <div class="rsd-modal-dialog rsd-modal-lg" role="dialog" aria-modal="true">
        <div class="rsd-modal-content">
            <div class="rsd-modal-header">
                <h3 class="rsd-modal-title" id="rsd-invoice-modal-title">➕ فاکتور جدید</h3>
                <button type="button" class="rsd-modal-close" data-rsd-modal-close aria-label="بستن">&times;</button>
            </div>
            <form method="post" id="rsd-admin-invoice-form" class="rsd-invoice-form" data-rsd-ajax-form="create_invoice">
                <input type="hidden" name="invoice_id" id="rsd-invoice-id" value="0">
                <div class="rsd-modal-body">
                    <div class="rsd-admin-grid cols-2">
                        <div class="rsd-admin-card" style="box-shadow:none;border:1px solid #e2e8f0">
                            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:14px">👤 انتخاب مشتری و دوره</div>

                            <div class="rsd-admin-form-row">
                                <label>مشتری <span style="color:#ef4444">*</span></label>
                                <input type="hidden" name="user_id" id="rsd-invoice-user-id" value="">
                                <div class="rsd-client-search-wrap" data-rsd-client-store="#rsd-invoice-user-id">
                                    <input type="text" id="rsd-invoice-client-input" required
                                        list="rsd-invoice-clients-datalist"
                                        class="rsd-client-search-input widefat"
                                        placeholder="نام / نام کاربری / ایمیل مشتری را جستجو کنید...">
                                    <datalist id="rsd-invoice-clients-datalist">
                                        <?php foreach ($client_sel as $c) : ?>
                                            <option value="<?php echo esc_attr(sprintf('%s — %s (%s)', $c->display_name, $c->user_login, $c->user_email)); ?>"
                                                data-user-id="<?php echo (int)$c->ID; ?>">
                                                <?php echo esc_html(sprintf('%s (%s — %s)', $c->display_name, $c->user_login, $c->user_email)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </datalist>
                                    <div style="color:#94a3b8;font-size:11px;margin-top:4px">
                                        شروع به تایپ کنید تا مشتری مورد نظر را پیدا کنید.
                                    </div>
                                </div>
                            </div>

                            <div class="rsd-admin-form-row rsd-admin-cols-2">
                                <div>
                                    <label>سال مالی</label>
                                    <input type="text" name="year" id="rsd-invoice-year" maxlength="4"
                                        inputmode="numeric" dir="ltr"
                                        placeholder="۱۴۰۴"
                                        title="۴ رقم سال شمسی، مثلاً ۱۴۰۴">
                                </div>
                                <div>
                                    <label>ماه / دوره</label>
                                    <input type="text" name="month" maxlength="50"
                                        placeholder="مثلاً: فروردین یا دوماهه اول">
                                </div>
                            </div>

                            <div class="rsd-admin-form-row rsd-admin-cols-2">
                                <div>
                                    <label>تاریخ صدور فاکتور <span style="color:#ef4444">*</span></label>
                                    <?php rsd_fp_render_jalali_triple('invoice_date', 'rsd-invoice-invdate', true, $today_j); ?>
                                </div>
                                <div>
                                    <label>تاریخ سررسید <span style="color:#64748b">(اختیاری)</span></label>
                                    <?php rsd_fp_render_jalali_triple('due_date', 'rsd-invoice-duedate', false, ''); ?>
                                </div>
                            </div>

                            <div class="rsd-admin-form-row">
                                <label>وضعیت اولیه</label>
                                <select name="status">
                                    <?php foreach ($statuses as $k => $lbl) :
                                        if ($k === 'overdue' || $k === 'void') continue;
                                        echo '<option value="' . esc_attr($k) . '"' . selected($k, 'unpaid', false) . '>' . esc_html($lbl) . '</option>';
                                    endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="rsd-admin-card" style="box-shadow:none;border:1px solid #e2e8f0">
                            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:14px">💰 مبالغ و توضیحات (به تومان ایران)</div>

                            <div class="rsd-admin-form-row">
                                <label>شرح / توضیح فاکتور</label>
                                <textarea name="description" rows="3"
                                    placeholder="مثلاً: صورتحساب خدمات حسابداری سال ۱۴۰۴ - فصل اول"></textarea>
                            </div>

                            <div class="rsd-admin-form-row rsd-admin-cols-3">
                                <div>
                                    <label>جمع کل (تومان، قبل از مالیات) <span style="color:#ef4444">*</span></label>
                                    <input type="number" min="0" step="1" name="subtotal" id="rsd-invoice-subtotal"
                                        dir="ltr" inputmode="numeric" value="0" required
                                        oninput="window.RSD_FP_ADMIN_INV && window.RSD_FP_ADMIN_INV.recalc()">
                                </div>
                                <div>
                                    <label>مالیات (تومان)</label>
                                    <input type="number" min="0" step="1" name="tax" id="rsd-invoice-tax"
                                        dir="ltr" inputmode="numeric" value="0"
                                        oninput="window.RSD_FP_ADMIN_INV && window.RSD_FP_ADMIN_INV.recalc()">
                                </div>
                                <div>
                                    <label>تخفیف (تومان)</label>
                                    <input type="number" min="0" step="1" name="discount" id="rsd-invoice-discount"
                                        dir="ltr" inputmode="numeric" value="0"
                                        oninput="window.RSD_FP_ADMIN_INV && window.RSD_FP_ADMIN_INV.recalc()">
                                </div>
                            </div>

                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-top:10px">
                                <div class="rsd-admin-form-row rsd-admin-cols-2">
                                    <div>
                                        <label style="font-weight:700;color:#0369a1">مبلغ کل فاکتور (تومان، خودکار)</label>
                                        <div id="rsd-invoice-total-preview"
                                            style="font-family:monospace;direction:ltr;font-size:20px;font-weight:800;color:#0369a1;text-align:left;padding:6px 0">
                                            0 <span style="font-size:13px;color:#64748b;font-weight:600">تومان</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label>پرداخت اولیه هنگام صدور (تومان)</label>
                                        <input type="number" min="0" step="1" name="paid_amount" id="rsd-invoice-paid"
                                            dir="ltr" inputmode="numeric" value="0">
                                        <select name="payment_method" id="rsd-invoice-pm" style="margin-top:6px">
                                            <option value="">روش پرداخت اولیه (اختیاری)</option>
                                            <?php foreach ($pay_methods as $k => $lbl) : ?>
                                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($lbl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="rsd-admin-form-row" id="rsd-invoice-edit-hint" style="display:none">
                                <div class="rsd-alert rsd-alert-info">
                                    ℹ️ در حالت ویرایش، مبالغ پرداخت‌شده جدید از طریق «ثبت پرداخت» در صفحه جزئیات فاکتور انجام می‌شود.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rsd-modal-footer" style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                    <div id="rsd-invoice-form-msg" style="flex:1;color:#64748b;font-size:13px;align-self:center"></div>
                    <div style="display:flex;gap:8px">
                        <button type="button" class="rsd-btn-admin secondary" data-rsd-modal-close>انصراف</button>
                        <button type="submit" class="rsd-btn-admin primary" id="rsd-invoice-submit-btn">
                            💾 ذخیره فاکتور
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    /* Inline helper: live total recalc */
    (function() {
        window.RSD_FP_ADMIN_INV = window.RSD_FP_ADMIN_INV || {};
        var fmt = function(n) {
            n = Number(n) || 0;
            try {
                return n.toLocaleString('fa-IR');
            } catch (e) {
                return n.toLocaleString('en-US');
            }
        };
        window.RSD_FP_ADMIN_INV.recalc = function() {
            var st = Number(document.getElementById('rsd-invoice-subtotal').value) || 0;
            var tx = Number(document.getElementById('rsd-invoice-tax').value) || 0;
            var ds = Number(document.getElementById('rsd-invoice-discount').value) || 0;
            var total = Math.max(0, st + tx - ds);
            var el = document.getElementById('rsd-invoice-total-preview');
            if (el) el.innerHTML = fmt(total) + ' <span style="font-size:13px;color:#64748b;font-weight:600">تومان</span>';
        };
    })();
</script>