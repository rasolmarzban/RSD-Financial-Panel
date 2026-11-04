<?php

/**
 * Admin Template: Reports Management (Full CRUD)
 *
 * @var array $reports
 * @var array $clients
 * @var int   $filter_client
 */
defined('ABSPATH') || exit;

use RSD\FinancePanel\Modules\Reports;

$rtypes = Reports::get_types();
$months = Reports::get_months();

$type_colors = [
    'tax'            => 'red',
    'vat'            => 'orange',
    'payroll'        => 'purple',
    'financial'      => 'blue',
    'audit'          => 'teal',
    'annual'         => 'green',
    'accounting'     => 'blue',
    'income_expense' => 'teal',
    'management'     => 'purple',
    'custom'         => 'gray',
    'other'          => 'gray',
];
?>
<style type="text/css">
    /* ---- Modal styles (frontend modal classes not loaded in admin) ---- */
    .rsd-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, .55);
        z-index: 999999;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 50px 20px;
        overflow-y: auto
    }

    .rsd-modal {
        background: #fff !important;
        border-radius: 14px !important;
        width: 100% !important;
        max-width: 720px !important;
        box-shadow: 0 20px 50px rgba(2, 6, 23, .25) !important;
        overflow: hidden !important;
        position: relative !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        visibility: visible !important;
        opacity: 1 !important
    }

    .rsd-modal-lg {
        max-width: 860px !important
    }

    .rsd-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc
    }

    .rsd-modal-header h3 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #0f172a
    }

    .rsd-modal-close {
        background: none;
        border: 0;
        font-size: 30px;
        line-height: 1;
        cursor: pointer;
        color: #64748b;
        padding: 0 6px
    }

    .rsd-modal-close:hover {
        color: #0f172a
    }

    .rsd-modal-body {
        padding: 22px 24px
    }

    .rsd-modal-footer {
        padding: 14px 24px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc
    }

    .rsd-modal .rsd-grid label {
        font-size: 13px
    }

    .rsd-modal input[type=text],
    .rsd-modal input[type=number],
    .rsd-modal select,
    .rsd-modal textarea {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13px
    }

    .rsd-modal input[type=text]:focus,
    .rsd-modal select:focus,
    .rsd-modal textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, .15)
    }

    #rsd-report-form .required {
        color: #dc2626
    }
</style>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>📊 مدیریت گزارش‌ها</h1>
            <p>گزارش‌های مالی، مالیاتی و حقوقی صادرشده برای مشتریان</p>
        </div>
    </div>

    <div class="rsd-admin-card" style="margin-bottom:16px;padding:16px 20px">
        <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center">
            <form method="get" action="" style="display:flex;gap:10px;align-items:center">
                <input type="hidden" name="page" value="rsd-fp-reports">
                <label style="font-weight:600;color:#334155;font-size:13px">فیلتر مشتری:</label>
                <select name="client_id" style="min-width:240px">
                    <option value="0">— همه مشتریان —</option>
                    <?php foreach ($clients as $c) : ?>
                        <option value="<?php echo (int)$c->ID; ?>" <?php selected($filter_client, (int)$c->ID); ?>>
                            <?php echo esc_html($c->display_name . ' (' . $c->user_email . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="rsd-btn-admin small secondary">اعمال فیلتر</button>
                <?php if ($filter_client > 0) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-reports')); ?>" class="rsd-btn-admin small light">پاک کردن</a>
                <?php endif; ?>
            </form>
            <div class="spacer" style="flex:1"></div>
            <button type="button" class="rsd-btn-admin primary" id="rsd-open-add-report">
                ＋ افزودن گزارش جدید
            </button>
        </div>
    </div>

    <div class="rsd-admin-card">
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table widefat">
                <thead>
                    <tr>
                        <th style="width:90px">شناسه</th>
                        <th>عنوان گزارش</th>
                        <th>مشتری</th>
                        <th>نوع گزارش</th>
                        <th>دوره / سال</th>
                        <th>تاریخ صدور</th>
                        <th>فایل</th>
                        <th style="width:230px">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reports)) : foreach ($reports as $r) :
                            $tc = $type_colors[$r->report_type] ?? 'gray';
                    ?>
                            <tr data-report-row="<?php echo (int)$r->id; ?>">
                                <td><span style="font-family:monospace;font-weight:700;color:#0369a1">#<?php echo (int)$r->id; ?></span></td>
                                <td style="color:#0f172a;font-weight:600">
                                    <?php echo !empty($r->title) ? esc_html($r->title) : '<span style="color:#94a3b8">بدون عنوان</span>'; ?>
                                    <?php if (!empty($r->description)) : ?>
                                        <div style="font-size:11px;color:#64748b;margin-top:4px;font-weight:400;max-width:380px;white-space:normal;line-height:1.6">
                                            <?php echo esc_html(wp_trim_words($r->description, 14, '…')); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rsd-user-chip">
                                        <?php echo get_avatar($r->user_id, 28); ?>
                                        <div style="display:flex;flex-direction:column;gap:2px">
                                            <span style="font-weight:600"><?php echo esc_html($r->client_name ?? 'کاربر #' . $r->user_id); ?></span>
                                            <span style="color:#64748b;font-size:11px"><?php echo esc_html(get_userdata($r->user_id)->user_email ?? ''); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="rsd-admin-badge <?php echo $tc; ?>">
                                        <?php echo isset($rtypes[$r->report_type]) ? esc_html($rtypes[$r->report_type]) : esc_html($r->report_type); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $period_parts = [];
                                    if (!empty($r->year))   $period_parts[] = 'سال ' . $r->year;
                                    if (!empty($r->month))  $period_parts[] = (isset($months[$r->month]) ? $months[$r->month] : $r->month);
                                    if (!empty($r->period)) $period_parts[] = $r->period;
                                    echo !empty($period_parts) ? esc_html(implode(' / ', $period_parts)) : '<span style="color:#cbd5e1">—</span>';
                                    ?>
                                </td>
                                <td style="color:#64748b;font-size:12px">
                                    <?php echo esc_html(date_i18n('j F Y', strtotime($r->created_at))); ?>
                                </td>
                                <td>
                                    <?php if (!empty($r->file_path)) : ?>
                                        <a href="<?php echo esc_url($r->file_path); ?>" target="_blank" rel="noopener"
                                            style="color:#0369a1;font-weight:600;text-decoration:underline;display:inline-flex;align-items:center;gap:4px">
                                            📎 دانلود
                                        </a>
                                        <?php if (!empty($r->file_name)) : ?>
                                            <div style="font-size:11px;color:#94a3b8;margin-top:2px"><?php echo esc_html($r->file_name); ?></div>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span style="color:#cbd5e1">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(['client_id' => $r->user_id], admin_url('admin.php?page=rsd-fp-documents'))); ?>"
                                        class="rsd-btn-admin small secondary">مدارک</a>
                                    <button type="button" class="rsd-btn-admin small" data-rsd-edit-report
                                        data-id="<?php echo (int)$r->id; ?>"
                                        data-user_id="<?php echo (int)$r->user_id; ?>"
                                        data-title="<?php echo esc_attr($r->title); ?>"
                                        data-report_type="<?php echo esc_attr($r->report_type); ?>"
                                        data-year="<?php echo esc_attr($r->year); ?>"
                                        data-month="<?php echo esc_attr($r->month); ?>"
                                        data-period="<?php echo esc_attr($r->period); ?>"
                                        data-description="<?php echo esc_attr($r->description); ?>"
                                        data-file_path="<?php echo esc_attr($r->file_path ?? ''); ?>"
                                        data-file_name="<?php echo esc_attr($r->file_name ?? ''); ?>">
                                        ویرایش
                                    </button>
                                    <button type="button" class="rsd-btn-admin small danger" data-rsd-delete-report
                                        data-id="<?php echo (int)$r->id; ?>"
                                        data-title="<?php echo esc_attr($r->title); ?>">
                                        حذف
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="8">
                                <div class="rsd-empty">
                                    <div class="rsd-empty-icon">📊</div>
                                    <h3>هنوز گزارشی ثبت نشده</h3>
                                    <p>گزارش‌های صادره برای مشتریان در این جدول نمایش داده می‌شوند. با دکمه بالا یک گزارش جدید ثبت کنید.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== ADD / EDIT REPORT MODAL (shared) ===== -->
<div class="rsd-modal-backdrop" id="rsd-report-modal" style="display:none">
    <div class="rsd-modal rsd-modal-lg">
        <div class="rsd-modal-header">
            <h3 id="rsd-report-modal-title">＋ افزودن گزارش جدید</h3>
            <button type="button" class="rsd-modal-close" onclick="document.getElementById('rsd-report-modal').style.display='none'">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-report-form" enctype="multipart/form-data">
                <input type="hidden" name="report_id" id="rsd-rpt-id" value="0">
                <input type="hidden" name="action" value="rsd_fp_save_report">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('rsd_fp_nonce')); ?>">

                <div class="rsd-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">مشتری <span style="color:#dc2626">*</span></label>
                        <select name="user_id" id="rsd-rpt-user_id" required style="width:100%">
                            <option value="">— انتخاب مشتری —</option>
                            <?php foreach ($clients as $c) : ?>
                                <option value="<?php echo (int)$c->ID; ?>">
                                    <?php echo esc_html($c->display_name . ' (' . $c->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">نوع گزارش</label>
                        <select name="report_type" id="rsd-rpt-report_type" style="width:100%">
                            <option value="">— نامشخص —</option>
                            <?php foreach ($rtypes as $k => $v) : ?>
                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="grid-column:1 / -1">
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">عنوان گزارش <span style="color:#dc2626">*</span></label>
                        <input type="text" name="title" id="rsd-rpt-title" required placeholder="مثلاً گزارش مالیات بر ارزش افزوده مهر ۱۴۰۵" style="width:100%">
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">سال گزارش (شمسی)</label>
                        <select name="year" id="rsd-rpt-year" style="width:100%">
                            <option value="">—</option>
                            <?php for ($y = 1410; $y >= 1385; $y--) : ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">ماه گزارش</label>
                        <select name="month" id="rsd-rpt-month" style="width:100%">
                            <option value="">—</option>
                            <?php foreach ($months as $mk => $mv) : ?>
                                <option value="<?php echo esc_attr($mk); ?>"><?php echo esc_html($mv); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="grid-column:1 / -1">
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">دوره گزارش (وضعیت آزاد)</label>
                        <input type="text" name="period" id="rsd-rpt-period" placeholder="مثلاً سه‌ماهه اول یا سال کامل ۱۴۰۵" style="width:100%">
                    </div>
                    <div style="grid-column:1 / -1">
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">توضیحات</label>
                        <textarea name="description" id="rsd-rpt-description" rows="3" style="width:100%" placeholder="توضیحات تکمیلی درباره این گزارش..."></textarea>
                    </div>
                    <div style="grid-column:1 / -1">
                        <label style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a">فایل گزارش (PDF / Excel / Word / تصویر)</label>
                        <div id="rsd-rpt-current-file-wrap" style="margin-bottom:10px;display:none">
                            <div style="display:flex;gap:10px;align-items:center;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:10px 14px">
                                <span>📎 فایل فعلی:</span>
                                <a id="rsd-rpt-current-file-link" href="#" target="_blank" rel="noopener" style="color:#0369a1;font-weight:600;text-decoration:underline"></a>
                                <label style="display:inline-flex;align-items:center;gap:5px;margin-right:auto;color:#dc2626;cursor:pointer;font-size:13px">
                                    <input type="checkbox" name="remove_file" id="rsd-rpt-remove-file" value="1">
                                    حذف فایل موجود
                                </label>
                            </div>
                        </div>
                        <input type="file" name="report_file" id="rsd-rpt-file" accept=".pdf,.xls,.xlsx,.doc,.docx,.jpg,.jpeg,.png,.zip">
                        <div style="font-size:12px;color:#94a3b8;margin-top:6px">
                            حداکثر حجم طبق محدودیت سرور: بهینه ۲۰ مگابایت
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer" style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="rsd-btn-admin light" onclick="document.getElementById('rsd-report-modal').style.display='none'">انصراف</button>
            <button type="button" class="rsd-btn-admin primary" id="rsd-report-submit-btn">
                <span id="rsd-report-submit-label">ثبت گزارش</span>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(function($) {
        "use strict";

        var $modal = $('#rsd-report-modal');
        var $form = $('#rsd-report-form');
        var $title = $('#rsd-report-modal-title');
        var $submitLabel = $('#rsd-report-submit-label');
        var $submitBtn = $('#rsd-report-submit-btn');
        var $curWrap = $('#rsd-rpt-current-file-wrap');
        var $curLink = $('#rsd-rpt-current-file-link');
        var $removeFile = $('#rsd-rpt-remove-file');
        var $fileInput = $('#rsd-rpt-file');
        var ajaxurl_wp = window.ajaxurl || ajaxurl;

        function openAddModal(preselectUserId) {
            $form[0].reset();
            $('#rsd-rpt-id').val('0');
            $form.find('input[name=action]').val('rsd_fp_save_report');
            $title.text('＋ افزودن گزارش جدید');
            $submitLabel.text('ثبت گزارش');
            $curWrap.hide();
            $removeFile.prop('checked', false);
            if (preselectUserId) {
                $('#rsd-rpt-user_id').val(String(preselectUserId));
            }
            $modal.fadeIn(200);
        }

        function openEditModal(data) {
            $form[0].reset();
            $('#rsd-rpt-id').val(data.id);
            $form.find('input[name=action]').val('rsd_fp_update_report');
            $title.text('✏️ ویرایش گزارش #' + data.id);
            $submitLabel.text('ذخیره تغییرات');
            $('#rsd-rpt-user_id').val(data.user_id);
            $('#rsd-rpt-report_type').val(data.report_type);
            $('#rsd-rpt-title').val(data.title);
            $('#rsd-rpt-year').val(data.year);
            $('#rsd-rpt-month').val(data.month);
            $('#rsd-rpt-period').val(data.period);
            $('#rsd-rpt-description').val(data.description);
            if (data.file_path) {
                $curWrap.show();
                $curLink.attr('href', data.file_path).text(data.file_name || 'مشاهده فایل');
            } else {
                $curWrap.hide();
            }
            $removeFile.prop('checked', false);
            $modal.fadeIn(200);
        }

        $('#rsd-open-add-report').on('click', function() {
            openAddModal(<?php echo (int)$filter_client; ?>);
        });

        $(document).on('click', '[data-rsd-edit-report]', function() {
            var $b = $(this);
            openEditModal({
                id: $b.data('id'),
                user_id: String($b.data('user_id') || ''),
                report_type: $b.data('report_type') || '',
                title: $b.data('title') || '',
                year: String($b.data('year') || ''),
                month: String($b.data('month') || ''),
                period: $b.data('period') || '',
                description: $b.data('description') || '',
                file_path: $b.data('file_path') || '',
                file_name: $b.data('file_name') || ''
            });
        });

        $(document).on('click', '[data-rsd-delete-report]', function() {
            var $b = $(this);
            var id = $b.data('id');
            var t = $b.data('title') || ('گزارش #' + id);
            if (!confirm('آیا از حذف «' + t + '» اطمینان دارید؟ این عملیات قابل بازگشت نیست.')) return;

            var orig = $b.html();
            $b.prop('disabled', true).text('در حال حذف...');

            $.ajax({
                url: ajaxurl_wp,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'rsd_fp_delete_report',
                    nonce: '<?php echo esc_js(wp_create_nonce('rsd_fp_nonce')); ?>',
                    report_id: id
                }
            }).done(function(res) {
                if (res && res.success) {
                    $('tr[data-report-row="' + id + '"]').remove();
                    alert(res.data.message || 'گزارش حذف شد.');
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    $b.prop('disabled', false).html(orig);
                    alert((res && res.data && res.data.message) ? res.data.message : 'خطا در حذف گزارش.');
                }
            }).fail(function() {
                $b.prop('disabled', false).html(orig);
                alert('خطا در ارتباط با سرور.');
            });
        });

        $submitBtn.on('click', function() {
            var user_id = $('#rsd-rpt-user_id').val();
            var title = $('#rsd-rpt-title').val().trim();
            if (!user_id || user_id === '0' || user_id === '') {
                alert('انتخاب مشتری الزامی است.');
                return;
            }
            if (!title) {
                alert('عنوان گزارش الزامی است.');
                return;
            }

            var fd = new FormData($form[0]);
            var origLabel = $submitLabel.text();
            $submitBtn.prop('disabled', true);
            $submitLabel.text('در حال ذخیره...');

            $.ajax({
                url: ajaxurl_wp,
                type: 'POST',
                dataType: 'json',
                data: fd,
                processData: false,
                contentType: false,
                cache: false
            }).done(function(res) {
                $submitBtn.prop('disabled', false);
                $submitLabel.text(origLabel);
                if (res && res.success) {
                    alert(res.data.message || 'با موفقیت ذخیره شد.');
                    setTimeout(function() {
                        location.reload();
                    }, 600);
                } else {
                    alert((res && res.data && res.data.message) ? res.data.message : 'خطا در ذخیره گزارش.');
                }
            }).fail(function() {
                $submitBtn.prop('disabled', false);
                $submitLabel.text(origLabel);
                alert('خطا در ارتباط با سرور.');
            });
        });

        $modal.on('click', function(e) {
            if (e.target === this) $modal.hide();
        });

        $('#rsd-rpt-remove-file').on('change', function() {
            if (this.checked) {
                $fileInput.val('');
            }
        });
    });
</script>