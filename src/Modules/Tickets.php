<?php

/**
 * Tickets module class
 *
 * Business logic for tickets & message management, plus notifications
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Database;

class Tickets
{
    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        add_action('rsd_fp_ticket_created', [$this, 'send_new_ticket_notification'], 10, 2);
        add_action('rsd_fp_ticket_replied', [$this, 'send_reply_notification'], 10, 3);
        add_action('rsd_fp_ticket_updated', [$this, 'send_status_notification'], 10, 3);
        add_action('rsd_fp_ticket_closed',  [$this, 'send_closed_notification'], 10, 2);
    }

    /**
     * List of user IDs that have manage_options capability (administrators).
     *
     * @return int[]
     */
    private static function get_admin_ids()
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }
        $users = get_users([
            'capability__in' => ['manage_options'],
            'fields'         => 'ID',
            'number'         => 100,
        ]);
        $cache = array_map('intval', (array) $users);
        return $cache;
    }

    /**
     * Insert a row into the notifications table.
     *
     * Uses raw wpdb->insert because Database::insert applies current_user caps
     * context we don't need; we use sanitised values and explicit allow list.
     *
     * @param int    $user_id        recipient of the notification
     * @param string $type           ticket_created / ticket_replied / ticket_updated / ticket_closed
     * @param string $title          short one-line title
     * @param string $message        optional longer body
     * @param int    $reference_id   ticket id
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
            'reference_type' => 'ticket',
            'reference_id'   => (int) $reference_id,
            'is_read'        => 0,
            'created_at'     => current_time('mysql'),
        ], ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s']);
        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * Build permalink for a ticket on the client portal.
     *
     * @param int $ticket_id
     * @return string
     */
    private static function client_ticket_url($ticket_id)
    {
        if (function_exists('rsd_fp_page_url')) {
            return add_query_arg(['view' => 'single', 'id' => (int) $ticket_id], rsd_fp_page_url('tickets'));
        }
        return add_query_arg(['view' => 'single', 'id' => (int) $ticket_id], home_url('/finance-tickets/'));
    }

    /**
     * Build admin URL for a single ticket.
     *
     * @param int $ticket_id
     * @return string
     */
    private static function admin_ticket_url($ticket_id)
    {
        return admin_url('admin.php?page=rsd-fp-tickets&view=single&id=' . (int) $ticket_id);
    }

    /**
     * Get ticket categories from settings (with English defaults)
     *
     * @return array
     */
    public static function get_categories()
    {
        $cats = \RSD\FinancePanel\Core\Plugin::get_setting('ticket_categories', []);
        if (empty($cats)) {
            $cats = [
                'accounting'   => esc_html__('Accounting', 'rsd-finance-panel'),
                'tax'          => esc_html__('Tax', 'rsd-finance-panel'),
                'payroll'      => esc_html__('Payroll', 'rsd-finance-panel'),
                'employees'    => esc_html__('Employees', 'rsd-finance-panel'),
                'invoice'      => esc_html__('Invoice', 'rsd-finance-panel'),
                'documents'    => esc_html__('Documents', 'rsd-finance-panel'),
                'registration' => esc_html__('Company Registration', 'rsd-finance-panel'),
                'other'        => esc_html__('Other', 'rsd-finance-panel'),
            ];
        }
        return $cats;
    }

    /**
     * Get ticket types (Question or Issue/Ticket)
     *
     * @return array
     */
    public static function get_types()
    {
        return [
            'question' => esc_html__('Ask a Question', 'rsd-finance-panel'),
            'issue'    => esc_html__('Submit a Ticket or Issue', 'rsd-finance-panel'),
        ];
    }

    /**
     * Get issue sub-types
     *
     * @return array
     */
    public static function get_issue_types()
    {
        return [
            'invoice_issue'    => esc_html__('Invoice Issue', 'rsd-finance-panel'),
            'report_issue'     => esc_html__('Report Issue', 'rsd-finance-panel'),
            'wrong_document'   => esc_html__('Wrong Document', 'rsd-finance-panel'),
            'correction_req'   => esc_html__('Correction Request', 'rsd-finance-panel'),
            'payroll_issue'    => esc_html__('Payroll Issue', 'rsd-finance-panel'),
            'other_issue'      => esc_html__('Other', 'rsd-finance-panel'),
        ];
    }

    /**
     * Get ticket statuses
     *
     * @return array
     */
    public static function get_statuses()
    {
        return [
            'new'         => esc_html__('New', 'rsd-finance-panel'),
            'in_progress' => esc_html__('Under Review', 'rsd-finance-panel'),
            'waiting'     => esc_html__('Waiting for Client', 'rsd-finance-panel'),
            'completed'   => esc_html__('Completed', 'rsd-finance-panel'),
            'closed'      => esc_html__('Closed', 'rsd-finance-panel'),
        ];
    }

    /**
     * Get priorities
     *
     * @return array
     */
    public static function get_priorities()
    {
        return [
            'low'      => esc_html__('Low', 'rsd-finance-panel'),
            'normal'   => esc_html__('Normal', 'rsd-finance-panel'),
            'high'     => esc_html__('High', 'rsd-finance-panel'),
            'urgent'   => esc_html__('Urgent', 'rsd-finance-panel'),
        ];
    }

    /**
     * Count unread messages for a user
     *
     * @param int $user_id
     * @return int
     */
    public static function count_unread($user_id)
    {
        global $wpdb;
        $tickets_table = Database::table('tickets');
        $messages_table = Database::table('ticket_messages');

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(m.id) 
             FROM {$messages_table} m 
             INNER JOIN {$tickets_table} t ON t.id = m.ticket_id 
             WHERE t.user_id = %d AND m.user_id != %d AND m.is_read = 0",
            $user_id,
            $user_id
        ));
    }

    /**
     * Send admin notification on new ticket (email + notification rows for every admin,
     * plus for the client if opened by admin).
     *
     * @param int   $ticket_id
     * @param array $data
     * @return void
     */
    public function send_new_ticket_notification($ticket_id, $data)
    {
        $ticket = Database::get_row('tickets', $ticket_id);
        if (!$ticket) return;

        $admin_email = get_option('admin_email');
        $admin_url   = self::admin_ticket_url($ticket_id);

        $subject = sprintf(esc_html__('New Ticket: %s', 'rsd-finance-panel'), $ticket->ticket_number);
        $client_user = get_user_by('id', (int) $ticket->user_id);
        $client_name = $client_user ? sprintf('%s (%s)', $client_user->display_name, $client_user->user_email) : '#' . $ticket->user_id;

        $body  = "A new ticket has been submitted.\n\n";
        $body .= "Number: " . $ticket->ticket_number . "\n";
        $body .= "Subject: " . $ticket->subject . "\n";
        $body .= "Submitted by: " . $client_name . "\n";
        if (!empty($data['category']) && ($cats = self::get_categories()) && isset($cats[$data['category']])) {
            $body .= "Category: " . $cats[$data['category']] . "\n";
        }
        if (!empty($data['priority']) && ($prios = self::get_priorities()) && isset($prios[$data['priority']])) {
            $body .= "Priority: " . $prios[$data['priority']] . "\n";
        }
        $body .= "\nView in admin panel: " . $admin_url . "\n";

        if ($admin_email) {
            @wp_mail($admin_email, $subject, $body);
        }

        // Notifications DB row: notify admins when opened by client OR when opened by admin too
        $opened_by_admin = !empty($data['opened_by']) || user_can((int)($data['opened_by'] ?? 0), 'manage_options');
        $title_for_admin = sprintf('New ticket #%s from %s', $ticket->ticket_number, $client_user ? $client_user->display_name : 'Client');
        $message_for_admin = sprintf('Subject: %s', $ticket->subject);
        foreach (self::get_admin_ids() as $aid) {
            self::add_notification($aid, 'ticket_created', $title_for_admin, $message_for_admin, (int)$ticket_id);
        }

        // Notify client if the ticket was opened by admin on their behalf
        if ($opened_by_admin) {
            $title_for_client = sprintf('A new ticket has been created for you #%s', $ticket->ticket_number);
            $msg   = sprintf('The team admin created the ticket "%s" for you. Click the notification to view it.', $ticket->subject);
            self::add_notification((int) $ticket->user_id, 'ticket_created', $title_for_client, $msg, (int)$ticket_id);
            if ($client_user) {
                $client_body  = "A new ticket has been created for you by the admin.\n\n";
                $client_body .= "Number: " . $ticket->ticket_number . "\n";
                $client_body .= "Subject: " . $ticket->subject . "\n\n";
                $client_body .= "To view it, visit your portal: " . self::client_ticket_url($ticket_id) . "\n";
                @wp_mail($client_user->user_email, sprintf('New Ticket: %s', $ticket->ticket_number), $client_body);
            }
        }
    }

    /**
     * Send notification when ticket is replied to.
     *
     * - Admin replied → notify client (DB notification row + email if user exists)
     * - Client replied → notify all admins (DB notification rows + admin email)
     *
     * @param int   $ticket_id
     * @param int   $reply_user_id
     * @param array $data
     * @return void
     */
    public function send_reply_notification($ticket_id, $reply_user_id, $data)
    {
        $ticket = Database::get_row('tickets', $ticket_id);
        if (!$ticket) return;

        $reply_from_admin = user_can((int) $reply_user_id, 'manage_options');
        $snippet = '';
        if (!empty($data['message'])) {
            $snippet = function_exists('mb_substr') ? mb_substr(strip_tags($data['message']), 0, 120, 'UTF-8') : substr(strip_tags($data['message']), 0, 120);
        }

        if ($reply_from_admin) {
            // Notify the ticket owner (client)
            $client_id = (int) $ticket->user_id;
            $title = sprintf('New reply to ticket #%s', $ticket->ticket_number);
            $msg   = sprintf('The admin has replied to the ticket "%s".', $ticket->subject);
            self::add_notification($client_id, 'ticket_replied', $title, $msg, (int)$ticket_id);
            $client = get_user_by('id', $client_id);
            if ($client) {
                $body  = "A new reply has been posted to your ticket.\n\n";
                $body .= "Ticket Number: " . $ticket->ticket_number . "\n";
                $body .= "Subject: " . $ticket->subject . "\n";
                if ($snippet) {
                    $body .= "Reply snippet:\n" . $snippet . (mb_strlen(strip_tags($data['message'] ?? '')) > 120 ? '…' : '') . "\n";
                }
                $body .= "\nView in portal: " . self::client_ticket_url($ticket_id) . "\n";
                @wp_mail($client->user_email, sprintf('Ticket Reply: %s', $ticket->ticket_number), $body);
            }
        } else {
            // Notify all administrators
            $replier = get_user_by('id', (int) $reply_user_id);
            $name = $replier ? $replier->display_name : 'Client #' . $reply_user_id;
            $title = sprintf('New reply from client on ticket #%s', $ticket->ticket_number);
            $msg   = sprintf('%s: %s', $name, $ticket->subject);
            foreach (self::get_admin_ids() as $aid) {
                self::add_notification($aid, 'ticket_replied', $title, $msg, (int)$ticket_id);
            }
            $admin_email = get_option('admin_email');
            if ($admin_email) {
                $body  = "A new reply has been received from the client.\n\n";
                $body .= "Submitted by: " . $name . "\n";
                $body .= "Ticket Number: " . $ticket->ticket_number . "\n";
                $body .= "Subject: " . $ticket->subject . "\n";
                if ($snippet) {
                    $body .= "Message snippet:\n" . $snippet . (mb_strlen(strip_tags($data['message'] ?? '')) > 120 ? '…' : '') . "\n";
                }
                $body .= "\nReply in admin panel: " . self::admin_ticket_url($ticket_id) . "\n";
                @wp_mail($admin_email, sprintf('Ticket Reply: %s', $ticket->ticket_number), $body);
            }
        }
    }

    /**
     * Notify the client when ticket status/priority/category changes (via admin quick form).
     *
     * @param int   $ticket_id
     * @param array $changes map of modified columns
     * @param int   $changed_by_user_id
     * @return void
     */
    public function send_status_notification($ticket_id, $changes, $changed_by_user_id)
    {
        if (empty($changes)) return;
        $ticket = Database::get_row('tickets', $ticket_id);
        if (!$ticket) return;

        $statuses   = self::get_statuses();
        $prios      = self::get_priorities();
        $cats       = self::get_categories();

        $bits = [];
        if (!empty($changes['status']))   $bits[] = 'Status: ' . ($statuses[$changes['status']] ?? $changes['status']);
        if (!empty($changes['priority'])) $bits[] = 'Priority: ' . ($prios[$changes['priority']] ?? $changes['priority']);
        if (!empty($changes['category'])) $bits[] = 'Category: ' . ($cats[$changes['category']] ?? $changes['category']);
        if (!empty($changes['subject']))  $bits[] = 'Subject changed.';
        if (empty($bits)) return;

        $summary = implode(', ', $bits);
        $title   = sprintf('Ticket Update #%s', $ticket->ticket_number);

        $changed_by_admin = user_can((int)$changed_by_user_id, 'manage_options');

        if ($changed_by_admin) {
            $msg = sprintf('The following changes were applied to ticket "%s": %s', $ticket->subject, $summary);
            self::add_notification((int) $ticket->user_id, 'ticket_updated', $title, $msg, (int)$ticket_id);
            $client = get_user_by('id', (int)$ticket->user_id);
            if ($client) {
                $body  = "Changes have been applied to your ticket.\n\n";
                $body .= "Ticket Number: " . $ticket->ticket_number . "\n";
                $body .= "Subject: " . $ticket->subject . "\n\n";
                $body .= "Changes: " . $summary . "\n\n";
                $body .= "View: " . self::client_ticket_url($ticket_id) . "\n";
                @wp_mail($client->user_email, sprintf('Ticket Update: %s', $ticket->ticket_number), $body);
            }
        }
    }

    /**
     * Notify the client and log when a ticket is closed.
     *
     * @param int   $ticket_id
     * @param array $data
     * @return void
     */
    public function send_closed_notification($ticket_id, $data)
    {
        $ticket = Database::get_row('tickets', $ticket_id);
        if (!$ticket) return;

        $closed_by_admin = !empty($data['closed_by']) && user_can((int)$data['closed_by'], 'manage_options');
        $title = sprintf('Ticket #%s closed', $ticket->ticket_number);
        $msg   = sprintf('The ticket "%s" has been closed. You can submit a new ticket if needed.', $ticket->subject);

        if ($closed_by_admin) {
            self::add_notification((int) $ticket->user_id, 'ticket_closed', $title, $msg, (int)$ticket_id);
            $client = get_user_by('id', (int)$ticket->user_id);
            if ($client) {
                $body  = "Your ticket has been closed.\n\n";
                $body .= "Number: " . $ticket->ticket_number . "\n";
                $body .= "Subject: " . $ticket->subject . "\n\n";
                $body .= "View in portal: " . self::client_ticket_url($ticket_id) . "\n";
                @wp_mail($client->user_email, sprintf('Ticket Closed: %s', $ticket->ticket_number), $body);
            }
            return;
        }

        // If a client closed their own ticket, notify admins
        foreach (self::get_admin_ids() as $aid) {
            self::add_notification($aid, 'ticket_closed', $title, $msg, (int)$ticket_id);
        }
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $client = get_user_by('id', (int)$ticket->user_id);
            $body   = "The client closed their ticket.\n\n";
            $body  .= "Number: " . $ticket->ticket_number . "\n";
            $body  .= "Subject: " . $ticket->subject . "\n";
            if ($client) $body .= "Client: " . $client->display_name . " <" . $client->user_email . ">\n";
            $body  .= "\nAdmin: " . self::admin_ticket_url($ticket_id) . "\n";
            @wp_mail($admin_email, sprintf('Ticket Closed: %s', $ticket->ticket_number), $body);
        }
    }
}
