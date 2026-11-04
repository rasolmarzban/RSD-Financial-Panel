<?php
/**
 * Company Info module class
 *
 * Business logic for company profile information
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Database;

class Company_Info
{
    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        // Show pending changes badge in admin
    }

    /**
     * Get company info for a user
     *
     * @param int $user_id
     * @return object|null
     */
    public static function get($user_id)
    {
        global $wpdb;
        $table = Database::table('company_info');
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id));
    }

    /**
     * Get editable fields list
     *
     * @return array
     */
    public static function get_fields()
    {
        return [
            'company_name' => [
                'label'   => esc_html__('نام شرکت', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'brand_name' => [
                'label'   => esc_html__('نام تجاری', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'address' => [
                'label'   => esc_html__('آدرس', 'rsd-finance-panel'),
                'type'    => 'textarea',
                'section' => 'basic',
            ],
            'registration_number' => [
                'label'   => esc_html__('شماره ثبت', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'tax_id' => [
                'label'   => esc_html__('شماره مالیاتی', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'vat_number' => [
                'label'   => esc_html__('شماره مالیات بر ارزش افزوده', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'ceo_name' => [
                'label'   => esc_html__('نام مدیرعامل', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'people',
            ],
            'shareholders' => [
                'label'   => esc_html__('نام سهامداران', 'rsd-finance-panel'),
                'type'    => 'textarea',
                'section' => 'people',
            ],
            'phone' => [
                'label'   => esc_html__('شماره تلفن', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'contact',
            ],
            'email' => [
                'label'   => esc_html__('ایمیل', 'rsd-finance-panel'),
                'type'    => 'email',
                'section' => 'contact',
            ],
            'website' => [
                'label'   => esc_html__('وب‌سایت', 'rsd-finance-panel'),
                'type'    => 'url',
                'section' => 'contact',
            ],
            'bank_name' => [
                'label'   => esc_html__('نام بانک', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'banking',
            ],
            'bank_account' => [
                'label'   => esc_html__('شماره حساب', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'banking',
            ],
            'financial_contact_name' => [
                'label'   => esc_html__('نام مسئول مالی', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'financial',
            ],
            'financial_contact_phone' => [
                'label'   => esc_html__('تلفن مسئول مالی', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'financial',
            ],
            'financial_contact_email' => [
                'label'   => esc_html__('ایمیل مسئول مالی', 'rsd-finance-panel'),
                'type'    => 'email',
                'section' => 'financial',
            ],
        ];
    }

    /**
     * Get section titles
     *
     * @return array
     */
    public static function get_sections()
    {
        return [
            'basic'     => esc_html__('اطلاعات پایه', 'rsd-finance-panel'),
            'people'    => esc_html__('مدیران و سهامداران', 'rsd-finance-panel'),
            'contact'   => esc_html__('اطلاعات تماس', 'rsd-finance-panel'),
            'banking'   => esc_html__('اطلاعات بانکی', 'rsd-finance-panel'),
            'financial' => esc_html__('مسئول مالی', 'rsd-finance-panel'),
        ];
    }
}
