<?php
/**
 * Admin Template: Service Requests Management
 *
 * @var array $services
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
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>🗂️ مدیریت درخواست‌های خدمات</h1>
            <p>پیگیری و پردازش درخواست‌های ثبت‌شده توسط مشتریان (ثبت شرکت / برند / مالی و ...)</p>
        </div>
    </div>

    <div class="rsd-admin-card">
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table widefat">
                <thead><tr>
                    <th style="width:110px">شناسه</th>
                    <th>نوع خدمت</th>
                    <th>عنوان درخواست</th>
                    <th>مشتری</th>
                    <th>زمان ثبت</th>
                    <th>وضعیت</th>
                    <th style="width:130px">عملیات</th>
                </tr></thead>
                <tbody>
                <?php if (!empty($services)) : foreach ($services as $s) :
                    $sc = $status_colors[$s->status] ?? 'gray';
                    $icon = $type_icons[$s->service_type] ?? '📝';
                    $label = isset($svc_types[$s->service_type]) ? $svc_types[$s->service_type]['label'] : $s->service_type;
                    $single_url = admin_url('admin.php?page=rsd-fp-services&view=single&id=' . (int)$s->id);
                    ?>
                    <tr>
                        <td><span style="font-family:monospace;font-weight:700;color:#0369a1">#<?php echo (int)$s->id; ?></span></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-size:22px"><?php echo $icon; ?></span>
                                <div>
                                    <div style="font-weight:700;color:#0f172a"><?php echo esc_html($label); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight:600;color:#0f172a;max-width:360px">
                            <?php echo esc_html($s->title); ?>
                            <?php if (!empty($s->description)) : ?>
                                <div style="color:#64748b;font-weight:400;font-size:12px;margin-top:3px;line-height:1.6">
                                    <?php echo esc_html(mb_substr($s->description, 0, 85)); ?>...
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="rsd-user-chip">
                                <?php echo get_avatar($s->user_id, 28); ?>
                                <div style="display:flex;flex-direction:column;gap:2px">
                                    <span style="font-weight:600"><?php echo esc_html($s->client_name ?? 'کاربر #'.$s->user_id); ?></span>
                                    <span style="color:#64748b;font-size:11px"><?php echo esc_html(get_userdata($s->user_id)->user_email ?? ''); ?></span>
                                </div>
                            </div>
                        </td>
                        <td style="color:#64748b;font-size:12px">
                            <?php echo esc_html(date_i18n('j F Y – H:i', strtotime($s->created_at))); ?>
                        </td>
                        <td>
                            <span class="rsd-admin-badge <?php echo $sc; ?>">
                                <?php echo isset($statuses[$s->status]) ? esc_html($statuses[$s->status]) : esc_html($s->status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($single_url); ?>"
                               class="rsd-btn-admin small primary">پردازش</a>
                            <a href="<?php echo esc_url(add_query_arg(['client_id'=>$s->user_id], admin_url('admin.php?page=rsd-fp-documents'))); ?>"
                               class="rsd-btn-admin small secondary">مدارک</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="7"><div class="rsd-empty">
                        <div class="rsd-empty-icon">🗂️</div>
                        <h3>هنوز درخواست خدمتی ثبت نشده</h3>
                        <p>مشتریان از پرتال کاربری می‌توانند خدمات جدیدی درخواست کنند.</p>
                    </div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
