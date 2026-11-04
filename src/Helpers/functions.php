<?php

/**
 * Global helper functions
 *
 * @package RSD_Finance_Panel
 * @subpackage Helpers
 */

defined('ABSPATH') || exit;

/**
 * Get the plugin singleton instance (the real object).
 * Safe to call any time after plugins_loaded hook.
 *
 * @return \RSD\FinancePanel\Core\Plugin
 */
function rsd_fp()
{
    if (function_exists('rsd_fp_init')) {
        return rsd_fp_init();
    }
    static $fallback = null;
    if ($fallback === null) {
        $fallback = new \RSD\FinancePanel\Core\Plugin();
        $fallback->run();
    }
    return $fallback;
}

/**
 * Get a plugin setting
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function rsd_fp_setting($key, $default = null)
{
    return \RSD\FinancePanel\Core\Plugin::get_setting($key, $default);
}

/**
 * Format currency amount
 *
 * @param float $amount
 * @return string
 */
function rsd_fp_currency($amount)
{
    return \RSD\FinancePanel\Core\Plugin::format_currency($amount);
}

/**
 * Get current client ID
 *
 * @return int
 */
function rsd_fp_client_id()
{
    return \RSD\FinancePanel\Core\Plugin::get_current_client_id();
}

/**
 * Get portal page URL
 *
 * @param string $type
 * @return string
 */
function rsd_fp_page_url($type = 'dashboard')
{
    return \RSD\FinancePanel\Core\Plugin::get_page_url($type);
}

/**
 * Check if user has admin access
 *
 * @return bool
 */
function rsd_fp_is_admin()
{
    return \RSD\FinancePanel\Core\Plugin::is_admin_user();
}

/**
 * Check if user has client access
 *
 * @return bool
 */
function rsd_fp_is_client()
{
    return \RSD\FinancePanel\Core\Plugin::is_client_user();
}

/**
 * Add admin flash notice
 *
 * @param string $message
 * @param string $type
 * @return void
 */
function rsd_fp_add_notice($message, $type = 'success')
{
    \RSD\FinancePanel\Core\Plugin::add_notice($message, $type);
}

/**
 * Template partial loader
 *
 * @param string $template Template name relative to templates dir
 * @param array $args Variables to extract
 * @param bool $echo
 * @return string|void
 */
function rsd_fp_get_template($template, $args = [], $echo = true)
{
    $template = ltrim($template, '/');
    $file = RSD_FP_PLUGIN_DIR . 'templates/' . $template . '.php';

    if (!file_exists($file)) {
        return '';
    }

    if (!empty($args)) {
        extract($args);
    }

    if (!$echo) {
        ob_start();
        include $file;
        return ob_get_clean();
    }

    include $file;
}

/**
 * Get status badge HTML
 *
 * @param string $status
 * @return string
 */
function rsd_fp_status_badge($status)
{
    $labels = [
        'new'          => esc_html__('جدید', 'rsd-finance-panel'),
        'pending'      => esc_html__('در انتظار', 'rsd-finance-panel'),
        'in_progress'  => esc_html__('در حال بررسی', 'rsd-finance-panel'),
        'waiting'      => esc_html__('منتظر پاسخ مشتری', 'rsd-finance-panel'),
        'completed'    => esc_html__('انجام‌شده', 'rsd-finance-panel'),
        'closed'       => esc_html__('بسته‌شده', 'rsd-finance-panel'),
        'uploaded'     => esc_html__('ارسال‌شده', 'rsd-finance-panel'),
        'incomplete'   => esc_html__('ناقص', 'rsd-finance-panel'),
        'approved'     => esc_html__('تأییدشده', 'rsd-finance-panel'),
        'rejected'     => esc_html__('ردشده', 'rsd-finance-panel'),
        'unpaid'       => esc_html__('پرداخت‌نشده', 'rsd-finance-panel'),
        'partial'      => esc_html__('پرداخت جزئی', 'rsd-finance-panel'),
        'paid'         => esc_html__('پرداخت‌شده', 'rsd-finance-panel'),
        'overdue'      => esc_html__('سررسید گذشته', 'rsd-finance-panel'),
        'confirmed'    => esc_html__('تأییدشده', 'rsd-finance-panel'),
        'needs_change' => esc_html__('نیاز به تغییر', 'rsd-finance-panel'),
    ];

    $colors = [
        'new'          => 'rsd-badge-primary',
        'pending'      => 'rsd-badge-warning',
        'in_progress'  => 'rsd-badge-info',
        'waiting'      => 'rsd-badge-warning',
        'completed'    => 'rsd-badge-success',
        'closed'       => 'rsd-badge-secondary',
        'uploaded'     => 'rsd-badge-success',
        'incomplete'   => 'rsd-badge-danger',
        'approved'     => 'rsd-badge-success',
        'rejected'     => 'rsd-badge-danger',
        'unpaid'       => 'rsd-badge-danger',
        'partial'      => 'rsd-badge-warning',
        'paid'         => 'rsd-badge-success',
        'overdue'      => 'rsd-badge-danger',
        'confirmed'    => 'rsd-badge-success',
        'needs_change' => 'rsd-badge-warning',
    ];

    $label = $labels[$status] ?? $status;
    $class = $colors[$status] ?? 'rsd-badge-secondary';

    return sprintf('<span class="rsd-badge %s">%s</span>', esc_attr($class), esc_html($label));
}

/**
 * Get array of status labels (in Persian)
 *
 * @return array
 */
function rsd_fp_status_labels()
{
    return [
        'new'          => esc_html__('جدید', 'rsd-finance-panel'),
        'pending'      => esc_html__('در انتظار', 'rsd-finance-panel'),
        'in_progress'  => esc_html__('در حال بررسی', 'rsd-finance-panel'),
        'waiting'      => esc_html__('منتظر پاسخ مشتری', 'rsd-finance-panel'),
        'completed'    => esc_html__('انجام‌شده', 'rsd-finance-panel'),
        'closed'       => esc_html__('بسته‌شده', 'rsd-finance-panel'),
        'uploaded'     => esc_html__('ارسال‌شده', 'rsd-finance-panel'),
        'incomplete'   => esc_html__('ناقص', 'rsd-finance-panel'),
        'approved'     => esc_html__('تأییدشده', 'rsd-finance-panel'),
        'rejected'     => esc_html__('ردشده', 'rsd-finance-panel'),
        'unpaid'       => esc_html__('پرداخت‌نشده', 'rsd-finance-panel'),
        'partial'      => esc_html__('پرداخت جزئی', 'rsd-finance-panel'),
        'paid'         => esc_html__('پرداخت‌شده', 'rsd-finance-panel'),
        'overdue'      => esc_html__('سررسید گذشته', 'rsd-finance-panel'),
    ];
}

/**
 * Get current Jalali (Persian Solar Hijri) year.
 * Uses a simple offset conversion: Jalali ≈ Gregorian - 621
 * More precise conversion (e.g. via jDateTime) is used if available.
 *
 * @return int
 */
function rsd_fp_current_jalali_year()
{
    if (class_exists('jDateTime')) {
        return (int) \jDateTime::date('Y');
    }
    if (function_exists('parsidate')) {
        return (int) parsidate('Y', 'now', 'en');
    }
    // Fallback: simple Gregorian-to-Jalali year offset
    $g_year = (int) date('Y');
    $g_month = (int) date('n');
    // Jalali New Year (Nowruz) is March 21 or close: months 1-2 = offset 622, else 621
    return $g_month <= 2 ? ($g_year - 622) : ($g_year - 621);
}

/**
 * Get today's full Jalali date as a formatted string.
 *
 * Uses the same 33-year U.M.A. algorithm (Gregorian -> Jalali parts).
 *
 * @param string $format Optional format using Jalali tokens: Y, m, d, n, j, F
 * @param string $sep    Separator to use between numeric parts (default "/")
 * @return string
 */
function rsd_fp_current_jalali_date($format = 'Y/m/d', $sep = '/')
{
    if (class_exists('jDateTime')) {
        $out = @\jDateTime::date($format);
        if ($out && is_string($out)) return $out;
    }
    if (function_exists('parsidate')) {
        $out = @parsidate($format, 'now', 'en');
        if ($out && is_string($out)) return $out;
    }
    // Fallback: build from jalali parts (U.M.A. algorithm)
    [$gy, $gm, $gd] = [(int)date('Y'), (int)date('n'), (int)date('j')];
    [$jy, $jm, $jd] = rsd_fp_gregorian_to_jalali_parts($gy, $gm, $gd);
    $pn = function ($n) {
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    };
    $month_names = rsd_fp_jalali_month_names();
    $out = '';
    for ($i = 0, $len = strlen($format); $i < $len; $i++) {
        $c = $format[$i];
        switch ($c) {
            case 'Y':
                $out .= (string)$jy;
                break;
            case 'm':
                $out .= $pn($jm);
                break;
            case 'n':
                $out .= (string)$jm;
                break;
            case 'd':
                $out .= $pn($jd);
                break;
            case 'j':
                $out .= (string)$jd;
                break;
            case 'F':
                $out .= isset($month_names[$jm]) ? $month_names[$jm] : (string)$jm;
                break;
            default:
                $out .= $c;
        }
    }
    return $out;
}

/**
 * Get an array of recent Jalali years for drop-downs
 *
 * @param int $past   Number of past years (including current)
 * @param int $future Number of future years
 * @return array
 */
function rsd_fp_jalali_years($past = 7, $future = 1)
{
    $current = rsd_fp_current_jalali_year();
    $years = [];
    $end = $current + $future;
    $start = $current - ($past - 1);
    for ($y = $end; $y >= $start; $y--) {
        $years[] = $y;
    }
    return $years;
}

/**
 * Convert Gregorian (Y-m-d numeric components) → Jalali [Y, m, d]
 *
 * Uses the standard 2820-year "U.M.A." 33-year leap-cycle algorithm.
 *
 * @param int $gy
 * @param int $gm
 * @param int $gd
 * @return array{0:int,1:int,2:int}
 */
function rsd_fp_gregorian_to_jalali_parts($gy, $gm, $gd)
{
    $gy = (int)$gy;
    $gm = (int)$gm;
    $gd = (int)$gd;

    // Standard U.M.A. 33-year cycle Jalali conversion (Birashk algorithm)
    $gy_norm = $gy - 1600;
    $gm_norm = $gm - 1;
    $gd_norm = $gd - 1;

    // Gregorian day-number (from epoch 1600-03-20 = day 0 for Jalali alignment)
    $g_d_m = [0, 31, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335];
    $g_day_no = (365 * $gy_norm)
        + (int)(($gy_norm + 3) / 4)
        - (int)(($gy_norm + 99) / 100)
        + (int)(($gy_norm + 399) / 400)
        + $g_d_m[$gm_norm]
        + $gd_norm;
    // Add +1 for Gregorian leap-years if month is March or later
    $is_greg_leap = (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0));
    if ($gm_norm > 1 && $is_greg_leap) {
        $g_day_no++;
    }

    // Nowruz (21 March) offset: day 79 in a standard Gregorian year from 01 Jan
    $j_day_no = $g_day_no - 79;
    if ($j_day_no < 0) {
        $j_day_no += 365;
        if ($is_greg_leap) $j_day_no--;
    }

    // 33-year Jalali cycles = 12053 days; 4-year sub-cycles = 1461 days
    $j_cycle_33 = (int)($j_day_no / 12053);
    $j_day_no %= 12053;

    $jy = 979 + (33 * $j_cycle_33) + 4 * (int)($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += (int)(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    if ($j_day_no < 186) {
        $jm = 1 + (int)($j_day_no / 31);
        $jd = 1 + ($j_day_no % 31);
    } else {
        $remain = $j_day_no - 186;
        $jm = 7 + (int)($remain / 30);
        $jd = 1 + ($remain % 30);
    }

    // Safety: if somehow year is out of Jalali range, apply known-good offset fallback
    if ($jy < 1300 || $jy > 2000) {
        $fallback_y = (int)date('Y');
        $fallback_m = (int)date('n');
        $jy = ($fallback_m <= 2) ? ($fallback_y - 622) : ($fallback_y - 621);
        $jm = max(1, min(12, $jm));
        $jd = max(1, min(31, $jd));
    }

    return [$jy, $jm, $jd];
}

/**
 * Convert Jalali (Y-m-d numeric components) → Gregorian [Y, m, d]
 *
 * @param int $jy
 * @param int $jm
 * @param int $jd
 * @return array{0:int,1:int,2:int}
 */
function rsd_fp_jalali_to_gregorian_parts($jy, $jm, $jd)
{
    $jy = (int) $jy + 1595;
    $jm = (int) $jm;
    $jd = (int) $jd;
    $days = -355668
        + (365 * $jy) + ((int)(($jy - 1) / 33)) * 8 + ((int)((($jy % 33) + 3) / 4))
        + ((($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186) + $jd);

    $gy = 400 * ((int)($days / 146097));
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * ((int)(--$days / 36524));
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $gy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;

    $sal_a = [
        0,
        31,
        (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28,
        31,
        30,
        31,
        30,
        31,
        31,
        30,
        31,
        30,
        31
    ];
    for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) {
        $gd -= $sal_a[$gm];
    }
    return [$gy, $gm, $gd];
}

/**
 * Jalali month names (used instead of date_i18n which only knows Gregorian).
 *
 * @return string[]
 */
function rsd_fp_jalali_month_names()
{
    return [
        1  => 'فروردین',
        2  => 'اردیبهشت',
        3  => 'خرداد',
        4  => 'تیر',
        5  => 'مرداد',
        6  => 'شهریور',
        7  => 'مهر',
        8  => 'آبان',
        9  => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];
}

/**
 * Jalali-aware date formatter. Accepts Gregorian input (DATE column Y-m-d or timestamp)
 * and returns Jalali string. Supports a small subset of `date()` format chars:
 *   Y = 4-digit Jalali year
 *   y = 2-digit
 *   m = 0-padded month
 *   n = month without leading zero
 *   F = month name
 *   d = 0-padded day
 *   j = day without leading zero
 *   H,i,s = pass-through (if input is timestamp)
 *
 * @param string $format
 * @param int|string|null $input Timestamp OR Gregorian 'Y-m-d' string. Default current time.
 * @return string
 */
function rsd_fp_jalali_date($format = 'j F Y', $input = null)
{
    if ($input === null || $input === '') {
        $timestamp = time();
    } elseif (is_numeric($input)) {
        $timestamp = (int) $input;
    } else {
        $timestamp = is_string($input) ? strtotime($input) : time();
    }
    if ($timestamp === false) {
        $timestamp = time();
    }

    [$gy, $gm, $gd] = [(int)date('Y', $timestamp), (int)date('n', $timestamp), (int)date('j', $timestamp)];
    [$jy, $jm, $jd] = rsd_fp_gregorian_to_jalali_parts($gy, $gm, $gd);

    $months = rsd_fp_jalali_month_names();
    $out = '';
    for ($i = 0, $len = strlen($format); $i < $len; $i++) {
        $c = $format[$i];
        switch ($c) {
            case 'Y':
                $out .= (string) $jy;
                break;
            case 'y':
                $out .= substr((string)$jy, -2);
                break;
            case 'm':
                $out .= str_pad((string)$jm, 2, '0', STR_PAD_LEFT);
                break;
            case 'n':
                $out .= (string)$jm;
                break;
            case 'F':
                $out .= $months[$jm] ?? '';
                break;
            case 'd':
                $out .= str_pad((string)$jd, 2, '0', STR_PAD_LEFT);
                break;
            case 'j':
                $out .= (string)$jd;
                break;
            case 'H':
                $out .= date('H', $timestamp);
                break;
            case 'i':
                $out .= date('i', $timestamp);
                break;
            case 's':
                $out .= date('s', $timestamp);
                break;
            case '\\':
                $i++;
                $out .= $i < $len ? $format[$i] : '';
                break;
            default:
                $out .= $c;
        }
    }
    return $out;
}

/**
 * Parse a Jalali date string (accepts separators / - or .) and return a Gregorian
 * Y-m-d string ready to be stored in a DATE column. Returns null on invalid input.
 *
 * Examples of accepted input:
 *   "1404/06/15" , "1404-6-15", "۱۴۰۴/۰۶/۱۵"
 *
 * @param string $input
 * @return string|null
 */
function rsd_fp_parse_jalali_to_gregorian($input)
{
    $input = trim((string)$input);
    if ($input === '') {
        return null;
    }
    // Allow Persian/Arabic numerals → ASCII digits
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $ascii   = range('0', '9');
    $input = str_replace($persian, $ascii, $input);
    $input = str_replace($arabic,  $ascii, $input);

    if (!preg_match('/^(\d{4})[\-\/\.](\d{1,2})[\-\/\.](\d{1,2})$/', $input, $m)) {
        return null;
    }
    $jy = (int)$m[1];
    $jm = (int)$m[2];
    $jd = (int)$m[3];
    if ($jy < 1200 || $jy > 1600 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
        return null;
    }
    // Months 7..12 max 30 day
    if ($jm >= 7 && $jd > 30) {
        return null;
    }
    // Esfand leap-year handling: 29 or 30
    if ($jm === 12) {
        // Round-trip check: convert candidate Esfand 30 → Greg → back to Jal.
        // If round-trip result is NOT 12/30 → max valid Esfand day = 29 (non-leap).
        list($gy, $gm, $gd) = rsd_fp_jalali_to_gregorian_parts($jy, 12, 30);
        $back = rsd_fp_gregorian_to_jalali_parts($gy, $gm, $gd);
        if ($back[0] !== $jy || $back[1] !== 12 || $back[2] !== 30) {
            if ($jd > 29) {
                return null;
            }
        }
    }

    [$gy, $gm, $gd] = rsd_fp_jalali_to_gregorian_parts($jy, $jm, $jd);
    return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
}

/**
 * Given a Gregorian date string/timestamp, return a Jalali display string
 * "YYYY/MM/DD". Mirror of date('Y/m/d') but in Jalali.
 *
 * @param int|string|null $input
 * @return string
 */
function rsd_fp_jalali_ymd($input = null)
{
    return rsd_fp_jalali_date('Y/m/d', $input);
}

/**
 * Return documents (optionally filtered by user_id) with non-empty expiry_date
 * as synthetic event-style objects so they can be merged with Calendar events
 * and rendered in dashboard deadlines list and both frontend/admin calendar
 * grids without template rewrites.
 *
 * Result objects carry these fields (same shape as calendar rows for merge):
 *   id, title, start_date (= expiry_date), category, user_id, status,
 *   source='document', document_id, is_incomplete, is_past_expiry, days_left.
 *
 * @param int|null $user_id   If null, return across all users (admin view).
 * @param array    $args      Optional { before_days: int, only_upcoming: bool, limit: int }
 * @return array Array of stdClass event-like records.
 */
function rsd_fp_documents_as_events($user_id = null, array $args = [])
{
    global $wpdb;
    $table = \RSD\FinancePanel\Core\Database::table('documents');

    $only_upcoming = !empty($args['only_upcoming']);
    $limit         = isset($args['limit']) ? (int) $args['limit'] : null;
    $before_days   = isset($args['before_days']) ? (int) $args['before_days'] : null; // only include docs with expiry <= today+N days

    $where = ['expiry_date IS NOT NULL AND expiry_date != ""'];
    $prepare_args = [];

    if (null !== $user_id) {
        $where[] = 'user_id = %d';
        $prepare_args[] = (int) $user_id;
    }

    $today = current_time('Y-m-d');
    if ($only_upcoming) {
        $where[] = 'expiry_date >= %s';
        $prepare_args[] = $today;
    }
    if (null !== $before_days && $before_days > 0) {
        $where[] = 'expiry_date <= DATE_ADD(%s, INTERVAL %d DAY)';
        $prepare_args[] = $today;
        $prepare_args[] = $before_days;
    }

    $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " ORDER BY expiry_date ASC";
    if (null !== $limit) {
        $sql .= ' LIMIT %d';
        $prepare_args[] = $limit;
    }

    if (!empty($prepare_args)) {
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_args));
    } else {
        $rows = $wpdb->get_results($sql);
    }

    $doc_cats   = \RSD\FinancePanel\Modules\Documents::get_categories();
    $today_ts   = strtotime($today . ' 00:00:00');
    $synthesized = [];

    foreach ((array) $rows as $d) {
        $exp_ts = strtotime(($d->expiry_date ?? '') . ' 00:00:00');
        if (false === $exp_ts) continue;

        $days_left = (int) round(($exp_ts - $today_ts) / 86400);
        $is_past   = $days_left < 0;

        // Map document category to a calendar category for colouring.
        $category   = 'deadline';
        $dc = $d->category ?? '';
        if (in_array($dc, ['tax_filing', 'tax', 'vat'], true))       $category = 'tax';
        elseif (in_array($dc, ['insurance', 'labor', 'social'], true)) $category = 'payroll';
        elseif (in_array($dc, ['company_reg', 'contract', 'agreement'], true)) $category = 'company';
        elseif (in_array($dc, ['reminder', 'notice'], true))          $category = 'reminder';

        $title = trim($d->title ?? '');
        if ('' === $title) {
            $cat_label = $doc_cats[$dc] ?? (($dc ?: 'مدرک'));
            $title = sprintf('انقضای %s', $cat_label);
        }
        if (!empty($d->is_incomplete)) {
            $title .= ' (ناقص)';
        }

        $event = (object) [
            'id'             => -(int) $d->id, // negative ids to avoid collision w/ calendar rows
            'title'          => $title,
            'start_date'     => $d->expiry_date,
            'end_date'       => $d->expiry_date,
            'category'       => $category,
            'user_id'        => (int) $d->user_id,
            'status'         => $is_past ? 'completed' : 'confirmed',
            'description'    => $d->description ?? '',
            'source'         => 'document',
            'document_id'    => (int) $d->id,
            'document_cat'   => $dc,
            'is_incomplete'  => !empty($d->is_incomplete),
            'is_past_expiry' => $is_past,
            'days_left'      => $days_left,
            'is_all_day'     => 1,
        ];
        $synthesized[] = $event;
    }

    return $synthesized;
}

/**
 * Merge calendar events with synthetic document-expiry events, sort ascending
 * by start_date, optionally slice to a limit.
 *
 * @param array $calendar_events
 * @param array $doc_events
 * @param int|null $limit
 * @return array
 */
function rsd_fp_merge_calendar_and_doc_events(array $calendar_events, array $doc_events, $limit = null)
{
    $merged = array_merge($calendar_events, $doc_events);
    usort($merged, function ($a, $b) {
        $tsA = strtotime($a->start_date ?? '0000-00-00');
        $tsB = strtotime($b->start_date ?? '0000-00-00');
        return $tsA <=> $tsB;
    });
    if (null !== $limit && $limit > 0) {
        $merged = array_slice($merged, 0, $limit);
    }
    return $merged;
}

/**
 * Render a Jalali triple-select (year/month/day) that synchronizes to a hidden input
 * holding the combined string "YYYY/MM/DD" (Jalali digits, slash-separated).
 *
 * Used by invoices, documents, calendar edit modal, payment records and any other
 * date form control that should behave identically across admin and (optionally) front-end.
 *
 * @param string $target_name  Name attribute for the hidden input holding the combined date string
 * @param string $prefix       Unique prefix used to build element IDs ("{$prefix}-hidden", data-rsd-triple-target)
 * @param bool   $required     Whether required attribute should be applied
 * @param string $default_value Optional default date string, accepted formats: YYYY/MM/DD or YYYY-MM-DD (Jalali or Gregorian → caller decides)
 * @return void
 */
if (!function_exists('rsd_fp_render_jalali_triple')) {
    function rsd_fp_render_jalali_triple($target_name, $prefix, $required = false, $default_value = '')
    {
        $persian_months = [
            1  => 'فروردین',
            2  => 'اردیبهشت',
            3  => 'خرداد',
            4  => 'تیر',
            5  => 'مرداد',
            6  => 'شهریور',
            7  => 'مهر',
            8  => 'آبان',
            9  => 'آذر',
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
