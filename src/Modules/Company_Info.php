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
     * Get the company logo URL for a user
     *
     * @param int $user_id
     * @return string
     */
    public static function get_logo_url($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return '';
        }
        $info = self::get($user_id);
        return (!empty($info->logo_url)) ? $info->logo_url : '';
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
                'label'   => esc_html__('Company Name', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'brand_name' => [
                'label'   => esc_html__('Brand Name', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'address' => [
                'label'   => esc_html__('Address', 'rsd-finance-panel'),
                'type'    => 'textarea',
                'section' => 'basic',
            ],
            'registration_number' => [
                'label'   => esc_html__('Registration Number', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'tax_id' => [
                'label'   => esc_html__('Tax Number', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'vat_number' => [
                'label'   => esc_html__('VAT Number', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'basic',
            ],
            'ceo_name' => [
                'label'   => esc_html__('CEO Name', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'people',
            ],
            'shareholders' => [
                'label'   => esc_html__('Shareholders', 'rsd-finance-panel'),
                'type'    => 'textarea',
                'section' => 'people',
            ],
            'phone' => [
                'label'   => esc_html__('Phone Number', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'contact',
            ],
            'email' => [
                'label'   => esc_html__('Email', 'rsd-finance-panel'),
                'type'    => 'email',
                'section' => 'contact',
            ],
            'website' => [
                'label'   => esc_html__('Website', 'rsd-finance-panel'),
                'type'    => 'url',
                'section' => 'contact',
            ],
            'bank_name' => [
                'label'   => esc_html__('Bank Name', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'banking',
            ],
            'bank_account' => [
                'label'   => esc_html__('Account Number', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'banking',
            ],
            'financial_contact_name' => [
                'label'   => esc_html__('Financial Contact Name', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'financial',
            ],
            'financial_contact_phone' => [
                'label'   => esc_html__('Financial Contact Phone', 'rsd-finance-panel'),
                'type'    => 'text',
                'section' => 'financial',
            ],
            'financial_contact_email' => [
                'label'   => esc_html__('Financial Contact Email', 'rsd-finance-panel'),
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
            'basic'     => esc_html__('Basic Information', 'rsd-finance-panel'),
            'people'    => esc_html__('Directors and Shareholders', 'rsd-finance-panel'),
            'contact'   => esc_html__('Contact Information', 'rsd-finance-panel'),
            'banking'   => esc_html__('Banking Information', 'rsd-finance-panel'),
            'financial' => esc_html__('Financial Contact', 'rsd-finance-panel'),
        ];
    }
}
