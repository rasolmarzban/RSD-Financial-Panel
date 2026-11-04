<?php
/**
 * Admin Template: Single Service Request View
 *
 * @var object $service
 * @var WP_User|null $client
 */
defined('ABSPATH') || exit;

use RSD\FinancePanel\Modules\Services;

$svc_types = Services::get_service_types();
$statuses = Services::get_statuses();
$status_colors = [
    'pending'      => 'yellow',
    'in_progress'  => 'purple',
    'needs_more'   => 'orange',
    'completed'    => 'green',
    'cancelled'    => 'gray',
    'rejected'     => 'red',
];
$type_icons = [
    'company_registration' => '🏢',
    'brand_registration'   => '🎖️',
    'tax_declaration'      => '📑',
    'bookkeeping'          => '📚',
    'payroll'              => '👔',
    'consulting'           => '☕',
    'other'                => '📝',
];
$back_url = admin_url('admin.php?page=rsd-fp-services');

if (!$service) : ?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-card">
        <div class="rsd-empty">
            <div class="rsd-empty-icon">🗂️</div>
            <h3>درخواست خدمت پیدا نشد</h3>
            <a href="<?php echo esc_url($back_url); ?>" class="rsd-btn-admin primary">بازگشت به لیست</a>
        </div>
    </div>
</div>
<?php return; endif;

$sc = $status_colors[$service->status] ?? 'gray';
$icon = $type_icons[$service->service_type] ?? '📝';
$label = isset($svc_types[$service->service_type]) ? $svc_types[$service->service_type]['label'] : $service->service_type;
$fields_def = isset($svc_types[$service->service_type]) ? ($svc_types[$service->service_type]['fields'] ?? []) : [];
$request_data = !empty($service->request_data) ? json_decode($service->request_data, true) : [];
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div style="flex:1">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap">
                <span style="font-size:28px"><?php echo $icon; ?></span>
                <span style="font-family:monospace;font-weight:700;color:#0369a1;font-size:18px">#<?php echo (int)$service->id; ?></span>
                <span class="rsd-admin-badge <?php echo $sc; ?>" style="font-size:13px;padding:5px 12px">
                    <?php echo isset($statuses[$service->status]) ? esc_html($statuses[$service->status]) : esc_html($service->status); ?>
                </span>
            </div>
            <h1 style="margin:0;font-size:24px;line-height:1.4;color:#0f172a"><?php echo esc_html($service->title); ?></h1>
            <div style="margin-top:8px;display:flex;gap:18px;flex-wrap:wrap;color:#475569;font-size:13px">
                <span>📂 نوع خدمت: <strong style="color:#0f172a"><?php echo esc_html($label); ?></strong></span>
                <span>👤 درخواست‌دهنده: <strong style="color:#0f172a"><?php echo esc_html($client ? $client->display_name : 'کاربر #'.$service->user_id); ?></strong></span>
                <span>📅 ثبت شده در: <strong><?php echo esc_html(date_i18n('j F Y ساعت H:i', strtotime($service->created_at))); ?></strong></span>
                <?php if (!empty($service->updated_at)) : ?>
                    <span>✏️ آخرین بروزرسانی: <strong><?php echo esc_html(date_i18n('j F Y ساعت H:i', strtotime($service->updated_at))); ?></strong></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
            <a href="<?php echo esc_url($back_url); ?>" class="rsd-btn-admin secondary">← بازگشت</a>
            <a href="<?php echo esc_url(add_query_arg(['view'=>'client','id'=>$service->user_id], admin_url('admin.php?page=rsd-fp-clients'))); ?>"
               class="rsd-btn-admin secondary">💼 پرتفویل مالی</a>
            <a href="<?php echo esc_url(get_edit_user_link($service->user_id)); ?>" target="_blank" rel="noopener"
               class="rsd-btn-admin secondary">👥 پروفایل</a>
        </div>
    </div>

    <!-- Status changer -->
    <div class="rsd-admin-card" style="padding:14px 20px;margin-bottom:16px">
        <form method="post" id="rsd-svc-quick-change" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:0">
            <?php wp_nonce_field('rsd_fp_nonce', 'nonce'); ?>
            <input type="hidden" name="service_id" value="<?php echo (int)$service->id; ?>">
            <label style="font-weight:600;color:#0f172a">تغییر وضعیت:</label>
            <select name="status" id="q_status">
                <?php foreach ($statuses as $key => $lab) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($service->status, $key); ?>>
                        <?php echo esc_html($lab); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label style="font-weight:600;color:#0f172a;margin-right:6px">ارجاع به مسئول:</label>
            <select name="assignee_id" id="q_assignee">
                <option value="0">—</option>
                <?php
                $admins = get_users(['role__in' => ['administrator', 'editor']]);
                foreach ($admins as $a) {
                    $sel = !empty($service->assignee_id) && (int)$service->assignee_id === (int)$a->ID ? 'selected' : '';
                    echo '<option value="' . (int)$a->ID . '" ' . $sel . '>' . esc_html($a->display_name . ' (' . $a->user_email . ')') . '</option>';
                }
                ?>
            </select>
            <button type="submit" class="rsd-btn-admin small primary" id="rsd-svc-apply">اعمال تغییرات</button>
        </form>
    </div>

    <div class="rsd-admin-grid cols-2" style="align-items:stretch">
        <!-- LEFT: Request details -->
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="rsd-admin-card">
                <div class="rsd-admin-card-header">
                    <div class="rsd-admin-card-title"><span class="rsd-icon">📝</span> توضیحات درخواست</div>
                </div>
                <div style="padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;line-height:2;color:#0f172a;white-space:pre-wrap;word-break:break-word">
                    <?php echo wp_kses_post(nl2br(make_clickable($service->description ?: '—'))); ?>
                </div>
            </div>

            <?php if (!empty($fields_def) || !empty($request_data)) : ?>
                <div class="rsd-admin-card">
                    <div class="rsd-admin-card-header">
                        <div class="rsd-admin-card-title"><span class="rsd-icon">📋</span> فیلدهای فرم خدمات</div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;padding:4px">
                        <?php
                        $shown_keys = [];
                        // Show using field definitions first
                        foreach ($fields_def as $fk => $fdef) {
                            $shown_keys[] = $fk;
                            $raw = $request_data[$fk] ?? null;
                            if (is_array($raw)) $raw = implode('، ', $raw);
                            $val = $raw !== null && $raw !== '' ? $raw : '<span style="color:#cbd5e1">—</span>';
                            ?>
                            <div class="rsd-info-row">
                                <span><?php echo !empty($fdef['required']) ? '<span style="color:#dc2626">*</span>' : ''; ?>
                                    <?php echo esc_html($fdef['label'] ?? $fk); ?></span>
                                <strong style="max-width:360px;word-break:break-word"><?php
                                    if (!empty($fdef['options']) && isset($fdef['options'][$raw])) {
                                        echo esc_html($fdef['options'][$raw]);
                                    } else {
                                        echo $val === '<span style="color:#cbd5e1">—</span>' ? $val : esc_html($val);
                                    }
                                ?></strong>
                            </div>
                        <?php }
                        // Show any extra keys present in request_data that weren't defined
                        foreach ((array)$request_data as $k => $v) {
                            if (in_array($k, $shown_keys, true)) continue;
                            if (is_array($v)) $v = implode('، ', $v);
                            ?>
                            <div class="rsd-info-row">
                                <span><?php echo esc_html($k); ?></span>
                                <strong style="max-width:360px;word-break:break-word"><?php echo esc_html($v); ?></strong>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Client & actions sidebar -->
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="margin-bottom:12px"><span class="rsd-icon">👤</span> اطلاعات مشتری</div>
                <?php if ($client) : ?>
                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:4px 4px 14px;border-bottom:1px dashed #e2e8f0;margin-bottom:14px">
                        <?php echo get_avatar($client->ID, 68); ?>
                        <div style="text-align:center">
                            <div style="font-weight:800;color:#0f172a;font-size:16px"><?php echo esc_html($client->display_name); ?></div>
                            <div style="color:#64748b;font-size:13px">@<?php echo esc_html($client->user_login); ?></div>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;color:#334155">
                        <div class="rsd-info-row"><span>📧 ایمیل</span><a href="mailto:<?php echo esc_attr($client->user_email); ?>" style="font-weight:600"><?php echo esc_html($client->user_email); ?></a></div>
                        <div class="rsd-info-row"><span>📅 تاریخ ثبت‌نام</span><strong><?php echo esc_html(date_i18n('j F Y', strtotime($client->user_registered))); ?></strong></div>
                        <div class="rsd-info-row"><span>🆔 شناسه کاربری</span><strong>#<?php echo (int)$client->ID; ?></strong></div>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
                        <a href="<?php echo esc_url(add_query_arg(['view'=>'client','id'=>$client->ID], admin_url('admin.php?page=rsd-fp-clients'))); ?>"
                           class="rsd-btn-admin small primary" style="flex:1;justify-content:center">💼 پرتفویل مالی</a>
                        <a href="<?php echo esc_url(get_edit_user_link($client->ID)); ?>" target="_blank" rel="noopener"
                           class="rsd-btn-admin small secondary" style="flex:1;justify-content:center">✏️ ویرایش کاربر</a>
                    </div>
                    <a href="<?php echo esc_url(add_query_arg(['client_id'=>$client->ID], admin_url('admin.php?page=rsd-fp-tickets'))); ?>"
                       class="rsd-btn-admin small secondary" style="width:100%;justify-content:center;margin-top:8px">🎟️ تیکت‌های مشتری</a>
                <?php else : ?>
                    <div class="rsd-empty" style="padding:24px 8px"><p>کاربر یافت نشد.</p></div>
                <?php endif; ?>
            </div>

            <!-- Internal admin notes -->
            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="margin-bottom:12px"><span class="rsd-icon">📌</span> یادداشت‌های داخلی (مدیر)</div>
                <textarea rows="5" placeholder="یادداشت‌ها در اینجا ثبت و ذخیره می‌شوند (نسخه بعدی)"
                          style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:12px;line-height:1.8;resize:vertical"><?php echo esc_textarea($service->admin_notes ?? ''); ?></textarea>
                <button type="button" class="rsd-btn-admin small primary" style="margin-top:10px">💾 ذخیره یادداشت‌ها</button>
            </div>

            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="margin-bottom:12px"><span class="rsd-icon">⚡</span> عملیات سریع</div>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="<?php echo esc_url(add_query_arg(['client_id'=>$service->user_id], admin_url('admin.php?page=rsd-fp-documents'))); ?>"
                       class="rsd-btn-admin small secondary">📁 مدارک مشتری</a>
                    <a href="<?php echo esc_url(add_query_arg(['client_id'=>$service->user_id], admin_url('admin.php?page=rsd-fp-invoices'))); ?>"
                       class="rsd-btn-admin small secondary">📑 فاکتورهای مشتری</a>
                    <button type="button" class="rsd-btn-admin small secondary"
                            onclick="window.location='<?php echo esc_url(add_query_arg(['view'=>'single','client_id'=>$service->user_id], admin_url('admin.php?page=rsd-fp-tickets'))); ?>'">❌ (پیاده‌سازی بعدی) باز کردن تیکت هم‌راه</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
.rsd-info-row { display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;gap:14px }
.rsd-info-row > span:first-child { color:#64748b;font-weight:500;font-size:13px }
.rsd-info-row > strong { color:#0f172a;font-weight:700 }
</style>
