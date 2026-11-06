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
 * Get the configured currency symbol (defaults to Dollar).
 *
 * @return string
 */
function rsd_fp_currency_symbol()
{
    return (string) \RSD\FinancePanel\Core\Plugin::get_setting('currency_symbol', '$');
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
        'new'          => esc_html__('New', 'rsd-finance-panel'),
        'pending'      => esc_html__('Pending', 'rsd-finance-panel'),
        'in_progress'  => esc_html__('In Progress', 'rsd-finance-panel'),
        'waiting'      => esc_html__('Waiting for Client', 'rsd-finance-panel'),
        'completed'    => esc_html__('Completed', 'rsd-finance-panel'),
        'closed'       => esc_html__('Closed', 'rsd-finance-panel'),
        'uploaded'     => esc_html__('Uploaded', 'rsd-finance-panel'),
        'incomplete'   => esc_html__('Incomplete', 'rsd-finance-panel'),
        'approved'     => esc_html__('Approved', 'rsd-finance-panel'),
        'rejected'     => esc_html__('Rejected', 'rsd-finance-panel'),
        'unpaid'       => esc_html__('Unpaid', 'rsd-finance-panel'),
        'partial'      => esc_html__('Partially Paid', 'rsd-finance-panel'),
        'paid'         => esc_html__('Paid', 'rsd-finance-panel'),
        'overdue'      => esc_html__('Overdue', 'rsd-finance-panel'),
        'confirmed'    => esc_html__('Confirmed', 'rsd-finance-panel'),
        'needs_change' => esc_html__('Needs Change', 'rsd-finance-panel'),
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
 * Get array of status labels (in English)
 *
 * @return array
 */
function rsd_fp_status_labels()
{
    return [
        'new'          => esc_html__('New', 'rsd-finance-panel'),
        'pending'      => esc_html__('Pending', 'rsd-finance-panel'),
        'in_progress'  => esc_html__('In Progress', 'rsd-finance-panel'),
        'waiting'      => esc_html__('Waiting for Client', 'rsd-finance-panel'),
        'completed'    => esc_html__('Completed', 'rsd-finance-panel'),
        'closed'       => esc_html__('Closed', 'rsd-finance-panel'),
        'uploaded'     => esc_html__('Uploaded', 'rsd-finance-panel'),
        'incomplete'   => esc_html__('Incomplete', 'rsd-finance-panel'),
        'approved'     => esc_html__('Approved', 'rsd-finance-panel'),
        'rejected'     => esc_html__('Rejected', 'rsd-finance-panel'),
        'unpaid'       => esc_html__('Unpaid', 'rsd-finance-panel'),
        'partial'      => esc_html__('Partially Paid', 'rsd-finance-panel'),
        'paid'         => esc_html__('Paid', 'rsd-finance-panel'),
        'overdue'      => esc_html__('Overdue', 'rsd-finance-panel'),
    ];
}

/**
 * Get the current year.
 *
 * @return int
 */
function rsd_fp_current_jalali_year()
{
    return (int) date('Y');
}

/**
 * Get today's date as a formatted string.
 *
 * @param string $format Format string using `date()` tokens (Y, m, d, n, j, F, ...)
 * @param string $sep    Unused; kept for backwards compatibility.
 * @return string
 */
function rsd_fp_current_jalali_date($format = 'Y/m/d', $sep = '/')
{
    return date($format);
}

/**
 * Get an array of recent years for drop-downs
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
 * Split a Gregorian date into its [Y, m, d] numeric components.
 *
 * @param int $gy
 * @param int $gm
 * @param int $gd
 * @return array{0:int,1:int,2:int}
 */
function rsd_fp_gregorian_to_jalali_parts($gy, $gm, $gd)
{
    return [(int) $gy, (int) $gm, (int) $gd];
}

/**
 * Return the supplied [Y, m, d] components unchanged.
 *
 * @param int $jy
 * @param int $jm
 * @param int $jd
 * @return array{0:int,1:int,2:int}
 */
function rsd_fp_jalali_to_gregorian_parts($jy, $jm, $jd)
{
    return [(int) $jy, (int) $jm, (int) $jd];
}

/**
 * English month names indexed 1-12.
 *
 * @return string[]
 */
function rsd_fp_jalali_month_names()
{
    return [
        1  => 'January',
        2  => 'February',
        3  => 'March',
        4  => 'April',
        5  => 'May',
        6  => 'June',
        7  => 'July',
        8  => 'August',
        9  => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];
}

/**
 * Date formatter. Accepts a Gregorian input (DATE column Y-m-d or timestamp)
 * and returns a formatted string. Supports a subset of `date()` format chars:
 *   Y = 4-digit year
 *   y = 2-digit year
 *   m = 0-padded month
 *   n = month without leading zero
 *   F = month name
 *   d = 0-padded day
 *   j = day without leading zero
 *   H,i,s = time components
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

    return date($format, $timestamp);
}

/**
 * Parse a date string (accepts separators / - or .) and return a Gregorian
 * Y-m-d string ready to be stored in a DATE column. Returns null on invalid input.
 *
 * Examples of accepted input:
 *   "2025/06/15" , "2025-6-15"
 *
 * @param string $input
 * @return string|null
 */
function rsd_fp_parse_jalali_to_gregorian($input)
{
    $input = trim((string) $input);
    if ($input === '') {
        return null;
    }
    // Normalise Persian/Arabic numerals to ASCII digits before parsing
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $ascii   = range('0', '9');
    $input = str_replace($persian, $ascii, $input);
    $input = str_replace($arabic,  $ascii, $input);

    if (!preg_match('/^(\d{4})[\-\/\.](\d{1,2})[\-\/\.](\d{1,2})$/', $input, $m)) {
        return null;
    }
    $year  = (int) $m[1];
    $month = (int) $m[2];
    $day   = (int) $m[3];
    if (!checkdate($month, $day, $year)) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

/**
 * Given a Gregorian date string/timestamp, return a "YYYY/MM/DD" display string.
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
            $cat_label = $doc_cats[$dc] ?? (($dc ?: 'Document'));
            $title = sprintf('Expiry of %s', $cat_label);
        }
        if (!empty($d->is_incomplete)) {
            $title .= ' (Incomplete)';
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
 * Render a triple-select (year/month/day) that synchronizes to a hidden input
 * holding the combined string "YYYY/MM/DD" (slash-separated).
 *
 * Used by invoices, documents, calendar edit modal, payment records and any other
 * date form control that should behave identically across admin and (optionally) front-end.
 *
 * @param string $target_name  Name attribute for the hidden input holding the combined date string
 * @param string $prefix       Unique prefix used to build element IDs ("{$prefix}-hidden", data-rsd-triple-target)
 * @param bool   $required     Whether required attribute should be applied
 * @param string $default_value Optional default date string, accepted formats: YYYY/MM/DD or YYYY-MM-DD
 * @return void
 */
if (!function_exists('rsd_fp_render_jalali_triple')) {
    function rsd_fp_render_jalali_triple($target_name, $prefix, $required = false, $default_value = '')
    {
        $months = rsd_fp_jalali_month_names();
        $current_year = (int) date('Y');
        $years = range($current_year - 20, $current_year + 5);
        $days  = range(1, 31);

        $sel_y = '';
        $sel_m = '';
        $sel_d = '';
        $dv = trim((string) $default_value);
        if ($dv !== '' && preg_match('#^(\d{4})[\-\/](0?[1-9]|1[0-2])[\-\/](0?[1-9]|[12]\d|3[01])$#', $dv, $mm)) {
            $sel_y = (string) (int) $mm[1];
            $sel_m = (int) $mm[2];
            $sel_d = (int) $mm[3];
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
                <?php foreach ($months as $mn => $ml) :
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
