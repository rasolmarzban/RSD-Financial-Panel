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
     * Get report types from settings (with English defaults)
     *
     * @return array
     */
    public static function get_types()
    {
        $types = \RSD\FinancePanel\Core\Plugin::get_setting('report_types', []);
        if (empty($types)) {
            $types = [
                'accounting'     => esc_html__('Accounting Report', 'rsd-finance-panel'),
                'income_expense' => esc_html__('Income and Expense Report', 'rsd-finance-panel'),
                'tax'            => esc_html__('Tax Report', 'rsd-finance-panel'),
                'vat'            => esc_html__('VAT Report', 'rsd-finance-panel'),
                'payroll'        => esc_html__('Payroll Report', 'rsd-finance-panel'),
                'annual'         => esc_html__('Annual Reports', 'rsd-finance-panel'),
                'management'     => esc_html__('Management Reports', 'rsd-finance-panel'),
                'other'          => esc_html__('Other Reports', 'rsd-finance-panel'),
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
     * Get months list
     *
     * @return array
     */
    public static function get_months()
    {
        return [
            '01' => esc_html__('January', 'rsd-finance-panel'),
            '02' => esc_html__('February', 'rsd-finance-panel'),
            '03' => esc_html__('March', 'rsd-finance-panel'),
            '04' => esc_html__('April', 'rsd-finance-panel'),
            '05' => esc_html__('May', 'rsd-finance-panel'),
            '06' => esc_html__('June', 'rsd-finance-panel'),
            '07' => esc_html__('July', 'rsd-finance-panel'),
            '08' => esc_html__('August', 'rsd-finance-panel'),
            '09' => esc_html__('September', 'rsd-finance-panel'),
            '10' => esc_html__('October', 'rsd-finance-panel'),
            '11' => esc_html__('November', 'rsd-finance-panel'),
            '12' => esc_html__('December', 'rsd-finance-panel'),
        ];
    }
}
