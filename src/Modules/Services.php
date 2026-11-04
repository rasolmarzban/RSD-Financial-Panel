<?php
/**
 * Services module class
 *
 * Business logic for new service requests
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

class Services
{
    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        add_action('rsd_fp_service_requested', [$this, 'notify_admin'], 10, 2);
    }

    /**
     * Get all service type definitions with forms
     *
     * @return array
     */
    public static function get_service_types()
    {
        return apply_filters('rsd_fp_service_types', [
            'company_registration' => [
                'label'       => esc_html__('درخواست ثبت شرکت', 'rsd-finance-panel'),
                'description' => esc_html__('ثبت انواع شرکت‌های تجاری و دانش‌بنیان', 'rsd-finance-panel'),
                'icon'        => 'building',
                'fields'      => [
                    'company_type' => [
                        'label'    => esc_html__('نوع شرکت', 'rsd-finance-panel'),
                        'type'     => 'select',
                        'options'  => [
                            'llc'          => esc_html__('مسئولیت محدود', 'rsd-finance-panel'),
                            'joint_stock'  => esc_html__('سهامی عام', 'rsd-finance-panel'),
                            'private_joint' => esc_html__('سهامی خاص', 'rsd-finance-panel'),
                            'cooperative'  => esc_html__('تعاونی', 'rsd-finance-panel'),
                        ],
                        'required' => true,
                    ],
                    'shareholders_count' => [
                        'label'    => esc_html__('تعداد سهامداران', 'rsd-finance-panel'),
                        'type'     => 'number',
                        'required' => true,
                    ],
                    'proposed_name' => [
                        'label'    => esc_html__('نام پیشنهادی', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'city' => [
                        'label'    => esc_html__('شهر', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'activity_type' => [
                        'label'    => esc_html__('نوع فعالیت', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                ],
            ],
            'brand_registration' => [
                'label'       => esc_html__('ثبت برند', 'rsd-finance-panel'),
                'description' => esc_html__('ثبت علامت تجاری و برند در ثبت اختراعات', 'rsd-finance-panel'),
                'icon'        => 'award',
                'fields'      => [
                    'brand_name' => [
                        'label'    => esc_html__('نام برند', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'brand_type' => [
                        'label'    => esc_html__('نوع برند', 'rsd-finance-panel'),
                        'type'     => 'select',
                        'options'  => [
                            'word'       => esc_html__('لفزی', 'rsd-finance-panel'),
                            'logo'       => esc_html__('تصویری', 'rsd-finance-panel'),
                            'composite'  => esc_html__('ترکیبی', 'rsd-finance-panel'),
                            'slogan'     => esc_html__('شعار', 'rsd-finance-panel'),
                        ],
                        'required' => true,
                    ],
                    'activity_field' => [
                        'label'    => esc_html__('زمینه فعالیت', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                    'notes' => [
                        'label'    => esc_html__('توضیحات', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                ],
            ],
            'tax_id_application' => [
                'label'       => esc_html__('دریافت شماره مالیاتی', 'rsd-finance-panel'),
                'description' => esc_html__('اختصاص شناسه ملی اقتصادی و پروانه کسب', 'rsd-finance-panel'),
                'icon'        => 'file-text',
                'fields'      => [
                    'activity_type' => [
                        'label'    => esc_html__('نوع فعالیت', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'activity_start_date' => [
                        'label'    => esc_html__('تاریخ شروع فعالیت', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'company_info' => [
                        'label'    => esc_html__('اطلاعات شرکت', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                    'required_docs' => [
                        'label'    => esc_html__('مدارک موردنیاز', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                ],
            ],
            'new_personnel_payroll' => [
                'label'       => esc_html__('صدور فیش حقوقی برای پرسنل جدید', 'rsd-finance-panel'),
                'description' => esc_html__('ورود اطلاعات پرسنل جدید برای شروع صدور فیش حقوقی', 'rsd-finance-panel'),
                'icon'        => 'user-plus',
                'fields'      => [
                    'full_name' => [
                        'label'    => esc_html__('نام و نام خانوادگی', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'start_date' => [
                        'label'    => esc_html__('تاریخ شروع کار', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'salary' => [
                        'label'    => esc_html__('حقوق (تومان)', 'rsd-finance-panel'),
                        'type'     => 'number',
                        'required' => true,
                    ],
                    'work_hours' => [
                        'label'    => esc_html__('ساعات کار', 'rsd-finance-panel'),
                        'type'     => 'text',
                    ],
                    'bank_info' => [
                        'label'    => esc_html__('اطلاعات بانکی', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                    'insurance_info' => [
                        'label'    => esc_html__('اطلاعات بیمه', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                    'tax_id' => [
                        'label'    => esc_html__('شماره مالیاتی', 'rsd-finance-panel'),
                        'type'     => 'text',
                    ],
                ],
            ],
            'employee_termination' => [
                'label'       => esc_html__('خروج کارمند', 'rsd-finance-panel'),
                'description' => esc_html__('ثبت اصلاحیه خروج کارمند و تسویه حساب', 'rsd-finance-panel'),
                'icon'        => 'user-minus',
                'fields'      => [
                    'employee_name' => [
                        'label'    => esc_html__('نام کارمند', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'end_date' => [
                        'label'    => esc_html__('تاریخ پایان همکاری', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'end_reason' => [
                        'label'    => esc_html__('دلیل پایان همکاری', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                    'notes' => [
                        'label'    => esc_html__('توضیحات', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get status labels
     *
     * @return array
     */
    public static function get_statuses()
    {
        return [
            'pending'     => esc_html__('در انتظار', 'rsd-finance-panel'),
            'in_progress' => esc_html__('در حال بررسی', 'rsd-finance-panel'),
            'completed'   => esc_html__('انجام‌شده', 'rsd-finance-panel'),
            'rejected'    => esc_html__('ردشده', 'rsd-finance-panel'),
        ];
    }

    /**
     * Notify admin when a new service request is submitted
     *
     * @param int $service_id
     * @param array $data
     * @return void
     */
    public function notify_admin($service_id, $data)
    {
        $admin_email = get_option('admin_email');
        if (!$admin_email) return;

        $subject = sprintf(
            esc_html__('New Service Request: %s', 'rsd-finance-panel'),
            $data['title'] ?? $data['service_type']
        );

        $body = sprintf(
            "New service request received.\n\nType: %s\nTitle: %s\nUser ID: %d\n\nView in admin: %s",
            $data['service_type'],
            $data['title'] ?? '',
            $data['user_id'] ?? 0,
            admin_url('admin.php?page=rsd-fp-services&view=single&id=' . $service_id)
        );

        wp_mail($admin_email, $subject, $body);
    }
}
