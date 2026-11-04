<?php

/**
 * Template: Frontend - Documents Page
 *
 * @var array $documents
 * @var int $client_id
 * @var string $active_tab
 */
defined('ABSPATH') || exit;

$active = 'documents';
$categories = \RSD\FinancePanel\Modules\Documents::get_categories();
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">📁 مدیریت مدارک</h2>
            <div class="spacer"></div>
            <button type="button" class="rsd-btn rsd-btn-primary" data-modal="rsd-upload-modal">
                ➕ بارگذاری مدرک جدید
            </button>
            <button type="button" class="rsd-btn rsd-btn-outline" data-modal="rsd-batch-upload-modal">
                📦 آپلود دسته‌ای
            </button>
        </div>

        <div class="rsd-tabs">
            <a class="rsd-tab <?php echo $active_tab === 'all' ? 'active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('tab', 'all')); ?>">
                همه مدارک (<?php echo esc_html(count($documents)); ?>)
            </a>
            <a class="rsd-tab <?php echo $active_tab === 'incomplete' ? 'active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('tab', 'incomplete')); ?>">
                مدارک ناقص (<?php
                            echo esc_html(count(array_filter($documents, fn($d) => !empty($d->is_incomplete))));
                            ?>)
            </a>
            <a class="rsd-tab <?php echo $active_tab === 'tax' ? 'active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('tab', 'tax')); ?>">
                نامه‌های اداره مالیات
            </a>
        </div>

        <?php if ($active_tab === 'incomplete') : ?>
            <div class="rsd-card" style="margin-bottom:20px;background:#fff1f0;border:1px solid #fed7d7">
                <div class="rsd-card-header">
                    <div class="rsd-card-title" style="color:#c53030">⚠️ مدارک درخواست‌شده از طرف شرکت</div>
                </div>
                <div class="rsd-list">
                    <?php
                    $incomplete = array_filter($documents, fn($d) => !empty($d->is_incomplete));
                    if (!empty($incomplete)) :
                        foreach ($incomplete as $doc) : ?>
                            <div class="rsd-list-item">
                                <div class="rsd-list-icon" style="background:#fee2e2;color:#dc2626">📄</div>
                                <div class="rsd-list-main">
                                    <div class="rsd-list-title"><?php echo esc_html($doc->title); ?></div>
                                    <div class="rsd-list-meta">
                                        تاریخ درخواست: <?php echo esc_html(date_i18n('j F Y', strtotime($doc->requested_date))); ?>
                                        <?php if (!empty($doc->period)) : ?>
                                            | دوره: <?php echo esc_html($doc->period); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($doc->description)) : ?>
                                            <br><span style="color:#64748b"><?php echo esc_html($doc->description); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($doc->expiry_date)) : ?>
                                            <br><span style="color:#dc2626;font-weight:700">مهلت ارسال: <?php echo esc_html(rsd_fp_jalali_date('j F Y', $doc->expiry_date)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="rsd-list-actions">
                                    <?php echo rsd_fp_status_badge($doc->status); ?>
                                    <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-primary"
                                        data-modal="rsd-upload-modal"
                                        data-doc-id="<?php echo (int)$doc->id; ?>">
                                        📥 بارگذاری مدرک
                                    </button>
                                </div>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <div class="rsd-empty">
                            <div class="rsd-empty-icon">✅</div>
                            <h3>همه مدارک کامل هستند</h3>
                            <p>مدرک ناقصی در انتظار بارگذاری نیست.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="rsd-card">
            <div class="rsd-card-header">
                <div class="rsd-card-title"><span class="rsd-card-icon">📚</span> آرشیو مدارک</div>
            </div>

            <div class="rsd-table-wrap">
                <table class="rsd-table">
                    <thead>
                        <tr>
                            <th>عنوان</th>
                            <th>دسته‌بندی</th>
                            <th>سال / دوره</th>
                            <th>فایل</th>
                            <th>تاریخ آپلود</th>
                            <th>تاریخ انقضا</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($documents)) : foreach ($documents as $doc) : ?>
                                <tr>
                                    <td style="font-weight:600;color:#0f172a">
                                        <?php echo esc_html($doc->title); ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo isset($categories[$doc->category])
                                            ? esc_html($categories[$doc->category])
                                            : esc_html__('-', 'rsd-finance-panel');
                                        ?>
                                    </td>
                                    <td><?php echo esc_html(trim(($doc->year ?? '') . ' / ' . ($doc->period || ''), ' /')); ?></td>
                                    <td>
                                        <?php if (!empty($doc->file_path)) : ?>
                                            <a href="<?php echo esc_url($doc->file_path); ?>" target="_blank"
                                                class="rsd-btn rsd-btn-sm rsd-btn-outline">📂 دانلود</a>
                                        <?php else : ?>
                                            <span style="color:#cbd5e1;font-size:11px">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(date_i18n('j F Y', strtotime($doc->created_at))); ?></td>
                                    <td>
                                        <?php if (!empty($doc->expiry_date)) :
                                            $exp_ts = strtotime($doc->expiry_date);
                                            $days_left = (int)ceil(($exp_ts - time()) / 86400);
                                            $style = '';
                                            if ($days_left < 0) {
                                                $style = 'color:#dc2626;font-weight:700';
                                                $extra = ' (منقضی‌شده)';
                                            } elseif ($days_left <= 7) {
                                                $style = 'color:#b45309;font-weight:700';
                                                $extra = ' (' . $days_left . ' روز مانده)';
                                            } else {
                                                $style = 'color:#16a34a';
                                                $extra = '';
                                            }
                                        ?>
                                            <span style="<?php echo esc_attr($style); ?>">
                                                <?php echo esc_html(rsd_fp_jalali_date('j F Y', $exp_ts) . ($extra ?? '')); ?>
                                            </span>
                                        <?php else : ?>
                                            <span style="color:#cbd5e1;font-size:11px">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo rsd_fp_status_badge($doc->status); ?></td>
                                    <td>
                                        <div class="cell-actions">
                                            <?php if (!empty($doc->is_incomplete)) : ?>
                                                <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-primary"
                                                    style="margin-left:4px"
                                                    data-modal="rsd-upload-modal"
                                                    data-doc-id="<?php echo (int)$doc->id; ?>">
                                                    📥 جایگزینی
                                                </button>
                                            <?php endif; ?>
                                            <?php if (current_user_can('manage_options') || $doc->user_id == $client_id) : ?>
                                                <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-danger rsd-confirm"
                                                    data-delete-document="<?php echo (int) $doc->id; ?>">
                                                    🗑️
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else : ?>
                            <tr>
                                <td colspan="8">
                                    <div class="rsd-empty">
                                        <div class="rsd-empty-icon">📂</div>
                                        <h3>هنوز مدرکی بارگذاری نشده است</h3>
                                        <p>از دکمه «بارگذاری مدرک جدید» استفاده کنید.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Upload Modal -->
<div class="rsd-modal-backdrop" id="rsd-upload-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">بارگذاری مدرک جدید</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-upload-doc-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="rsd_doc_id" id="rsd_doc_id" value="">
                <!-- Current document preview (only rendered when re-uploading an incomplete doc) -->
                <div class="rsd-current-doc-preview" style="display:none;padding:12px 14px;border:1px dashed #fcd34d;border-radius:12px;background:#fffbeb;margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                        <span style="display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:50%;background:#fef3c7;color:#b45309;font-size:16px">ℹ️</span>
                        <div>
                            <div style="font-weight:700;color:#92400e;font-size:13px">اطلاعات فعلی مدرک ناقص</div>
                            <div style="color:#92400e;font-size:11px;margin-top:2px">در صورت نیاز می‌توانید موارد زیر را اصلاح کنید یا فقط فایل جدید را انتخاب و ثبت بزنید.</div>
                        </div>
                    </div>
                    <div class="rsd-current-doc-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;font-size:12.5px;color:#334155">
                        <div><span style="color:#64748b">عنوان:</span> <strong class="rsd-cur-title"></strong></div>
                        <div><span style="color:#64748b">دسته‌بندی:</span> <strong class="rsd-cur-category"></strong></div>
                        <div><span style="color:#64748b">سال:</span> <strong class="rsd-cur-year"></strong></div>
                        <div><span style="color:#64748b">ماه:</span> <strong class="rsd-cur-month"></strong></div>
                        <div style="grid-column:1/-1"><span style="color:#64748b">دوره:</span> <strong class="rsd-cur-period"></strong></div>
                        <div><span style="color:#64748b">تاریخ انقضا:</span> <strong style="color:#dc2626" class="rsd-cur-expiry"></strong></div>
                        <div><span style="color:#64748b">آپلود شده:</span> <strong class="rsd-cur-uploaded"></strong></div>
                        <div style="grid-column:1/-1"><span style="color:#64748b">توضیحات:</span> <strong class="rsd-cur-description"></strong></div>
                    </div>
                    <div class="rsd-cur-file-row" style="display:flex;align-items:center;gap:8px;margin-top:12px;padding:8px 10px;border-radius:10px;background:white;border:1px solid #fde68a">
                        <span style="font-size:20px">📎</span>
                        <div style="flex:1;min-width:0">
                            <div class="rsd-cur-filename" style="font-weight:600;color:#0f172a;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
                            <div class="rsd-cur-filesize" style="color:#64748b;font-size:11px;margin-top:2px"></div>
                        </div>
                        <a href="#" target="_blank" class="rsd-cur-download rsd-btn rsd-btn-sm rsd-btn-outline">📂 دانلود فایل فعلی</a>
                    </div>
                </div>

                <div class="rsd-form-row">
                    <label>عنوان مدرک <span class="required">*</span></label>
                    <input type="text" name="title" required>
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>دسته‌بندی</label>
                        <select name="category">
                            <?php foreach ($categories as $k => $v) : ?>
                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsd-form-row">
                        <label>سال</label>
                        <select name="year">
                            <option value="">-</option>
                            <?php foreach (rsd_fp_jalali_years(7, 1) as $y) : ?>
                                <option value="<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>دوره مربوطه</label>
                        <input type="text" name="period" placeholder="مثلاً: دوماهه اول سال">
                    </div>
                    <div class="rsd-form-row">
                        <label>ماه</label>
                        <select name="month">
                            <option value="">-</option>
                            <?php for ($i = 1; $i <= 12; $i++) : ?>
                                <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>تاریخ انقضا / مهلت</label>
                    <?php rsd_fp_render_jalali_triple('expiry_date', 'rsd-fe-doc-expiry', false, ''); ?>
                    <span style="color:#94a3b8;font-size:11px;margin-inline-end:4px">سال، ماه و روز مهلت را انتخاب کنید (اختیاری)</span>
                </div>
                <div class="rsd-form-row">
                    <label>توضیحات</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="rsd-form-row">
                    <label class="rsd-file-label">فایل
                        <span class="rsd-file-label-hint" style="color:#64748b;font-size:11px;margin-inline-start:6px">(برای آپدیت فقط متادیتا می‌توانید بدون انتخاب فایل جدید ثبت کنید)</span>
                    </label>
                    <div class="rsd-upload-area">
                        <div class="upload-icon">📤</div>
                        <div class="upload-text">کلیک یا بکشید</div>
                        <div class="upload-hint">PDF, JPG, DOC, XLS — اگر فایل جدیدی انتخاب نشود فقط اطلاعات مدرک به‌روزرسانی می‌شود</div>
                        <input type="file" name="document_file" style="display:none"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar,.txt,.csv">
                    </div>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button class="rsd-btn rsd-btn-light" data-close-modal>انصراف</button>
            <button type="submit" form="rsd-upload-doc-form" class="rsd-btn rsd-btn-primary">ثبت مدرک</button>
        </div>
    </div>
</div>

<!-- Batch Upload Modal -->
<div class="rsd-modal-backdrop" id="rsd-batch-upload-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">آپلود دسته‌ای مدارک</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-batch-upload-form" method="post" enctype="multipart/form-data">
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>دسته‌بندی پیش‌فرض</label>
                        <select name="category">
                            <?php foreach ($categories as $k => $v) : ?>
                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsd-form-row">
                        <label>سال</label>
                        <select name="year">
                            <option value="">-</option>
                            <?php foreach (rsd_fp_jalali_years(7, 1) as $y) : ?>
                                <option value="<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>تاریخ انقضا / مهلت مشترک همه فایل‌ها</label>
                    <?php rsd_fp_render_jalali_triple('expiry_date', 'rsd-fe-batch-expiry', false, ''); ?>
                    <span style="color:#94a3b8;font-size:11px;margin-inline-end:4px">سال، ماه و روز مهلت را انتخاب کنید (اختیاری)</span>
                </div>
                <div class="rsd-form-row">
                    <label>فایل‌ها <span class="required">*</span></label>
                    <div class="rsd-upload-area">
                        <div class="upload-icon">📦</div>
                        <div class="upload-text">چند فایل را انتخاب یا بکشید</div>
                        <div class="upload-hint">هر فایل یک ردیف مستقل ثبت می‌شود</div>
                        <input type="file" name="documents[]" multiple required style="display:none">
                    </div>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button class="rsd-btn rsd-btn-light" data-close-modal>انصراف</button>
            <button type="submit" form="rsd-batch-upload-form" class="rsd-btn rsd-btn-primary">شروع آپلود</button>
        </div>
    </div>
</div>