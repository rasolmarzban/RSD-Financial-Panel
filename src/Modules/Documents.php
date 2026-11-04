<?php

/**
 * Documents module class
 *
 * Business logic for document management
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Database;

class Documents
{
    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        // Hook into upload for post-processing
        add_action('rsd_fp_document_uploaded', [$this, 'maybe_mark_complete'], 10, 2);

        // Notification + email flow:
        // (A) Admin sends a new document request -> notify client.
        // (B) Admin changes any existing document status TO incomplete -> notify client.
        add_action('rsd_fp_document_requested',        [$this, 'notify_client_document_requested'], 10, 3);
        add_action('rsd_fp_document_status_updated',   [$this, 'notify_client_document_status_incomplete'], 10, 4);
    }

    /**
     * Insert a row into the notifications table (reference_type = document).
     *
     * Mirrors Tickets::add_notification pattern so we keep DB write paths consistent.
     *
     * @param int    $user_id
     * @param string $type           document_requested | document_incomplete
     * @param string $title
     * @param string $message
     * @param int    $reference_id   document id
     * @return int|false
     */
    private static function add_notification($user_id, $type, $title, $message = '', $reference_id = 0)
    {
        global $wpdb;
        $table = Database::table('notifications');
        $inserted = $wpdb->insert($table, [
            'user_id'        => (int) $user_id,
            'type'           => mb_substr(sanitize_key($type), 0, 50),
            'title'          => mb_substr(sanitize_text_field($title), 0, 255),
            'message'        => empty($message) ? null : sanitize_textarea_field($message),
            'reference_type' => 'document',
            'reference_id'   => (int) $reference_id,
            'is_read'        => 0,
            'created_at'     => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s']);
        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * Build client-portal URL scoped to INCOMPLETE documents so the bell link
     * lets the user land exactly where they can re-upload the flagged item.
     *
     * @param int $doc_id
     * @return string
     */
    private static function client_incomplete_url($doc_id = 0)
    {
        $url = function_exists('rsd_fp_page_url') ? rsd_fp_page_url('documents') : home_url('/finance-documents/');
        $args = ['tab' => 'incomplete'];
        if ($doc_id > 0) {
            $args['doc_id'] = (int) $doc_id;
        }
        return add_query_arg($args, $url);
    }

    /**
     * When admin submits a brand new incomplete-document request, create
     * a notification row + (optional) wp_mail email so user hears about it.
     *
     * Hooked via rsd_fp_document_requested.
     *
     * @param int   $doc_id
     * @param int   $client_id
     * @param array $meta  { title, category, year, month, period, description, expiry_date, is_incomplete=1 }
     * @return void
     */
    public function notify_client_document_requested($doc_id, $client_id, $meta)
    {
        $doc_id    = (int) $doc_id;
        $client_id = (int) $client_id;
        if ($doc_id <= 0 || $client_id <= 0) {
            return;
        }

        $title_raw   = $meta['title'] ?? '';
        $expiry_raw  = $meta['expiry_date'] ?? '';
        $period_raw  = isset($meta['period']) || isset($meta['year'])
            ? trim(($meta['year'] ?? '') . (!empty($meta['period']) ? ' - ' . $meta['period'] : ''))
            : '';

        $notice_title = sprintf('درخواست مدرک جدید: %s', $title_raw);
        $notice_msg   = 'مدیر یک مدرک را برای شما درخواست کرده است. '
            . (!empty($period_raw) ? ' دوره مربوطه: ' . trim($period_raw) . '.' : '')
            . (!empty($expiry_raw) ? ' مهلت ارسال: ' . rsd_fp_jalali_date('j F Y', $expiry_raw) . '.' : '')
            . ' برای آپلود روی اعلان کلیک کنید.';

        self::add_notification($client_id, 'document_requested', $notice_title, $notice_msg, $doc_id);

        $client_user = get_user_by('id', $client_id);
        if ($client_user && !empty($client_user->user_email)) {
            $body  = "درخواست مدرک جدیدی از سوی مدیریت برای شما ثبت شده است.\n\n";
            $body .= "عنوان مدرک: " . ($title_raw ?: '# ' . $doc_id) . "\n";
            if (!empty($period_raw))   $body .= "دوره: " . $period_raw . "\n";
            if (!empty($meta['description'] ?? '')) $body .= "توضیحات: " . $meta['description'] . "\n";
            if (!empty($expiry_raw))  $body .= "مهلت ارسال: " . rsd_fp_jalali_date('j F Y', $expiry_raw) . "\n";
            $body .= "\nجهت آپلود به پرتال مراجعه کنید: " . self::client_incomplete_url($doc_id) . "\n";
            @wp_mail($client_user->user_email, sprintf('درخواست مدرک جدید: %s', $title_raw), $body);
        }
    }

    /**
     * When admin changes an existing document's status TO incomplete, notify the client.
     *
     * Hooked via rsd_fp_document_status_updated($doc_id, $new_status, $is_incomplete, $client_id).
     *
     * @param int    $doc_id
     * @param string $new_status
     * @param int    $is_incomplete
     * @param int    $client_id
     * @return void
     */
    public function notify_client_document_status_incomplete($doc_id, $new_status, $is_incomplete, $client_id)
    {
        $doc_id      = (int) $doc_id;
        $client_id   = (int) $client_id;
        $is_incomplete = (int) $is_incomplete;
        if ($doc_id <= 0 || $client_id <= 0) {
            return;
        }
        if ($new_status !== 'incomplete' && $is_incomplete !== 1) {
            return;
        }

        $doc         = Database::get_row('documents', $doc_id);
        $title_raw   = $doc->title ?? '';
        $expiry_raw  = $doc->expiry_date ?? '';
        $period_raw  = !empty($doc) && ($doc->year || $doc->period)
            ? trim(($doc->year ?? '') . (!empty($doc->period) ? ' - ' . $doc->period : ''))
            : '';

        $notice_title = sprintf('مدرک ناقص شده است: %s', $title_raw);
        $notice_msg   = 'مدیر مدرک شما را ناقص ثبت کرده است. لطفاً نسخه صحیح را مجدداً در همان درخواست آپلود کنید.'
            . (!empty($period_raw) ? ' دوره: ' . $period_raw . '.' : '')
            . (!empty($expiry_raw) ? ' مهلت ارسال: ' . rsd_fp_jalali_date('j F Y', $expiry_raw) . '.' : '');

        self::add_notification($client_id, 'document_incomplete', $notice_title, $notice_msg, $doc_id);

        $client_user = get_user_by('id', $client_id);
        if ($client_user && !empty($client_user->user_email)) {
            $body  = "مدیر مدرک زیر را ناقص ثبت کرده و از شما می‌خواهد نسخه صحیح را مجدداً بارگذاری کنید.\n\n";
            $body .= "عنوان مدرک: " . ($title_raw ?: '# ' . $doc_id) . "\n";
            if (!empty($period_raw))  $body .= "دوره: " . $period_raw . "\n";
            if (!empty($doc->description ?? '')) $body .= "توضیحات: " . $doc->description . "\n";
            if (!empty($expiry_raw)) $body .= "مهلت ارسال: " . rsd_fp_jalali_date('j F Y', $expiry_raw) . "\n";
            $body .= "\nجهت آپلود مجدد وارد پرتال شوید: " . self::client_incomplete_url($doc_id) . "\n";
            @wp_mail($client_user->user_email, sprintf('مدرک ناقص شده: %s', $title_raw), $body);
        }
    }

    /**
     * If document was marked as incomplete (requested), mark status appropriately
     *
     * @param int $doc_id
     * @param array $data
     * @return void
     */
    public function maybe_mark_complete($doc_id, $data)
    {
        $doc = Database::get_row('documents', $doc_id);
        if ($doc && $doc->is_incomplete && $doc->file_path) {
            Database::update('documents', [
                'status'        => 'uploaded',
                'is_incomplete' => 0,
            ], ['id' => $doc_id]);
        }
    }

    /**
     * Get document categories from settings
     *
     * @return array
     */
    public static function get_categories()
    {
        $cats = \RSD\FinancePanel\Core\Plugin::get_setting('document_categories', []);
        if (empty($cats)) {
            $cats = [
                'invoice'      => esc_html__('فاکتور', 'rsd-finance-panel'),
                'receipt'      => esc_html__('رسید', 'rsd-finance-panel'),
                'contract'     => esc_html__('قرارداد', 'rsd-finance-panel'),
                'tax_document' => esc_html__('سند مالیاتی', 'rsd-finance-panel'),
                'payroll'      => esc_html__('حقوق و دستمزد', 'rsd-finance-panel'),
                'other'        => esc_html__('سایر', 'rsd-finance-panel'),
            ];
        }
        return $cats;
    }

    /**
     * Get allowed document statuses as [key => ['label', 'color']]
     *
     * Single source of truth so templates + SQL validation use the same set.
     *
     * @return array{key: array{0:string, 1:string}}
     */
    public static function get_statuses()
    {
        return [
            'uploaded'   => [esc_html__('ارسال‌شده', 'rsd-finance-panel'),   'green'],
            'incomplete' => [esc_html__('ناقص', 'rsd-finance-panel'),       'red'],
            'approved'   => [esc_html__('تأییدشده', 'rsd-finance-panel'),   'purple'],
        ];
    }

    /**
     * Get count of incomplete documents for a client
     *
     * @param int $user_id
     * @return int
     */
    public static function count_incomplete($user_id)
    {
        global $wpdb;
        $table = Database::table('documents');
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_incomplete = 1",
            $user_id
        ));
    }

    /**
     * Get allowed mime types for upload
     *
     * @return array
     */
    public static function allowed_mimes()
    {
        return apply_filters('rsd_fp_document_mimes', [
            'pdf'   => 'application/pdf',
            'doc'   => 'application/msword',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'   => 'application/vnd.ms-excel',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'zip'   => 'application/zip',
            'rar'   => 'application/x-rar-compressed',
            'txt'   => 'text/plain',
            'csv'   => 'text/csv',
        ]);
    }
}
