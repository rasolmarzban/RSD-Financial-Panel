<?php
/**
 * Reports module class
 *
 * Business logic for reports management
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Database;

class Reports
{
    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        // Hooks for report generation events
    }

    /**
     * Get report types from settings (with Persian defaults)
     *
     * @return array
     */
    public static function get_types()
    {
        $types = \RSD\FinancePanel\Core\Plugin::get_setting('report_types', []);
        if (empty($types)) {
            $types = [
                'accounting'     => esc_html__('گزارش حسابداری', 'rsd-finance-panel'),
                'income_expense' => esc_html__('گزارش درآمد و هزینه', 'rsd-finance-panel'),
                'tax'            => esc_html__('گزارش مالیات', 'rsd-finance-panel'),
                'vat'            => esc_html__('گزارش ارزش افزوده', 'rsd-finance-panel'),
                'payroll'        => esc_html__('گزارش حقوق و دستمزد', 'rsd-finance-panel'),
                'annual'         => esc_html__('گزارش‌های سالانه', 'rsd-finance-panel'),
                'management'     => esc_html__('گزارش‌های مدیریتی', 'rsd-finance-panel'),
                'other'          => esc_html__('سایر گزارش‌ها', 'rsd-finance-panel'),
            ];
        }
        return $types;
    }

    /**
     * Get distinct years available in reports for a client
     *
     * @param int $user_id
     * @return array
     */
    public static function get_available_years($user_id)
    {
        global $wpdb;
        $table = Database::table('reports');
        $years = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT year FROM {$table} WHERE user_id = %d AND year IS NOT NULL AND year != '' ORDER BY year DESC",
            $user_id
        ));
        return $years ?: [];
    }

    /**
     * Get months list (Persian)
     *
     * @return array
     */
    public static function get_months()
    {
        return [
            '01' => esc_html__('فروردین', 'rsd-finance-panel'),
            '02' => esc_html__('اردیبهشت', 'rsd-finance-panel'),
            '03' => esc_html__('خرداد', 'rsd-finance-panel'),
            '04' => esc_html__('تیر', 'rsd-finance-panel'),
            '05' => esc_html__('مرداد', 'rsd-finance-panel'),
            '06' => esc_html__('شهریور', 'rsd-finance-panel'),
            '07' => esc_html__('مهر', 'rsd-finance-panel'),
            '08' => esc_html__('آبان', 'rsd-finance-panel'),
            '09' => esc_html__('آذر', 'rsd-finance-panel'),
            '10' => esc_html__('دی', 'rsd-finance-panel'),
            '11' => esc_html__('بهمن', 'rsd-finance-panel'),
            '12' => esc_html__('اسفند', 'rsd-finance-panel'),
        ];
    }
}
