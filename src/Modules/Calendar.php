<?php

/**
 * Calendar module class
 *
 * Business logic for calendar, events, and deadlines management
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

class Calendar
{
    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        // Schedule cron for reminders
        if (!wp_next_scheduled('rsd_fp_deadline_reminders')) {
            wp_schedule_event(time(), 'daily', 'rsd_fp_deadline_reminders');
        }
        add_action('rsd_fp_deadline_reminders', [$this, 'send_deadline_reminders']);
    }

    /**
     * Get calendar categories (event types)
     *
     * @return array
     */
    public static function get_categories()
    {
        $cats = \RSD\FinancePanel\Core\Plugin::get_setting('calendar_categories', []);
        if (empty($cats)) {
            $cats = [
                'tax_deadline'    => esc_html__('Tax Deadline', 'rsd-finance-panel'),
                'document_due'    => esc_html__('Document Submission Deadline', 'rsd-finance-panel'),
                'payment_due'     => esc_html__('Payment Due', 'rsd-finance-panel'),
                'payroll'         => esc_html__('Payroll', 'rsd-finance-panel'),
                'meeting'         => esc_html__('Meetings', 'rsd-finance-panel'),
                'company_affairs' => esc_html__('Company Affairs', 'rsd-finance-panel'),
                'client_request'  => esc_html__('Client Request', 'rsd-finance-panel'),
                'other'           => esc_html__('Other', 'rsd-finance-panel'),
            ];
        }
        return $cats;
    }

    /**
     * Get category color for display
     *
     * @param string $category
     * @return string
     */
    public static function get_category_color($category)
    {
        $colors = [
            'tax_deadline'    => '#ef4444',
            'document_due'    => '#f59e0b',
            'payment_due'     => '#10b981',
            'payroll'         => '#3b82f6',
            'meeting'         => '#8b5cf6',
            'company_affairs' => '#06b6d4',
            'client_request'  => '#ec4899',
            'other'           => '#6b7280',
        ];
        return $colors[$category] ?? '#6b7280';
    }

    /**
     * Get calendar event statuses
     *
     * @return array
     */
    public static function get_statuses()
    {
        return [
            'pending'      => esc_html__('Pending Approval', 'rsd-finance-panel'),
            'confirmed'    => esc_html__('Approved', 'rsd-finance-panel'),
            'needs_change' => esc_html__('Needs Change', 'rsd-finance-panel'),
            'rejected'     => esc_html__('Rejected', 'rsd-finance-panel'),
        ];
    }

    /**
     * Get view modes
     *
     * @return array
     */
    public static function get_views()
    {
        return [
            'day'    => esc_html__('Daily', 'rsd-finance-panel'),
            'week'   => esc_html__('Weekly', 'rsd-finance-panel'),
            'month'  => esc_html__('Monthly', 'rsd-finance-panel'),
            'year'   => esc_html__('Yearly', 'rsd-finance-panel'),
        ];
    }

    /**
     * Send deadline reminder emails to clients
     *
     * Hooked into daily cron
     *
     * @return void
     */
    public function send_deadline_reminders()
    {
        global $wpdb;
        $table = \RSD\FinancePanel\Core\Database::table('calendar');

        // Get events starting within 3 days
        $reminders = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE 
             reminder IS NOT NULL 
             AND status = 'confirmed'
             AND start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)"
        );

        foreach ($reminders as $event) {
            $user = get_user_by('id', $event->user_id);
            if (!$user) continue;

            $subject = sprintf(esc_html__('Reminder: %s', 'rsd-finance-panel'), $event->title);
            $body = sprintf(
                "This is a reminder for your scheduled event.\n\nTitle: %s\nDate: %s\nDescription: %s\n\nView calendar: %s",
                $event->title,
                $event->start_date,
                $event->description ?? '',
                rsd_fp_page_url('calendar')
            );

            wp_mail($user->user_email, $subject, $body);
        }
    }
}
