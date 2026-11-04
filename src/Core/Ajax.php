<?php

/**
 * AJAX Handler class
 *
 * Handles all AJAX requests for the plugin
 *
 * @package RSD_Finance_Panel
 * @subpackage Core
 */

namespace RSD\FinancePanel\Core;

defined('ABSPATH') || exit;

class Ajax
{
    /**
     * Register AJAX actions
     *
     * @return void
     */
    public function init()
    {
        // Authentication check
        $actions = [
            // Documents
            'upload_document',
            'delete_document',
            'request_document',
            'admin_update_document_status',

            // Tickets
            'create_ticket',
            'reply_ticket',
            'close_ticket',
            'get_ticket_messages',
            'admin_update_ticket',
            'admin_create_ticket',

            // Services
            'submit_service_request',

            // Calendar
            'get_calendar_events',
            'create_calendar_event',
            'update_calendar_event',
            'delete_calendar_event',
            'request_meeting',

            // Invoices
            'get_invoice_details',
            'create_invoice',
            'update_invoice',
            'delete_invoice',
            'add_invoice_payment',

            // Documents batch upload
            'batch_upload_documents',

            // Company info
            'save_company_info',
            'submit_company_changes',
            'approve_company_changes',
            'reject_company_changes',

            // Generic
            'dismiss_notification',
            'read_notifications',

            // Reports (admin)
            'save_report',
            'update_report',
            'delete_report',
        ];

        foreach ($actions as $action) {
            add_action('wp_ajax_rsd_fp_' . $action, [$this, $action]);
        }

        // Auth actions — registered both as nopriv (guests) and priv (logged-in reset pass)
        $auth_actions = [
            'auth_register',
            'auth_login',
            'auth_lostpassword',
            'auth_reset_password',
            'auth_send_otp',
            'auth_verify_otp',
        ];
        foreach ($auth_actions as $a) {
            add_action('wp_ajax_nopriv_rsd_fp_' . $a, [$this, $a]);
            add_action('wp_ajax_rsd_fp_' . $a,        [$this, $a]);
        }
    }

    /**
     * Send JSON response
     *
     * @param bool $success
     * @param mixed $data
     * @param int $code
     * @return void
     */
    private function send($success, $data = null, $code = 200)
    {
        wp_send_json([
            'success' => $success,
            'data'    => $data,
        ], $code);
    }

    /**
     * Verify AJAX nonce and permissions
     *
     * @param bool $require_client
     * @return void
     */
    private function verify($require_client = true)
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rsd_fp_nonce')) {
            $this->send(false, ['message' => esc_html__('توکن امنیتی نامعتبر است.', 'rsd-finance-panel')], 403);
        }

        if ($require_client && !current_user_can('rsd_fp_view_dashboard') && !current_user_can('manage_options')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')], 403);
        }
    }

    /**
     * Normalize an expiry_date field from either:
     *   - Jalali input YYYY/MM/DD  (default UI from now on)
     *   - Gregorian YYYY-MM-DD (legacy, to avoid breaking existing partial code)
     *
     * Always returns a Gregorian DATE Y-m-d ready for DB, or null when empty.
     * Sends an error response and bails out when format is clearly wrong.
     *
     * @param string $raw
     * @param string $empty_ok   return null when empty (default true)
     * @return string|null
     */
    private function normalize_expiry($raw, $empty_ok = true)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            if ($empty_ok) return null;
            $this->send(false, ['message' => esc_html__('تاریخ انقضا الزامی است.', 'rsd-finance-panel')]);
        }
        // Gregorian dash-format (legacy / tests):
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        // Jalali slash-format (default)
        $gregorian = function_exists('rsd_fp_parse_jalali_to_gregorian') ? rsd_fp_parse_jalali_to_gregorian($raw) : null;
        if ($gregorian === null) {
            $this->send(false, [
                'message' => esc_html__('فرمت تاریخ انقضا نامعتبر است. لطفاً با قالب شمسی ۱۴۰۰/۰۱/۰۱ وارد کنید.', 'rsd-finance-panel'),
            ]);
        }
        return $gregorian;
    }

    /**
     * Handle document upload
     *
     * @return void
     */
    public function upload_document()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $doc_id_in = (int)($_POST['rsd_doc_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'other');
        $year = sanitize_text_field($_POST['year'] ?? '');
        $month = sanitize_text_field($_POST['month'] ?? '');
        $period = sanitize_text_field($_POST['period'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $expiry_date = $this->normalize_expiry($_POST['expiry_date'] ?? '', true);

        if (empty($title)) {
            $this->send(false, ['message' => esc_html__('عنوان مدرک الزامی است.', 'rsd-finance-panel')]);
        }

        $is_update = false;
        $existing_doc = null;
        if ($doc_id_in > 0) {
            $existing_doc = Database::get_row('documents', $doc_id_in);
            if (
                $existing_doc
                && ((int)$existing_doc->user_id === $user_id || current_user_can('manage_options'))
                && (int)$existing_doc->is_incomplete === 1
            ) {
                $is_update = true;
            } elseif ($existing_doc) {
                $this->send(false, ['message' => esc_html__('شما مجاز به ویرایش این مدرک نیستید.', 'rsd-finance-panel')]);
            }
        }

        // For NEW document insert a file is required; for UPDATE of an existing
        // incomplete document we allow metadata-only update (no file re-upload).
        $file_data = null;
        $has_file  = !empty($_FILES['document_file']) && is_array($_FILES['document_file']) && UPLOAD_ERR_OK === (int)($_FILES['document_file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($has_file || !$is_update) {
            $file_data = $this->handle_file_upload('document_file');
            if (is_wp_error($file_data)) {
                $this->send(false, ['message' => $file_data->get_error_message()]);
            }
        }

        $data = [
            'user_id'     => $user_id,
            'title'       => $title,
            'category'    => $category,
            'year'        => $year,
            'month'       => $month,
            'period'      => $period,
            'description' => $description,
            'expiry_date' => $expiry_date,
            'uploaded_by' => $user_id,
        ];

        if ($file_data) {
            $data['file_path'] = $file_data['url'];
            $data['file_name'] = $file_data['file'];
            $data['file_size'] = $file_data['size'] ?? 0;
            $data['file_type'] = $file_data['type'] ?? '';
        }

        if ($is_update) {
            $old_file = $existing_doc->file_path ?? '';
            // Completing an incomplete document from user (re-upload) → set status uploaded, drop is_incomplete flag
            $data['status']        = 'uploaded';
            $data['is_incomplete'] = 0;
            $updated = Database::update('documents', $data, ['id' => $existing_doc->id]);
            if (!$updated) {
                $this->send(false, ['message' => esc_html__('به‌روزرسانی مدرک انجام نشد.', 'rsd-finance-panel')]);
            }
            // Replace the old file to free disk space
            if ($file_data && $old_file && !empty($data['file_path']) && $old_file !== $data['file_path']) {
                $local = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $old_file);
                if (file_exists($local)) {
                    wp_delete_file($local);
                }
            }
            $doc_id = (int)$existing_doc->id;
            do_action('rsd_fp_incomplete_document_completed', $doc_id, (int)$user_id, $data);
            do_action('rsd_fp_document_uploaded', $doc_id, $data);

            $this->send(true, [
                'message'   => esc_html__('مدرک ناقص با موفقیت جایگزین و ثبت شد.', 'rsd-finance-panel'),
                'doc_id'    => $doc_id,
                'is_update' => true,
                'metadata_only' => !$file_data,
            ]);
        }

        if (!$file_data) {
            $this->send(false, ['message' => esc_html__('انتخاب فایل برای مدرک جدید الزامی است.', 'rsd-finance-panel')]);
        }
        $data['status'] = 'uploaded';
        $doc_id = Database::insert('documents', $data);

        if (!$doc_id) {
            $this->send(false, ['message' => esc_html__('خطا در ذخیره مدرک.', 'rsd-finance-panel')]);
        }

        do_action('rsd_fp_document_uploaded', $doc_id, $data);

        $this->send(true, [
            'message'   => esc_html__('مدرک با موفقیت بارگذاری شد.', 'rsd-finance-panel'),
            'doc_id'    => $doc_id,
            'is_update' => false,
        ]);
    }

    /**
     * Handle batch document upload
     *
     * @return void
     */
    public function batch_upload_documents()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $category = sanitize_text_field($_POST['category'] ?? 'other');
        $year = sanitize_text_field($_POST['year'] ?? '');
        $month = sanitize_text_field($_POST['month'] ?? '');
        $expiry_date = $this->normalize_expiry($_POST['expiry_date'] ?? '', true);

        $uploaded = 0;

        if (!empty($_FILES['documents'])) {
            $files = $this->normalize_file_array($_FILES['documents']);

            foreach ($files as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $_FILES['batch_doc'] = $file;
                $file_data = $this->handle_file_upload('batch_doc');

                if (!is_wp_error($file_data) && $file_data) {
                    $new_id = Database::insert('documents', [
                        'user_id'     => $user_id,
                        'title'       => pathinfo($file['name'], PATHINFO_FILENAME),
                        'category'    => $category,
                        'year'        => $year,
                        'month'       => $month,
                        'expiry_date' => $expiry_date,
                        'file_path'   => $file_data['url'],
                        'file_name'   => $file_data['file'],
                        'file_size'   => $file_data['size'] ?? 0,
                        'file_type'   => $file_data['type'] ?? '',
                        'status'      => 'uploaded',
                        'uploaded_by' => $user_id,
                    ]);
                    if ($new_id) {
                        do_action('rsd_fp_document_uploaded', $new_id, [
                            'user_id'    => $user_id,
                            'title'      => pathinfo($file['name'], PATHINFO_FILENAME),
                            'category'   => $category,
                            'year'       => $year,
                            'month'      => $month,
                            'expiry_date' => $expiry_date,
                            'file_path'  => $file_data['url'],
                        ]);
                    }
                    $uploaded++;
                }
            }
        }

        $this->send(true, [
            'message' => sprintf(esc_html__('%d مدرک با موفقیت بارگذاری شد.', 'rsd-finance-panel'), $uploaded),
            'count'   => $uploaded,
        ]);
    }

    /**
     * Delete document
     *
     * @return void
     */
    public function delete_document()
    {
        $this->verify();

        $doc_id = (int) ($_POST['doc_id'] ?? 0);
        $user_id = get_current_user_id();

        $doc = Database::get_row('documents', $doc_id);

        if (!$doc) {
            $this->send(false, ['message' => esc_html__('مدرک پیدا نشد.', 'rsd-finance-panel')]);
        }

        if (!current_user_can('manage_options') && (int) $doc->user_id !== $user_id) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        if ($doc->file_path) {
            $file_path = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $doc->file_path);
            if (file_exists($file_path)) {
                wp_delete_file($file_path);
            }
        }

        Database::delete('documents', ['id' => $doc_id]);

        $this->send(true, ['message' => esc_html__('مدرک با موفقیت حذف شد.', 'rsd-finance-panel')]);
    }

    /**
     * Admin request document from client
     *
     * @return void
     */
    public function request_document()
    {
        $this->verify();

        if (!current_user_can('manage_options')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        $client_id = (int) ($_POST['user_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $period = sanitize_text_field($_POST['period'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $expiry_date = $this->normalize_expiry($_POST['expiry_date'] ?? '', true);

        if (!$client_id || empty($title)) {
            $this->send(false, ['message' => esc_html__('فیلدهای الزامی تکمیل نشده‌اند.', 'rsd-finance-panel')]);
        }

        $doc_id = Database::insert('documents', [
            'user_id'        => $client_id,
            'title'          => $title,
            'period'         => $period,
            'description'    => $description,
            'expiry_date'    => $expiry_date,
            'status'         => 'incomplete',
            'is_incomplete'  => 1,
            'requested_by'   => get_current_user_id(),
            'requested_date' => current_time('mysql'),
        ]);

        if (!$doc_id) {
            $this->send(false, ['message' => esc_html__('خطا در ثبت درخواست مدرک.', 'rsd-finance-panel')]);
        }

        do_action(
            'rsd_fp_document_requested',
            $doc_id,
            $client_id,
            [
                'title'       => $title,
                'period'      => $period,
                'description' => $description,
                'expiry_date' => $expiry_date,
            ]
        );

        $this->send(true, [
            'message'     => esc_html__('درخواست مدرک برای مشتری ارسال شد.', 'rsd-finance-panel'),
            'doc_id'      => $doc_id,
            'expiry_date' => $expiry_date,
        ]);
    }

    /**
     * Admin change document status + is_incomplete flag sync
     *
     * Expects $_POST[doc_id] + $_POST[status] ∈ {uploaded, incomplete, approved}
     * When status === 'incomplete'  → also sets is_incomplete = 1 (so it shows
     *                                 in client's "مدارک ناقص" widget/section).
     * When status === anything else → also sets is_incomplete = 0.
     *
     * @return void
     */
    public function admin_update_document_status()
    {
        $this->verify();

        if (!current_user_can('manage_options')) {
            $this->send(false, ['message' => esc_html__('فقط مدیر می‌تواند وضعیت مدرک را تغییر دهد.', 'rsd-finance-panel')]);
        }

        $doc_id = (int)($_POST['doc_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        $doc = Database::get_row('documents', $doc_id);
        if (!$doc) {
            $this->send(false, ['message' => esc_html__('مدرک پیدا نشد.', 'rsd-finance-panel')]);
        }

        $allowed = array_keys(\RSD\FinancePanel\Modules\Documents::get_statuses());
        if (!in_array($status, $allowed, true)) {
            $this->send(false, [
                'message' => esc_html__('وضعیت انتخابی نامعتبر است.', 'rsd-finance-panel'),
                'allowed' => $allowed,
            ]);
        }

        $is_incomplete = ($status === 'incomplete') ? 1 : 0;

        $updated = Database::update('documents', [
            'status'        => $status,
            'is_incomplete' => $is_incomplete,
        ], ['id' => $doc_id]);

        if (!$updated) {
            $this->send(false, ['message' => esc_html__('به‌روزرسانی وضعیت مدرک انجام نشد.', 'rsd-finance-panel')]);
        }

        do_action('rsd_fp_document_status_updated', $doc_id, $status, $is_incomplete, (int)$doc->user_id);

        $labels = \RSD\FinancePanel\Modules\Documents::get_statuses();
        $label  = $labels[$status][0] ?? $status;

        $this->send(true, [
            'message'       => sprintf(esc_html__('وضعیت مدرک به «%s» تغییر یافت.', 'rsd-finance-panel'), $label),
            'is_incomplete' => $is_incomplete,
            'doc_id'        => $doc_id,
        ]);
    }

    /**
     * Create new ticket
     *
     * @return void
     */
    public function create_ticket()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'other');
        $type = sanitize_text_field($_POST['type'] ?? 'question');
        $priority = sanitize_text_field($_POST['priority'] ?? 'normal');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            $this->send(false, ['message' => esc_html__('موضوع و متن پیام تیکت الزامی هستند.', 'rsd-finance-panel')]);
        }

        $ticket_number = 'TKT-' . date('Ymd') . '-' . str_pad(wp_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $ticket_id = Database::insert('tickets', [
            'user_id'       => $user_id,
            'ticket_number' => $ticket_number,
            'subject'       => $subject,
            'category'      => $category,
            'type'          => $type,
            'priority'      => $priority,
            'status'        => 'new',
            'created_by'    => $user_id,
            'last_reply_at' => current_time('mysql'),
        ]);

        $msg_id = false;
        if ($ticket_id) {
            $msg_id = Database::insert('ticket_messages', [
                'ticket_id' => $ticket_id,
                'user_id'   => $user_id,
                'message'   => $message,
                'is_read'   => 0,
            ]);
            if (!$msg_id) {
                $this->send(false, ['message' => esc_html__('تیکت ذخیره نشد؛ پیام اولیه ثبت نگردید.', 'rsd-finance-panel')]);
            }
            do_action('rsd_fp_ticket_created', $ticket_id, [
                'ticket_number' => $ticket_number,
                'subject'       => $subject,
                'category'      => $category,
                'priority'      => $priority,
                'user_id'       => $user_id,
                'message'       => $message,
                'message_id'    => $msg_id,
            ]);
        }

        $this->send(true, [
            'message'       => esc_html__('تیکت با موفقیت ثبت شد.', 'rsd-finance-panel'),
            'ticket_id'     => $ticket_id,
            'ticket_number' => $ticket_number,
        ]);
    }

    /**
     * Reply to a ticket
     *
     * @return void
     */
    public function reply_ticket()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($message) || !$ticket_id) {
            $this->send(false, ['message' => esc_html__('متن پاسخ و شماره تیکت الزامی هستند.', 'rsd-finance-panel')]);
        }

        $ticket = Database::get_row('tickets', $ticket_id);

        if (!$ticket) {
            $this->send(false, ['message' => esc_html__('تیکت پیدا نشد.', 'rsd-finance-panel')]);
        }

        if (!current_user_can('manage_options') && (int) $ticket->user_id !== $user_id) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        $attachments = [];
        if (!empty($_FILES['ticket_attachment'])) {
            $file_data = $this->handle_file_upload('ticket_attachment');
            if (!is_wp_error($file_data) && $file_data) {
                $attachments[] = $file_data;
            }
        }

        $msg_id = Database::insert('ticket_messages', [
            'ticket_id'   => $ticket_id,
            'user_id'     => $user_id,
            'message'     => $message,
            'attachments' => !empty($attachments) ? wp_json_encode($attachments) : null,
        ]);
        if (!$msg_id) {
            $this->send(false, ['message' => esc_html__('پاسخ ذخیره نشد؛ دوباره تلاش کنید.', 'rsd-finance-panel')]);
        }

        $new_status = current_user_can('manage_options') ? 'waiting' : 'in_progress';
        Database::update('tickets', [
            'status'        => $new_status,
            'last_reply_at' => current_time('mysql'),
        ], ['id' => $ticket_id]);

        do_action('rsd_fp_ticket_replied', $ticket_id, $user_id, [
            'message_id'   => $msg_id,
            'message'      => $message,
            'attachments'  => $attachments,
            'new_status'   => $new_status,
        ]);

        $this->send(true, [
            'message' => esc_html__('پاسخ با موفقیت ارسال شد.', 'rsd-finance-panel'),
        ]);
    }

    /**
     * Admin update ticket status / priority / category / subject
     *
     * @return void
     */
    public function admin_update_ticket()
    {
        $this->verify(false);

        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_tickets')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
        $ticket = Database::get_row('tickets', $ticket_id);
        if (!$ticket) {
            $this->send(false, ['message' => esc_html__('تیکت پیدا نشد.', 'rsd-finance-panel')]);
        }

        $data = [];
        if (!empty($_POST['status']) && is_string($_POST['status'])) {
            $statuses = \RSD\FinancePanel\Modules\Tickets::get_statuses();
            if (isset($statuses[sanitize_key($_POST['status'])])) {
                $data['status'] = sanitize_key($_POST['status']);
            }
        }
        if (!empty($_POST['priority']) && is_string($_POST['priority'])) {
            $priorities = \RSD\FinancePanel\Modules\Tickets::get_priorities();
            if (isset($priorities[sanitize_key($_POST['priority'])])) {
                $data['priority'] = sanitize_key($_POST['priority']);
            }
        }
        if (!empty($_POST['category']) && is_string($_POST['category'])) {
            $cats = \RSD\FinancePanel\Modules\Tickets::get_categories();
            $cat = sanitize_key($_POST['category']);
            if (isset($cats[$cat])) {
                $data['category'] = $cat;
            }
        }
        if (!empty($_POST['subject'])) {
            $data['subject'] = sanitize_text_field($_POST['subject']);
        }

        if (empty($data)) {
            $this->send(false, ['message' => esc_html__('تغییری برای اعمال شدن یافت نشد.', 'rsd-finance-panel')]);
        }

        Database::update('tickets', $data, ['id' => $ticket_id]);

        do_action('rsd_fp_ticket_updated', $ticket_id, $data, get_current_user_id());

        $this->send(true, ['message' => esc_html__('تغییرات با موفقیت اعمال شد.', 'rsd-finance-panel')]);
    }

    /**
     * Admin creates ticket on behalf of a client
     *
     * @return void
     */
    public function admin_create_ticket()
    {
        $this->verify(false);

        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_tickets')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $category = sanitize_key($_POST['category'] ?? 'other');
        $priority = sanitize_key($_POST['priority'] ?? 'normal');

        if (!$user_id || !get_userdata($user_id)) {
            $this->send(false, ['message' => esc_html__('کاربر انتخاب‌شده نامعتبر است.', 'rsd-finance-panel')]);
        }
        if (empty($subject) || empty($message)) {
            $this->send(false, ['message' => esc_html__('موضوع و متن پیام تیکت الزامی هستند.', 'rsd-finance-panel')]);
        }

        $ticket_number = 'TKT-' . date('Ymd') . '-' . str_pad(wp_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $admin_id = get_current_user_id();

        $ticket_id = Database::insert('tickets', [
            'user_id'        => $user_id,
            'ticket_number'  => $ticket_number,
            'subject'        => $subject,
            'category'       => $category,
            'type'           => 'admin_opened',
            'priority'       => $priority,
            'status'         => 'waiting',
            'created_by'     => $admin_id,
            'last_reply_at'  => current_time('mysql'),
        ]);

        $msg_id = false;
        if ($ticket_id) {
            $msg_id = Database::insert('ticket_messages', [
                'ticket_id' => $ticket_id,
                'user_id'   => $admin_id,
                'message'   => $message,
                'is_read'   => 0,
            ]);
            if (!$msg_id) {
                $this->send(false, ['message' => esc_html__('تیکت ثبت شد ولی پیام اولیه ذخیره نگردید.', 'rsd-finance-panel')]);
            }
            do_action('rsd_fp_ticket_created', $ticket_id, [
                'ticket_number' => $ticket_number,
                'subject'       => $subject,
                'category'      => $category,
                'priority'      => $priority,
                'user_id'       => $user_id,
                'message'       => $message,
                'message_id'    => $msg_id,
                'opened_by'     => $admin_id,
            ]);
        }

        $this->send(true, [
            'message'       => esc_html__('تیکت با موفقیت ایجاد شد.', 'rsd-finance-panel'),
            'ticket_id'     => $ticket_id,
            'ticket_number' => $ticket_number,
        ]);
    }

    /**
     * Close ticket
     *
     * @return void
     */
    public function close_ticket()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $ticket_id = (int) ($_POST['ticket_id'] ?? 0);

        $ticket = Database::get_row('tickets', $ticket_id);

        if (!$ticket) {
            $this->send(false, ['message' => esc_html__('تیکت پیدا نشد.', 'rsd-finance-panel')]);
        }

        if (!current_user_can('manage_options') && (int) $ticket->user_id !== $user_id) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        Database::update('tickets', [
            'status'    => 'closed',
            'closed_at' => current_time('mysql'),
        ], ['id' => $ticket_id]);

        do_action('rsd_fp_ticket_closed', $ticket_id, [
            'closed_by' => $user_id,
            'closed_at' => current_time('mysql'),
        ]);

        $this->send(true, ['message' => esc_html__('تیکت بسته شد.', 'rsd-finance-panel')]);
    }

    /**
     * Get ticket messages (for dynamic loading)
     *
     * @return void
     */
    public function get_ticket_messages()
    {
        $this->verify();

        $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
        $user_id = get_current_user_id();

        $ticket = Database::get_row('tickets', $ticket_id);

        if (!$ticket) {
            $this->send(false, ['message' => esc_html__('تیکت پیدا نشد.', 'rsd-finance-panel')]);
        }

        if (!current_user_can('manage_options') && (int) $ticket->user_id !== $user_id) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        global $wpdb;
        $table = Database::table('ticket_messages');
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as user_name, u.user_email 
             FROM {$table} m 
             LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id 
             WHERE m.ticket_id = %d 
             ORDER BY m.created_at ASC",
            $ticket_id
        ));

        // Mark messages as read if they're from the other party
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                if ((int) $msg->user_id !== $user_id && !$msg->is_read) {
                    Database::update('ticket_messages', [
                        'is_read' => 1,
                        'read_at' => current_time('mysql'),
                    ], ['id' => (int) $msg->id]);
                }
            }
        }

        $this->send(true, ['messages' => $messages]);
    }

    /**
     * Submit service request
     *
     * @return void
     */
    public function submit_service_request()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $service_type = sanitize_text_field($_POST['service_type'] ?? '');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (empty($service_type) || empty($title)) {
            $this->send(false, ['message' => esc_html__('نوع خدمت و عنوان درخواست الزامی هستند.', 'rsd-finance-panel')]);
        }

        // Collect all service-specific fields
        $request_data = [];
        foreach ($_POST as $key => $val) {
            if (strpos($key, 'svc_') === 0) {
                $k = sanitize_key(substr($key, 4));
                $request_data[$k] = is_array($val) ? array_map('sanitize_text_field', $val) : sanitize_text_field($val);
            }
        }

        $service_id = Database::insert('services', [
            'user_id'      => $user_id,
            'service_type' => $service_type,
            'title'        => $title,
            'description'  => $description,
            'request_data' => wp_json_encode($request_data),
            'status'       => 'pending',
        ]);

        $this->send(true, [
            'message'    => esc_html__('درخواست خدمت با موفقیت ثبت شد.', 'rsd-finance-panel'),
            'service_id' => $service_id,
        ]);
    }

    /**
     * Get calendar events for fullcalendar or similar
     *
     * @return void
     */
    public function get_calendar_events()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $start = $_POST['start'] ?? null;
        $end = $_POST['end'] ?? null;
        $category = $_POST['category'] ?? null;

        global $wpdb;
        $table = Database::table('calendar');

        $sql = "SELECT * FROM {$table} WHERE user_id = %d AND status != 'rejected'";
        $args = [$user_id];

        if ($start && $end) {
            $sql .= " AND (start_date BETWEEN %s AND %s OR end_date BETWEEN %s AND %s)";
            $args[] = $start;
            $args[] = $end;
            $args[] = $start;
            $args[] = $end;
        }

        if ($category) {
            $sql .= " AND category = %s";
            $args[] = $category;
        }

        $events = $wpdb->get_results($wpdb->prepare($sql, ...$args));

        // Format for calendar
        $formatted = [];
        foreach ($events as $e) {
            $formatted[] = [
                'id'    => (int) $e->id,
                'title' => $e->title,
                'start' => $e->start_date,
                'end'   => $e->end_date ?: null,
                'allDay' => (bool) $e->is_all_day,
                'category' => $e->category,
                'description' => $e->description,
                'event_type' => $e->event_type,
                'status' => $e->status,
                'request_status' => $e->request_status,
            ];
        }

        $this->send(true, ['events' => $formatted]);
    }

    /**
     * Create calendar event (client adds own)
     *
     * @return void
     */
    public function create_calendar_event()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['title'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'other');
        $start_date_raw = trim((string)($_POST['start_date'] ?? ''));
        $end_date_raw = trim((string)($_POST['end_date'] ?? ''));
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $is_all_day = isset($_POST['is_all_day']) ? 1 : 0;

        if (empty($title) || empty($start_date_raw)) {
            $this->send(false, ['message' => esc_html__('عنوان رویداد و تاریخ شروع الزامی هستند.', 'rsd-finance-panel')]);
        }

        // Normalize Jalali/Gregorian date input to Gregorian YYYY-MM-DD
        $start_date = '';
        $end_date = '';
        if ($start_date_raw !== '') {
            $s = $this->normalize_expiry($start_date_raw, false);
            $start_date = $s ? ($s . ' 00:00:00') : '';
        }
        if ($end_date_raw !== '') {
            $e = $this->normalize_expiry($end_date_raw, false);
            $end_date = $e ? ($e . ' 23:59:59') : '';
        }

        $event_id = Database::insert('calendar', [
            'user_id'     => $user_id,
            'title'       => $title,
            'description' => $description,
            'category'    => $category,
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'is_all_day'  => $is_all_day,
            'event_type'  => 'event',
            'status'      => 'pending',
            'created_by'  => $user_id,
        ]);

        $this->send(true, [
            'message'  => esc_html__('رویداد برای تأیید ثبت شد.', 'rsd-finance-panel'),
            'event_id' => $event_id,
        ]);
    }

    /**
     * Delete calendar event
     *
     * @return void
     */
    public function delete_calendar_event()
    {
        $this->verify();

        $event_id = (int) ($_POST['event_id'] ?? 0);
        $user_id = get_current_user_id();

        $event = Database::get_row('calendar', $event_id);

        if (!$event) {
            $this->send(false, ['message' => esc_html__('رویداد پیدا نشد.', 'rsd-finance-panel')]);
        }

        if (!current_user_can('manage_options') && (int) $event->user_id !== $user_id) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        Database::delete('calendar', ['id' => $event_id]);
        $this->send(true, ['message' => esc_html__('رویداد با موفقیت حذف شد.', 'rsd-finance-panel')]);
    }

    /**
     * Update calendar event dates OR document expiry date (for synthetic doc rows)
     *
     * @return void
     */
    public function update_calendar_event()
    {
        $this->verify();

        if (!current_user_can('manage_options')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')], 403);
        }

        $event_id    = (int) ($_POST['event_id'] ?? 0);
        $start_j     = trim(sanitize_text_field($_POST['start_jalali'] ?? ''));
        $end_j       = trim(sanitize_text_field($_POST['end_jalali'] ?? ''));

        if ($event_id === 0) {
            $this->send(false, ['message' => esc_html__('شناسه رویداد نامعتبر است.', 'rsd-finance-panel')]);
        }

        if ($event_id > 0) {
            // Real calendar event: update start_date + end_date
            $row = Database::get_row('calendar', $event_id);
            if (!$row) {
                $this->send(false, ['message' => esc_html__('رویداد پیدا نشد.', 'rsd-finance-panel')]);
            }

            $start_g = $start_j ? rsd_fp_parse_jalali_to_gregorian($start_j) : null;
            $end_g   = $end_j   ? rsd_fp_parse_jalali_to_gregorian($end_j)   : null;
            $start_g = $start_g ? substr($start_g, 0, 10) . ' ' . substr($row->start_date, 11) : null;
            $end_g   = $end_g   ? substr($end_g,   0, 10) . ' ' . substr($row->end_date   ?: $row->start_date, 11) : null;
            if ($start_j && empty($start_g)) {
                $this->send(false, ['message' => esc_html__('فرمت تاریخ شروع شمسی نامعتبر است.', 'rsd-finance-panel')]);
            }
            if ($end_j && empty($end_g)) {
                $this->send(false, ['message' => esc_html__('فرمت تاریخ پایان شمسی نامعتبر است.', 'rsd-finance-panel')]);
            }

            $upd = [];
            if ($start_j !== '') $upd['start_date'] = $start_g;
            if ($end_j   !== '') $upd['end_date']   = $end_g;
            if (empty($upd)) {
                $this->send(true, ['message' => esc_html__('تغییری اعمال نشد.', 'rsd-finance-panel')]);
            }
            $upd['updated_at'] = current_time('mysql');
            Database::update('calendar', $upd, ['id' => $event_id]);
            $this->send(true, ['message' => esc_html__('تاریخ‌های رویداد با موفقیت به‌روز شد.', 'rsd-finance-panel')]);
        } else {
            // Synthetic document row: id = -document_id -> update expiry_date only
            global $wpdb;
            $doc_id  = abs($event_id);
            $table   = $wpdb->prefix . 'rsd_fp_documents';
            $doc     = $wpdb->get_row($wpdb->prepare("SELECT id, expiry_date FROM {$table} WHERE id = %d", $doc_id));
            if (!$doc) {
                $this->send(false, ['message' => esc_html__('مدرک پیدا نشد.', 'rsd-finance-panel')]);
            }

            $exp_g = $start_j ? rsd_fp_parse_jalali_to_gregorian($start_j) : null;
            if ($start_j && empty($exp_g)) {
                $this->send(false, ['message' => esc_html__('فرمت تاریخ انقضا شمسی نامعتبر است.', 'rsd-finance-panel')]);
            }

            $wpdb->update($table, [
                'expiry_date' => $start_j ? $exp_g : null,
                'updated_at'  => current_time('mysql'),
            ], ['id' => $doc_id]);

            $this->send(true, ['message' => esc_html__('تاریخ انقضای مدرک با موفقیت به‌روز شد.', 'rsd-finance-panel')]);
        }
    }

    /**
     * Request a meeting
     *
     * @return void
     */
    public function request_meeting()
    {
        $this->verify();

        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['title'] ?? '');
        $start_date_raw = trim((string)($_POST['start_date'] ?? ''));
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (empty($title) || empty($start_date_raw)) {
            $this->send(false, ['message' => esc_html__('موضوع و تاریخ جلسه الزامی هستند.', 'rsd-finance-panel')]);
        }

        // Normalize Jalali/Gregorian date input to Gregorian YYYY-MM-DD
        $start_date = '';
        if ($start_date_raw !== '') {
            $s = $this->normalize_expiry($start_date_raw, false);
            $start_date = $s ? ($s . ' 00:00:00') : '';
        }

        $event_id = Database::insert('calendar', [
            'user_id'        => $user_id,
            'title'          => $title,
            'description'    => $description,
            'category'       => 'meeting',
            'start_date'     => $start_date,
            'event_type'     => 'meeting_request',
            'status'         => 'pending',
            'request_status' => 'pending',
            'created_by'     => $user_id,
        ]);

        $this->send(true, [
            'message'  => esc_html__('درخواست جلسه برای تأیید ثبت شد.', 'rsd-finance-panel'),
            'event_id' => $event_id,
        ]);
    }

    /**
     * Get invoice details
     *
     * @return void
     */
    public function get_invoice_details()
    {
        $this->verify();

        $invoice_id = (int) ($_POST['invoice_id'] ?? 0);
        $user_id = get_current_user_id();

        $invoice = Database::get_row('invoices', $invoice_id);

        if (!$invoice) {
            $this->send(false, ['message' => esc_html__('فاکتور پیدا نشد.', 'rsd-finance-panel')]);
        }

        if (!current_user_can('manage_options') && (int) $invoice->user_id !== $user_id) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')]);
        }

        $payments = Database::get_rows('payments', ['invoice_id' => $invoice_id], 'payment_date', 'DESC');

        $this->send(true, [
            'invoice'  => $invoice,
            'payments' => $payments,
        ]);
    }

    /**
     * Create a new invoice (admin only)
     *
     * @return void
     */
    public function create_invoice()
    {
        $this->verify(false);

        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_all')) {
            $this->send(false, ['message' => esc_html__('فقط مدیر می‌تواند فاکتور ایجاد کند.', 'rsd-finance-panel')]);
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $subtotal = max(0.0, (float) ($_POST['subtotal'] ?? 0));
        $tax = max(0.0, (float) ($_POST['tax'] ?? 0));
        $discount = max(0.0, (float) ($_POST['discount'] ?? 0));
        $year_raw = trim((string) ($_POST['year'] ?? ''));
        $month = sanitize_text_field($_POST['month'] ?? '');
        $status = sanitize_key($_POST['status'] ?? 'unpaid');
        $paid_amount = max(0.0, (float) ($_POST['paid_amount'] ?? 0));

        // --- Server-side validation for new text-based financial year (4-digit Jalali) ---
        $year = '';
        if ($year_raw !== '') {
            if (!preg_match('/^\d{4}$/', $year_raw)) {
                $this->send(false, ['message' => esc_html__('سال مالی باید ۴ رقم باشد، مثلاً ۱۴۰۴.', 'rsd-finance-panel')]);
            }
            $year_num = (int) $year_raw;
            if ($year_num < 1380 || $year_num > 1500) {
                $this->send(false, ['message' => esc_html__('سال مالی باید بین ۱۳۸۰ تا ۱۵۰۰ باشد.', 'rsd-finance-panel')]);
            }
            $year = $year_raw;
        }

        // --- Invoice date is now REQUIRED (user selects via triple; no server default) ---
        $raw_invoice_date = trim((string) ($_POST['invoice_date'] ?? ''));
        if ($raw_invoice_date === '') {
            $this->send(false, ['message' => esc_html__('تاریخ صدور فاکتور الزامی است.', 'rsd-finance-panel')]);
        }
        $invoice_date = $this->normalize_expiry($raw_invoice_date, false);
        if (!$invoice_date) {
            $this->send(false, ['message' => esc_html__('تاریخ صدور فاکتور نامعتبر است.', 'rsd-finance-panel')]);
        }
        $due_date = $this->normalize_expiry($_POST['due_date'] ?? '', true);

        if ($user_id <= 0 || !get_userdata($user_id)) {
            $this->send(false, ['message' => esc_html__('مشتری انتخاب‌شده نامعتبر است. لطفاً از لیست جستجو انتخاب کنید.', 'rsd-finance-panel')]);
        }
        if ($subtotal <= 0 && $tax <= 0 && $discount <= 0) {
            $this->send(false, ['message' => esc_html__('حداقل مبلغ فاکتور باید بزرگتر از صفر باشد.', 'rsd-finance-panel')]);
        }

        $invoice_id = \RSD\FinancePanel\Modules\Invoices::create_invoice([
            'user_id'      => $user_id,
            'invoice_date' => $invoice_date,
            'due_date'     => $due_date,
            'year'         => $year,
            'month'        => $month,
            'subtotal'     => $subtotal,
            'tax'          => $tax,
            'discount'     => $discount,
            'status'       => $status,
            'paid_amount'  => $paid_amount,
            'description'  => $description,
        ]);

        if (!$invoice_id) {
            $this->send(false, ['message' => esc_html__('خطا در ایجاد فاکتور؛ لطفاً مقادیر را بررسی کنید.', 'rsd-finance-panel')]);
        }

        // If an initial payment was supplied, also add a payments row for record
        if ($paid_amount > 0) {
            \RSD\FinancePanel\Modules\Invoices::add_payment([
                'invoice_id'     => $invoice_id,
                'user_id'        => $user_id,
                'amount'         => $paid_amount,
                'payment_method' => sanitize_key($_POST['payment_method'] ?? 'other'),
                'description'    => esc_html__('پرداخت اولیه هنگام صدور فاکتور', 'rsd-finance-panel'),
            ]);
        }

        $this->send(true, [
            'message'    => esc_html__('فاکتور با موفقیت ایجاد شد.', 'rsd-finance-panel'),
            'invoice_id' => $invoice_id,
            'print_url'  => \RSD\FinancePanel\Modules\Invoices::get_print_url($invoice_id),
        ]);
    }

    /**
     * Update an existing invoice (admin only)
     *
     * @return void
     */
    public function update_invoice()
    {
        $this->verify(false);

        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_all')) {
            $this->send(false, ['message' => esc_html__('فقط مدیر می‌تواند فاکتور را ویرایش کند.', 'rsd-finance-panel')]);
        }

        $invoice_id = (int) ($_POST['invoice_id'] ?? 0);
        $invoice = Database::get_row('invoices', $invoice_id);
        if (!$invoice) {
            $this->send(false, ['message' => esc_html__('فاکتور پیدا نشد.', 'rsd-finance-panel')]);
        }

        $data = [];
        if (isset($_POST['user_id'])) {
            $uid = (int) $_POST['user_id'];
            if ($uid <= 0 || !get_userdata($uid)) {
                $this->send(false, ['message' => esc_html__('مشتری انتخاب‌شده برای ویرایش نامعتبر است.', 'rsd-finance-panel')]);
            }
            $data['user_id'] = $uid;
        }
        if (isset($_POST['description'])) $data['description'] = sanitize_textarea_field($_POST['description'] ?? '');
        if (isset($_POST['subtotal']))    $data['subtotal']    = max(0.0, (float) $_POST['subtotal']);
        if (isset($_POST['tax']))         $data['tax']         = max(0.0, (float) $_POST['tax']);
        if (isset($_POST['discount']))    $data['discount']    = max(0.0, (float) $_POST['discount']);
        if (isset($_POST['year']) && trim((string)$_POST['year']) !== '') {
            $year_raw = trim((string) $_POST['year']);
            if (!preg_match('/^\d{4}$/', $year_raw)) {
                $this->send(false, ['message' => esc_html__('سال مالی باید ۴ رقم باشد، مثلاً ۱۴۰۴.', 'rsd-finance-panel')]);
            }
            $year_num = (int) $year_raw;
            if ($year_num < 1380 || $year_num > 1500) {
                $this->send(false, ['message' => esc_html__('سال مالی باید بین ۱۳۸۰ تا ۱۵۰۰ باشد.', 'rsd-finance-panel')]);
            }
            $data['year'] = $year_raw;
        } elseif (isset($_POST['year'])) {
            $data['year'] = '';
        }
        if (isset($_POST['month']))       $data['month']       = sanitize_text_field($_POST['month'] ?? '');
        if (isset($_POST['status']))      $data['status']      = sanitize_key($_POST['status'] ?? 'unpaid');
        if (isset($_POST['invoice_date']) && trim((string)$_POST['invoice_date']) !== '') {
            $raw = trim((string) $_POST['invoice_date']);
            $norm = $this->normalize_expiry($raw, false);
            if (!$norm) {
                $this->send(false, ['message' => esc_html__('تاریخ صدور فاکتور نامعتبر است.', 'rsd-finance-panel')]);
            }
            $data['invoice_date'] = $norm;
        }
        if (isset($_POST['due_date'])) {
            $data['due_date'] = $this->normalize_expiry($_POST['due_date'], true);
        }

        if (empty($data)) {
            $this->send(false, ['message' => esc_html__('تغییری برای اعمال‌شدن یافت نشد.', 'rsd-finance-panel')]);
        }

        $updated = \RSD\FinancePanel\Modules\Invoices::update_invoice($invoice_id, $data);
        if (!$updated) {
            $this->send(false, ['message' => esc_html__('به‌روزرسانی فاکتور انجام نشد.', 'rsd-finance-panel')]);
        }

        $this->send(true, [
            'message'    => esc_html__('فاکتور با موفقیت به‌روزرسانی شد.', 'rsd-finance-panel'),
            'invoice_id' => $invoice_id,
        ]);
    }

    /**
     * Delete an existing invoice (admin only)
     *
     * @return void
     */
    public function delete_invoice()
    {
        $this->verify(false);

        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_all')) {
            $this->send(false, ['message' => esc_html__('فقط مدیر می‌تواند فاکتور را حذف کند.', 'rsd-finance-panel')]);
        }

        $invoice_id = (int) ($_POST['invoice_id'] ?? 0);
        if ($invoice_id <= 0) {
            $this->send(false, ['message' => esc_html__('فاکتور نامعتبر است.', 'rsd-finance-panel')]);
        }

        $deleted = \RSD\FinancePanel\Modules\Invoices::delete_invoice($invoice_id);
        if (!$deleted) {
            $this->send(false, ['message' => esc_html__('حذف فاکتور انجام نشد.', 'rsd-finance-panel')]);
        }

        $this->send(true, [
            'message'    => esc_html__('فاکتور با موفقیت حذف شد.', 'rsd-finance-panel'),
            'invoice_id' => $invoice_id,
        ]);
    }

    /**
     * Add a payment record to an invoice (admin only)
     *
     * @return void
     */
    public function add_invoice_payment()
    {
        $this->verify(false);

        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_all')) {
            $this->send(false, ['message' => esc_html__('فقط مدیر می‌تواند پرداخت ثبت کند.', 'rsd-finance-panel')]);
        }

        $invoice_id = (int) ($_POST['invoice_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $method = sanitize_key($_POST['payment_method'] ?? 'other');
        $transaction_id = sanitize_text_field($_POST['transaction_id'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        $payment_date_raw = trim((string)($_POST['payment_date'] ?? ''));
        $payment_date = '';
        if ($payment_date_raw !== '') {
            // Normalize a Jalali or Gregorian date + optional time
            if (strpos($payment_date_raw, ' ') !== false) {
                [$d, $t] = array_pad(explode(' ', $payment_date_raw, 2), 2, '');
                $greg = $this->normalize_expiry($d, false);
                $payment_date = $greg . ' ' . ($t ?: current_time('H:i:s'));
            } else {
                $greg = $this->normalize_expiry($payment_date_raw, false);
                $payment_date = $greg . ' ' . current_time('H:i:s');
            }
        } else {
            $payment_date = current_time('mysql');
        }

        if ($invoice_id <= 0) {
            $this->send(false, ['message' => esc_html__('فاکتور نامعتبر است.', 'rsd-finance-panel')]);
        }
        if ($amount <= 0) {
            $this->send(false, ['message' => esc_html__('مبلغ پرداخت باید بزرگتر از صفر باشد.', 'rsd-finance-panel')]);
        }

        $payment_id = \RSD\FinancePanel\Modules\Invoices::add_payment([
            'invoice_id'     => $invoice_id,
            'amount'         => $amount,
            'payment_method' => $method,
            'transaction_id' => $transaction_id,
            'description'    => $description,
            'payment_date'   => $payment_date,
        ]);

        if (!$payment_id) {
            $this->send(false, ['message' => esc_html__('ثبت پرداخت انجام نشد.', 'rsd-finance-panel')]);
        }

        $this->send(true, [
            'message'     => esc_html__('پرداخت با موفقیت ثبت شد.', 'rsd-finance-panel'),
            'payment_id'  => $payment_id,
            'invoice_id'  => $invoice_id,
        ]);
    }

    /**
     * Save company info
     *
     * @return void
     */
    public function save_company_info()
    {
        $this->verify();

        $user_id = get_current_user_id();

        if (current_user_can('manage_options')) {
            $target_user = (int) ($_POST['user_id'] ?? $user_id);
        } else {
            $target_user = $user_id;
        }

        $fields = [
            'company_name',
            'brand_name',
            'address',
            'registration_number',
            'tax_id',
            'vat_number',
            'ceo_name',
            'shareholders',
            'phone',
            'email',
            'website',
            'bank_name',
            'bank_account',
            'financial_contact_name',
            'financial_contact_phone',
            'financial_contact_email',
        ];

        $data = [];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $data[$f] = is_array($_POST[$f]) ? wp_json_encode(array_map('sanitize_text_field', $_POST[$f])) : sanitize_text_field($_POST[$f]);
            }
        }

        global $wpdb;
        $table = Database::table('company_info');
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d", $target_user));

        if ($existing) {
            Database::update('company_info', $data, ['id' => (int) $existing]);
        } else {
            $data['user_id'] = $target_user;
            Database::insert('company_info', $data);
        }

        $this->send(true, ['message' => esc_html__('اطلاعات شرکت با موفقیت ذخیره شد.', 'rsd-finance-panel')]);
    }

    /**
     * Submit company info changes for admin review
     *
     * @return void
     */
    public function submit_company_changes()
    {
        $this->verify();

        $user_id = get_current_user_id();

        $fields = [
            'company_name',
            'brand_name',
            'address',
            'registration_number',
            'tax_id',
            'vat_number',
            'ceo_name',
            'shareholders',
            'phone',
            'email',
            'website',
            'bank_name',
            'bank_account',
            'financial_contact_name',
            'financial_contact_phone',
            'financial_contact_email',
        ];

        $changes = [];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $changes[$f] = is_array($_POST[$f]) ? array_map('sanitize_text_field', $_POST[$f]) : sanitize_text_field($_POST[$f]);
            }
        }

        global $wpdb;
        $table = Database::table('company_info');
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d", $user_id));

        if ($existing) {
            Database::update('company_info', [
                'pending_changes' => wp_json_encode($changes),
            ], ['id' => (int)$existing]);
        } else {
            Database::insert('company_info', [
                'user_id'         => $user_id,
                'pending_changes' => wp_json_encode($changes),
            ]);
        }

        $this->send(true, [
            'message' => esc_html__('تغییرات شما برای تأیید به شرکت ارسال شد.', 'rsd-finance-panel'),
        ]);
    }

    /**
     * Admin: Approve pending company info changes
     * (copy pending_changes JSON into real DB columns and clear pending)
     *
     * @return void
     */
    public function approve_company_changes()
    {
        $this->verify();

        if (!current_user_can('manage_options')) {
            $this->send(false, ['message' => esc_html__('شما دسترسی مدیریت را ندارید.', 'rsd-finance-panel')]);
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            $this->send(false, ['message' => esc_html__('کاربر نامعتبر است.', 'rsd-finance-panel')]);
        }

        $fields = [
            'company_name',
            'brand_name',
            'address',
            'registration_number',
            'tax_id',
            'vat_number',
            'ceo_name',
            'shareholders',
            'phone',
            'email',
            'website',
            'bank_name',
            'bank_account',
            'financial_contact_name',
            'financial_contact_phone',
            'financial_contact_email',
        ];

        global $wpdb;
        $table = Database::table('company_info');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id));

        if (!$row) {
            $this->send(false, ['message' => esc_html__('اطلاعات شرکت برای این کاربر پیدا نشد.', 'rsd-finance-panel')]);
        }

        $pending = json_decode($row->pending_changes ?? 'null', true);
        if (!is_array($pending) || empty($pending)) {
            $this->send(false, ['message' => esc_html__('تغییری در انتظار تأیید وجود ندارد.', 'rsd-finance-panel')]);
        }

        $update = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $pending)) {
                $v = $pending[$f];
                $update[$f] = is_array($v)
                    ? wp_json_encode(array_map('sanitize_text_field', $v))
                    : sanitize_text_field((string) $v);
            }
        }
        $update['pending_changes'] = null;

        Database::update('company_info', $update, ['user_id' => $user_id]);

        $this->send(true, [
            'message' => esc_html__('تغییرات اطلاعات شرکت با تأیید مدیر ثبت شد.', 'rsd-finance-panel'),
        ]);
    }

    /**
     * Admin: Reject / discard pending company info changes (clear pending_changes without applying)
     *
     * @return void
     */
    public function reject_company_changes()
    {
        $this->verify();

        if (!current_user_can('manage_options')) {
            $this->send(false, ['message' => esc_html__('شما دسترسی مدیریت را ندارید.', 'rsd-finance-panel')]);
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            $this->send(false, ['message' => esc_html__('کاربر نامعتبر است.', 'rsd-finance-panel')]);
        }

        global $wpdb;
        $table = Database::table('company_info');
        $row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE user_id = %d", $user_id));

        if (!$row) {
            $this->send(false, ['message' => esc_html__('اطلاعات شرکت برای این کاربر پیدا نشد.', 'rsd-finance-panel')]);
        }

        Database::update('company_info', [
            'pending_changes' => null,
        ], ['user_id' => $user_id]);

        $this->send(true, [
            'message' => esc_html__('درخواست تغییرات رد شد و وضعیت به حالت قبل بازگشت.', 'rsd-finance-panel'),
        ]);
    }

    /**
     * Dismiss a single notification
     *
     * @return void
     */
    public function dismiss_notification()
    {
        $this->verify();

        $notif_id = (int) ($_POST['notif_id'] ?? 0);
        $user_id = get_current_user_id();

        $notif = Database::get_row('notifications', $notif_id);

        if (!$notif || (int) $notif->user_id !== $user_id) {
            $this->send(false, ['message' => esc_html__('اعلان پیدا نشد.', 'rsd-finance-panel')]);
        }

        Database::update('notifications', [
            'is_read' => 1,
            'read_at' => current_time('mysql'),
        ], ['id' => $notif_id]);

        $this->send(true);
    }

    /**
     * Mark all notifications as read
     *
     * @return void
     */
    public function read_notifications()
    {
        $this->verify();

        $user_id = get_current_user_id();
        global $wpdb;
        $table = Database::table('notifications');

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET is_read = 1, read_at = %s WHERE user_id = %d AND is_read = 0",
            current_time('mysql'),
            $user_id
        ));

        $this->send(true);
    }

    /**
     * Handle file upload using WordPress upload APIs
     *
     * @param string $field_name
     * @return array|\WP_Error
     */
    private function handle_file_upload($field_name)
    {
        if (empty($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
            return [];
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $uploaded = wp_handle_upload($_FILES[$field_name], ['test_form' => false]);

        if (isset($uploaded['error'])) {
            return new \WP_Error('upload_error', $uploaded['error']);
        }

        return $uploaded;
    }

    /**
     * Convert $_FILES array to normalized format
     *
     * @param array $files_array
     * @return array
     */
    private function normalize_file_array($files_array)
    {
        $result = [];

        if (!is_array($files_array['name'])) {
            return [$files_array];
        }

        $count = count($files_array['name']);
        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'name'     => $files_array['name'][$i],
                'type'     => $files_array['type'][$i],
                'tmp_name' => $files_array['tmp_name'][$i],
                'error'    => $files_array['error'][$i],
                'size'     => $files_array['size'][$i],
            ];
        }

        return $result;
    }

    /**
     * Admin: Create a new report for a client
     *
     * @return void
     */
    public function save_report()
    {
        $this->verify(false);
        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_reports')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')], 403);
        }

        $user_id       = (int) ($_POST['user_id'] ?? 0);
        $title         = sanitize_text_field($_POST['title'] ?? '');
        $report_type   = sanitize_text_field($_POST['report_type'] ?? '');
        $year          = sanitize_text_field($_POST['year'] ?? '');
        $month         = sanitize_text_field($_POST['month'] ?? '');
        $period        = sanitize_text_field($_POST['period'] ?? '');
        $description   = sanitize_textarea_field($_POST['description'] ?? '');

        if ($user_id <= 0) {
            $this->send(false, ['message' => esc_html__('انتخاب مشتری الزامی است.', 'rsd-finance-panel')]);
        }
        if (empty($title)) {
            $this->send(false, ['message' => esc_html__('عنوان گزارش الزامی است.', 'rsd-finance-panel')]);
        }

        $data = [
            'user_id'     => $user_id,
            'title'       => $title,
            'report_type' => $report_type,
            'year'        => $year,
            'month'       => $month,
            'period'      => $period,
            'description' => $description,
            'created_by'  => get_current_user_id(),
        ];

        $upload = $this->handle_file_upload('report_file');
        if (is_wp_error($upload)) {
            $this->send(false, ['message' => $upload->get_error_message()]);
        }
        if (!empty($upload['url'])) {
            $data['file_path'] = esc_url_raw($upload['url']);
            $data['file_name'] = sanitize_text_field(basename($upload['file'] ?? ($_FILES['report_file']['name'] ?? '')));
        }

        $report_id = Database::insert('reports', $data);
        if (!$report_id) {
            $this->send(false, ['message' => esc_html__('خطا در ذخیره گزارش.', 'rsd-finance-panel')]);
        }

        $this->send(true, [
            'message'   => esc_html__('گزارش با موفقیت ثبت شد.', 'rsd-finance-panel'),
            'report_id' => (int) $report_id,
        ]);
    }

    /**
     * Admin: Update an existing report
     *
     * @return void
     */
    public function update_report()
    {
        $this->verify(false);
        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_reports')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')], 403);
        }

        $report_id = (int) ($_POST['report_id'] ?? 0);
        $report    = $report_id > 0 ? Database::get_row('reports', $report_id) : null;

        if (!$report) {
            $this->send(false, ['message' => esc_html__('گزارش پیدا نشد.', 'rsd-finance-panel')]);
        }

        $user_id       = (int) ($_POST['user_id'] ?? $report->user_id);
        $title         = sanitize_text_field($_POST['title'] ?? $report->title);
        $report_type   = sanitize_text_field($_POST['report_type'] ?? $report->report_type);
        $year          = sanitize_text_field($_POST['year'] ?? $report->year);
        $month         = sanitize_text_field($_POST['month'] ?? $report->month);
        $period        = sanitize_text_field($_POST['period'] ?? $report->period);
        $description   = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : $report->description;

        if ($user_id <= 0) {
            $this->send(false, ['message' => esc_html__('انتخاب مشتری الزامی است.', 'rsd-finance-panel')]);
        }
        if (empty($title)) {
            $this->send(false, ['message' => esc_html__('عنوان گزارش الزامی است.', 'rsd-finance-panel')]);
        }

        $data = [
            'user_id'     => $user_id,
            'title'       => $title,
            'report_type' => $report_type,
            'year'        => $year,
            'month'       => $month,
            'period'      => $period,
            'description' => $description,
        ];

        $upload = $this->handle_file_upload('report_file');
        if (is_wp_error($upload)) {
            $this->send(false, ['message' => $upload->get_error_message()]);
        }
        if (!empty($upload['url'])) {
            $data['file_path'] = esc_url_raw($upload['url']);
            $data['file_name'] = sanitize_text_field(basename($upload['file'] ?? ($_FILES['report_file']['name'] ?? '')));
        } elseif (isset($_POST['remove_file']) && $_POST['remove_file'] === '1') {
            $data['file_path'] = null;
            $data['file_name'] = null;
        }

        Database::update('reports', $data, ['id' => $report_id]);

        $this->send(true, ['message' => esc_html__('گزارش با موفقیت به‌روزرسانی شد.', 'rsd-finance-panel')]);
    }

    /**
     * Admin: Delete a report
     *
     * @return void
     */
    public function delete_report()
    {
        $this->verify(false);
        if (!current_user_can('manage_options') && !current_user_can('rsd_fp_manage_reports')) {
            $this->send(false, ['message' => esc_html__('دسترسی مجاز نیست.', 'rsd-finance-panel')], 403);
        }

        $report_id = (int) ($_POST['report_id'] ?? 0);
        $report    = $report_id > 0 ? Database::get_row('reports', $report_id) : null;

        if (!$report) {
            $this->send(false, ['message' => esc_html__('گزارش پیدا نشد.', 'rsd-finance-panel')]);
        }

        Database::delete('reports', ['id' => $report_id]);

        $this->send(true, ['message' => esc_html__('گزارش با موفقیت حذف شد.', 'rsd-finance-panel')]);
    }

    // ============================================================
    //  AUTH: guest / nopriv endpoints (register, login, OTP, lost, reset)
    // ============================================================

    /**
     * Verify nonce only (no login requirement). Used for auth endpoints.
     *
     * @return void
     */
    private function verify_nonce_only()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rsd_fp_nonce')) {
            $this->send(false, ['message' => esc_html__('توکن امنیتی نامعتبر است.', 'rsd-finance-panel')], 403);
        }
    }

    /**
     * Helper: get an Auth instance (either from plugin container, or freshly created).
     *
     * @return \RSD\FinancePanel\Modules\Auth|null
     */
    private function auth()
    {
        $candidate = null;

        $rsd_fp_instance = $GLOBALS['rsd_fp_instance'] ?? null;
        if (is_object($rsd_fp_instance) && method_exists($rsd_fp_instance, 'get_component')) {
            $candidate = $rsd_fp_instance->get_component('auth');
        }

        if (!$candidate instanceof \RSD\FinancePanel\Modules\Auth) {
            $candidate = new \RSD\FinancePanel\Modules\Auth();
        }
        return $candidate;
    }

    /**
     * Guest: Register a new client account.
     * POST fields: phone (required), email (optional), password, password_confirm, otp (optional)
     *
     * @return void
     */
    public function auth_register()
    {
        $this->verify_nonce_only();

        $auth = $this->auth();
        if (!$auth) {
            $this->send(false, ['message' => esc_html__('سیستم احراز هویت بارگذاری نشده است.', 'rsd-finance-panel')], 500);
        }

        $phone             = isset($_POST['phone'])             ? trim((string)$_POST['phone'])             : '';
        $email             = isset($_POST['email'])             ? trim((string)$_POST['email'])             : '';
        $password          = isset($_POST['password'])          ? (string)$_POST['password']                : '';
        $password_confirm  = isset($_POST['password_confirm'])  ? (string)$_POST['password_confirm']        : '';
        $otp               = isset($_POST['otp'])               ? trim((string)$_POST['otp'])                : '';

        $result = $auth->handle_register($phone, $email, $password, $password_confirm, $otp);

        if (is_wp_error($result)) {
            $this->send(false, ['message' => $result->get_error_message()], 400);
        }

        $user_id = (int)$result;
        $remember = true;
        $auth->auto_login_user($user_id, $remember);

        $redirect = Plugin::get_page_url('dashboard');
        if (!empty($_POST['redirect_to'])) {
            $candidate = esc_url_raw(trim((string)$_POST['redirect_to']));
            if ($candidate !== '') {
                $redirect = $candidate;
            }
        }

        $this->send(true, [
            'message'     => esc_html__('حساب کاربری شما با موفقیت ساخته شد و وارد شدید.', 'rsd-finance-panel'),
            'user_id'     => $user_id,
            'redirect_to' => $redirect,
        ]);
    }

    /**
     * Guest: Login with identifier (phone/email/username) + password (+ OTP if enabled).
     * POST fields: identifier, password, remember (0/1), otp (optional)
     *
     * @return void
     */
    public function auth_login()
    {
        $this->verify_nonce_only();

        $auth = $this->auth();
        if (!$auth) {
            $this->send(false, ['message' => esc_html__('سیستم احراز هویت بارگذاری نشده است.', 'rsd-finance-panel')], 500);
        }

        $identifier = isset($_POST['identifier']) ? trim((string)$_POST['identifier']) : '';
        $password   = isset($_POST['password'])   ? (string)$_POST['password']        : '';
        $remember   = !empty($_POST['remember'])  ? true : false;
        $otp        = isset($_POST['otp'])        ? trim((string)$_POST['otp'])        : '';

        $user = $auth->handle_login($identifier, $password, $remember, $otp);

        if (is_wp_error($user)) {
            $this->send(false, ['message' => $user->get_error_message()], 400);
        }

        $redirect = Plugin::get_page_url('dashboard');
        if (!empty($_POST['redirect_to'])) {
            $candidate = esc_url_raw(trim((string)$_POST['redirect_to']));
            if ($candidate !== '') {
                $redirect = $candidate;
            }
        }

        $this->send(true, [
            'message'     => esc_html__('با موفقیت وارد شدید. در حال انتقال به پنل ...', 'rsd-finance-panel'),
            'user_id'     => (int)$user->ID,
            'redirect_to' => $redirect,
        ]);
    }

    /**
     * Guest: Request password-reset email.
     * POST field: identifier (phone/email/username)
     *
     * @return void
     */
    public function auth_lostpassword()
    {
        $this->verify_nonce_only();

        $auth = $this->auth();
        if (!$auth) {
            $this->send(false, ['message' => esc_html__('سیستم احراز هویت بارگذاری نشده است.', 'rsd-finance-panel')], 500);
        }

        $identifier = isset($_POST['identifier']) ? trim((string)$_POST['identifier']) : '';
        $result = $auth->handle_lostpassword($identifier);

        if (is_wp_error($result)) {
            $this->send(false, ['message' => $result->get_error_message()], 400);
        }

        $this->send(true, [
            'message' => esc_html__('در صورت معتبر بودن ایمیل ثبت\u200cشده، لینک بازنشانی رمز عبور برای شما ارسال شد.', 'rsd-finance-panel'),
        ]);
    }

    /**
     * Guest or logged-in: Complete the password-reset step with key+login.
     * POST fields: key, login, new_password, new_password_confirm
     *
     * @return void
     */
    public function auth_reset_password()
    {
        $this->verify_nonce_only();

        $auth = $this->auth();
        if (!$auth) {
            $this->send(false, ['message' => esc_html__('سیستم احراز هویت بارگذاری نشده است.', 'rsd-finance-panel')], 500);
        }

        $key              = isset($_POST['key'])                  ? trim((string)$_POST['key'])                  : '';
        $login            = isset($_POST['login'])                ? trim((string)$_POST['login'])                : '';
        $new_password     = isset($_POST['new_password'])         ? (string)$_POST['new_password']               : '';
        $new_password_c   = isset($_POST['new_password_confirm']) ? (string)$_POST['new_password_confirm']      : '';

        $result = $auth->handle_resetpass($key, $login, $new_password, $new_password_c);

        if (is_wp_error($result)) {
            $this->send(false, ['message' => $result->get_error_message()], 400);
        }

        $auth_url = Plugin::get_page_url('auth');
        if (!$auth_url) {
            $auth_url = home_url('/');
        }
        $auth_url = add_query_arg('passwordreset', 'true', $auth_url);

        $this->send(true, [
            'message'     => esc_html__('رمز عبور شما با موفقیت تغییر کرد. اکنون می‌توانید با رمز جدید وارد شوید.', 'rsd-finance-panel'),
            'redirect_to' => $auth_url,
        ]);
    }

    /**
     * Guest: Send a 6-digit OTP to the provided mobile via configured SMS gateway.
     * POST fields: phone, context ('register' | 'login')
     *
     * @return void
     */
    public function auth_send_otp()
    {
        $this->verify_nonce_only();

        $auth = $this->auth();
        if (!$auth) {
            $this->send(false, ['message' => esc_html__('سیستم احراز هویت بارگذاری نشده است.', 'rsd-finance-panel')], 500);
        }

        if (!$auth->is_otp_enabled()) {
            $this->send(false, ['message' => esc_html__('احراز هویت دو مرحله\u200cای در حال حاضر غیرفعال است.', 'rsd-finance-panel')], 400);
        }

        $phone   = isset($_POST['phone'])   ? trim((string)$_POST['phone'])   : '';
        $context = isset($_POST['context']) ? trim((string)$_POST['context']) : 'register';
        if (!in_array($context, ['register', 'login'], true)) {
            $context = 'register';
        }

        $result = $auth->send_otp($phone, $context);

        if (is_wp_error($result)) {
            $this->send(false, ['message' => $result->get_error_message()], 400);
        }

        $this->send(true, [
            'message' => sprintf(
                esc_html__('کد تأیید ۶ رقمی به شماره %s ارسال شد. لطفاً وارد کنید.', 'rsd-finance-panel'),
                esc_html(\RSD\FinancePanel\SMS\FarazSMS::normalise_phone($phone))
            ),
            'otp_ttl_seconds' => \RSD\FinancePanel\Modules\Auth::OTP_TTL,
        ]);
    }

    /**
     * Guest: Verify an OTP code for a phone number (used by UI to step forward
     * to the password form before actually submitting register/login).
     * POST fields: phone, code
     *
     * @return void
     */
    public function auth_verify_otp()
    {
        $this->verify_nonce_only();

        $auth = $this->auth();
        if (!$auth) {
            $this->send(false, ['message' => esc_html__('سیستم احراز هویت بارگذاری نشده است.', 'rsd-finance-panel')], 500);
        }

        $phone = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
        $code  = isset($_POST['code'])  ? trim((string)$_POST['code'])  : '';

        $ok = $auth->verify_otp($phone, $code);
        if (!$ok) {
            $this->send(false, ['message' => esc_html__('کد تأیید وارد شده معتبر نیست یا منقضی شده است.', 'rsd-finance-panel')], 400);
        }

        $this->send(true, [
            'message' => esc_html__('کد تأیید با موفقیت تأیید شد.', 'rsd-finance-panel'),
        ]);
    }
}
