<?php

/**
 * Template: Frontend - Calendar Page
 *
 * @var array $events
 * @var int $client_id
 */
defined('ABSPATH') || exit;

$active = 'calendar';
$categories = \RSD\FinancePanel\Modules\Calendar::get_categories();
$statuses = \RSD\FinancePanel\Modules\Calendar::get_statuses();
$views = \RSD\FinancePanel\Modules\Calendar::get_views();

// Jalali month view with GET-based navigation (cal_y = Jalali year, cal_m = Jalali month 1-12)
$today_g = current_time('Y-m-d');
$today_parts = rsd_fp_gregorian_to_jalali_parts(
    (int) substr($today_g, 0, 4),
    (int) substr($today_g, 5, 2),
    (int) substr($today_g, 8, 2)
);
$j_y_cur = (int) ($today_parts[0] ?? 1400);
$j_m_cur = (int) ($today_parts[1] ?? 1);
$j_d_cur = (int) ($today_parts[2] ?? 1);

$j_y = isset($_GET['cal_y']) ? (int) $_GET['cal_y'] : $j_y_cur;
$j_m = isset($_GET['cal_m']) ? (int) $_GET['cal_m'] : $j_m_cur;
if ($j_y < 1380) $j_y = 1380;
if ($j_y > 1420) $j_y = 1420;
if ($j_m < 1) $j_m = 1;
if ($j_m > 12) $j_m = 12;
$is_cur_month = ($j_y === $j_y_cur && $j_m === $j_m_cur);

$prev_y = $j_y;
$prev_m = $j_m - 1;
if ($prev_m < 1) {
    $prev_m = 12;
    $prev_y--;
}
$next_y = $j_y;
$next_m = $j_m + 1;
if ($next_m > 12) {
    $next_m = 1;
    $next_y++;
}

$self_uri = remove_query_arg(['cal_y', 'cal_m', 'mini_y', 'mini_m'], $_SERVER['REQUEST_URI'] ?? '');

$j_month_labels = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
$j_days_in_month = ($j_m <= 6) ? 31 : (($j_m <= 11) ? 30 : 29);
$j1_g = rsd_fp_parse_jalali_to_gregorian(sprintf('%04d/%02d/01', $j_y, $j_m));
$j1_w = (int) date('w', strtotime($j1_g . ' 00:00:00'));
$weekdays = ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش'];
$start_offset = ($j1_w + 1) % 7;

// Pre-map all events to Jalali date keys for fast lookup
$day_map = [];
foreach ($events as $ev) {
    $gd = $ev->start_date ?? '';
    if ('' === $gd) continue;
    $g_ts = strtotime($gd . ' 00:00:00');
    if (!$g_ts) continue;
    $parts = rsd_fp_gregorian_to_jalali_parts((int)date('Y', $g_ts), (int)date('n', $g_ts), (int)date('j', $g_ts));
    $key = (int)$parts[0] . '-' . (int)$parts[1];
    $day_map[$key][] = $ev;
}
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">📅 تقویم کاری و موعدهای مهم</h2>
            <div class="spacer"></div>
            <button type="button" class="rsd-btn rsd-btn-outline" data-modal="rsd-event-modal">
                ➕ افزودن موعد
            </button>
            <button type="button" class="rsd-btn rsd-btn-primary" data-modal="rsd-meeting-modal">
                🤝 درخواست جلسه
            </button>
        </div>

        <div class="rsd-grid rsd-grid-3">
            <!-- Main Calendar -->
            <div class="rsd-card" style="grid-column: span 2">
                <div class="rsd-calendar">
                    <div class="rsd-calendar-header">
                        <div>
                            <a href="<?php echo esc_url(add_query_arg(['cal_y' => $prev_y, 'cal_m' => $prev_m], $self_uri)); ?>"
                                class="rsd-cal-nav-btn" title="ماه قبل">‹</a>
                            <a href="<?php echo esc_url(add_query_arg(['cal_y' => $next_y, 'cal_m' => $next_m], $self_uri)); ?>"
                                class="rsd-cal-nav-btn" title="ماه بعد" style="margin-right:5px">›</a>
                        </div>
                        <div class="rsd-cal-title">
                            <?php echo esc_html($j_month_labels[$j_m - 1] . ' ' . $j_y);
                            if (!$is_cur_month) {
                                echo ' <a href="' . esc_url(add_query_arg(['cal_y' => $j_y_cur, 'cal_m' => $j_m_cur], $self_uri)) . '"'
                                    . ' style="font-size:11px;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:12px;text-decoration:none;margin-right:6px">امروز</a>';
                            } ?>
                        </div>
                        <div class="rsd-cal-view-switcher">
                            <?php foreach ($views as $k => $v) : ?>
                                <span class="rsd-cal-view-btn <?php echo $k === 'month' ? 'active' : ''; ?>"><?php echo esc_html($v); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="rsd-cal-grid">
                        <div class="rsd-cal-weekdays">
                            <?php foreach ($weekdays as $wd) : ?>
                                <div><?php echo esc_html($wd); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="rsd-cal-days">
                            <?php
                            for ($i = 0; $i < $start_offset; $i++) echo '<div class="rsd-cal-day other-month"></div>';
                            for ($d = 1; $d <= $j_days_in_month; $d++) :
                                $is_today = $is_cur_month && ($d === $j_d_cur);
                                $key = $j_y . '-' . $j_m . '-' . $d;
                                $day_events = [];
                                // Gather all events whose Y-M matches this cell from pre-computed map
                                foreach (($day_map[$j_y . '-' . $j_m] ?? []) as $ev) {
                                    $gd = $ev->start_date ?? '';
                                    if ('' === $gd) continue;
                                    $g_ts = strtotime($gd . ' 00:00:00');
                                    $parts = rsd_fp_gregorian_to_jalali_parts((int)date('Y', $g_ts), (int)date('n', $g_ts), (int)date('j', $g_ts));
                                    if ((int)$parts[2] === $d) $day_events[] = $ev;
                                }
                            ?>
                                <div class="rsd-cal-day <?php echo $is_today ? 'today' : ''; ?>"
                                    title="<?php echo esc_attr($j_y . '/' . $j_m . '/' . $d); ?>">
                                    <span class="rsd-cal-num"><?php echo esc_html($d); ?></span>
                                    <?php foreach ($day_events as $ev) :
                                        $color = \RSD\FinancePanel\Modules\Calendar::get_category_color($ev->category ?? 'deadline');
                                        $is_doc = ($ev->source ?? '') === 'document';
                                        $is_past = !empty($ev->is_past_expiry);
                                        $title_short = $ev->title ?? '';
                                        if (function_exists('mb_substr') && mb_strlen($title_short, 'UTF-8') > 18) {
                                            $title_short = mb_substr($title_short, 0, 18, 'UTF-8') . '…';
                                        }
                                    ?>
                                        <div class="rsd-cal-event" style="background:<?php echo esc_attr($color); ?><?php echo $is_past ? ';text-decoration:line-through;opacity:.7' : ''; ?>">
                                            <?php if ($is_doc) echo '📎 ';
                                            echo esc_html($title_short); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php
                            endfor;
                            $last = ($start_offset + $j_days_in_month) % 7;
                            if ($last > 0) {
                                for ($i = $last; $i < 7; $i++) echo '<div class="rsd-cal-day other-month"></div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category filter + Legend + upcoming -->
            <div>
                <div class="rsd-card" style="margin-bottom:18px">
                    <div class="rsd-card-header">
                        <div class="rsd-card-title"><span class="rsd-card-icon">🏷️</span> فیلتر دسته‌بندی</div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <?php foreach ($categories as $k => $v) :
                            $color = \RSD\FinancePanel\Modules\Calendar::get_category_color($k);
                            $count = count(array_filter($events, fn($e) => ($e->category ?? '') === $k));
                        ?>
                            <label style="display:flex;align-items:center;gap:8px;font-size:12px;cursor:pointer;padding:6px 8px;border-radius:8px;transition:.2s"
                                onmouseover="this.style.background='#f8fafc'"
                                onmouseout="this.style.background=''">
                                <input type="checkbox" checked onchange="this.closest('.rsd-grid').querySelectorAll('.rsd-cal-event').forEach(function(el){ el.style.display = ''; });">
                                <span style="width:10px;height:10px;border-radius:50%;display:inline-block;background:<?php echo esc_attr($color); ?>"></span>
                                <span style="flex:1;color:#334155"><?php echo esc_html($v); ?></span>
                                <span style="background:#f1f5f9;color:#64748b;padding:1px 7px;border-radius:20px;font-size:11px"><?php echo esc_html($count); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rsd-card">
                    <div class="rsd-card-header">
                        <div class="rsd-card-title"><span class="rsd-card-icon">⏰</span> نزدیک‌ترین موعدها</div>
                    </div>
                    <div class="rsd-list">
                        <?php
                        $upcoming = [];
                        $now_ts = strtotime(current_time('mysql'));
                        foreach ($events as $e) :
                            $ev_ts = strtotime($e->start_date);
                            if ($ev_ts >= $now_ts - 86400) $upcoming[] = $e;
                            if (count($upcoming) >= 6) break;
                        endforeach;
                        usort($upcoming, fn($a, $b) => strtotime($a->start_date) <=> strtotime($b->start_date));
                        ?>
                        <?php if (!empty($upcoming)) : ?>
                            <?php foreach (array_slice($upcoming, 0, 6) as $ev) :
                                $color = \RSD\FinancePanel\Modules\Calendar::get_category_color($ev->category);
                                $diff = (int) round((strtotime($ev->start_date) - $now_ts) / 86400);
                            ?>
                                <div class="rsd-list-item" style="padding:10px">
                                    <div class="rsd-list-icon" style="background:<?php echo esc_attr($color); ?>22;color:<?php echo esc_attr($color); ?>;width:36px;height:36px;border-radius:10px">
                                        📌
                                    </div>
                                    <div class="rsd-list-main">
                                        <div class="rsd-list-title" style="font-size:12px"><?php echo esc_html($ev->title); ?></div>
                                        <div class="rsd-list-meta">
                                            <?php echo esc_html(date_i18n('j F', strtotime($ev->start_date))); ?>
                                        </div>
                                    </div>
                                    <span class="rsd-badge <?php echo $diff <= 3 ? 'rsd-badge-danger' : 'rsd-badge-info'; ?>">
                                        <?php echo $diff <= 0 ? 'امروز' : $diff . ' روز دیگر'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="rsd-empty" style="padding:20px 0">
                                <div class="rsd-empty-icon">📅</div>
                                <p>رویداد آتی‌ای ثبت نشده است.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- Event Modal -->
<div class="rsd-modal-backdrop" id="rsd-event-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">ثبت موعد جدید</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-event-form">
                <div class="rsd-form-row">
                    <label>عنوان <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="مثلاً: ارسال مدارک مالیاتی">
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
                        <label>تاریخ <span class="required">*</span></label>
                        <?php rsd_fp_render_jalali_triple('start_date', 'rsd-fe-cal-event', true, ''); ?>
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>توضیحات</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button class="rsd-btn rsd-btn-light" data-close-modal>انصراف</button>
            <button type="submit" form="rsd-event-form" class="rsd-btn rsd-btn-primary">ثبت برای تأیید</button>
        </div>
    </div>
</div>

<!-- Meeting Modal -->
<div class="rsd-modal-backdrop" id="rsd-meeting-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">درخواست قرار ملاقات</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-meeting-form">
                <div class="rsd-form-row">
                    <label>موضوع جلسه <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="موضوع جلسه را بنویسید">
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>تاریخ پیشنهادی <span class="required">*</span></label>
                        <?php rsd_fp_render_jalali_triple('start_date', 'rsd-fe-cal-meeting', true, ''); ?>
                    </div>
                    <div class="rsd-form-row">
                        <label>ساعت پیشنهادی</label>
                        <input type="time" name="svc_hour">
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>توضیحات</label>
                    <textarea name="description" rows="3" placeholder="جزئیات بیشتر..."></textarea>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button class="rsd-btn rsd-btn-light" data-close-modal>انصراف</button>
            <button type="submit" form="rsd-meeting-form" class="rsd-btn rsd-btn-primary">ثبت درخواست جلسه</button>
        </div>
    </div>
</div>