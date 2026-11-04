<?php

/**
 * Template: Frontend - Reports Page
 *
 * @var array $reports
 * @var int $client_id
 * @var array $filters
 */
defined('ABSPATH') || exit;

$active = 'reports';
$types = \RSD\FinancePanel\Modules\Reports::get_types();
$months = \RSD\FinancePanel\Modules\Reports::get_months();
$years = \RSD\FinancePanel\Modules\Reports::get_available_years($client_id);
if (empty($years)) {
    $years = rsd_fp_jalali_years(6, 0);
}
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">📊 گزارش‌ها</h2>
            <div class="spacer"></div>
        </div>

        <div class="rsd-toolbar">
            <form method="get" style="display:contents">
                <select name="year" class="rsd-filter">
                    <option value="">همه سال‌ها</option>
                    <?php foreach ($years as $y) : ?>
                        <option value="<?php echo esc_attr($y); ?>"
                            <?php selected($filters['year'] ?? '', $y); ?>><?php echo esc_html($y); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="month" class="rsd-filter">
                    <option value="">همه ماه‌ها</option>
                    <?php foreach ($months as $k => $v) : ?>
                        <option value="<?php echo esc_attr($k); ?>"
                            <?php selected($filters['month'] ?? '', $k); ?>><?php echo esc_html($v); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="type" class="rsd-filter">
                    <option value="">همه انواع</option>
                    <?php foreach ($types as $k => $v) : ?>
                        <option value="<?php echo esc_attr($k); ?>"
                            <?php selected($filters['type'] ?? '', $k); ?>><?php echo esc_html($v); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="rsd-btn rsd-btn-sm rsd-btn-primary">اعمال فیلتر</button>
                <a href="<?php echo esc_url(remove_query_arg(['year', 'month', 'type'])); ?>"
                    class="rsd-btn rsd-btn-sm rsd-btn-light">پاک کردن</a>
            </form>
        </div>

        <div class="rsd-card">
            <div class="rsd-table-wrap">
                <table class="rsd-table">
                    <thead>
                        <tr>
                            <th>عنوان گزارش</th>
                            <th>دسته‌بندی</th>
                            <th>دوره</th>
                            <th>تاریخ انتشار</th>
                            <th>توضیحات</th>
                            <th>فایل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reports)) : foreach ($reports as $r) : ?>
                                <tr>
                                    <td style="font-weight:600;color:#0f172a"><?php echo esc_html($r->title); ?></td>
                                    <td>
                                        <?php
                                        echo isset($types[$r->report_type])
                                            ? '<span class="rsd-badge rsd-badge-info">' . esc_html($types[$r->report_type]) . '</span>'
                                            : '<span class="rsd-badge rsd-badge-secondary">سایر</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(trim(($r->year ? $r->year . ' - ' : '') . ($r->month ? $months[$r->month] ?? '' : '') . ($r->period ? ' (' . $r->period . ')' : ''), ' -()')); ?>
                                    </td>
                                    <td><?php echo esc_html(date_i18n('j F Y', strtotime($r->created_at))); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($r->description)) {
                                            echo esc_html(mb_substr($r->description, 0, 80)) . (mb_strlen($r->description) > 80 ? '...' : '');
                                        } else {
                                            echo '<span style="color:#cbd5e1">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="cell-actions">
                                            <?php if (!empty($r->file_path)) : ?>
                                                <a href="<?php echo esc_url($r->file_path); ?>" target="_blank"
                                                    class="rsd-btn rsd-btn-sm rsd-btn-outline">👁 مشاهده</a>
                                                <a href="<?php echo esc_url($r->file_path); ?>" download
                                                    class="rsd-btn rsd-btn-sm rsd-btn-primary">⬇ دانلود</a>
                                            <?php else : ?>
                                                <span style="color:#cbd5e1;font-size:11px">فایل ضمیمه نشده</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else : ?>
                            <tr>
                                <td colspan="6">
                                    <div class="rsd-empty">
                                        <div class="rsd-empty-icon">📊</div>
                                        <h3>گزارشی با این فیلترها یافت نشد</h3>
                                        <p>گزارش‌های تهیه‌شده برای شما اینجا نمایش داده می‌شود.</p>
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