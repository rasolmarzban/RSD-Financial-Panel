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
                'label'       => esc_html__('Company Registration Request', 'rsd-finance-panel'),
                'description' => esc_html__('Registration of various commercial and knowledge-based companies', 'rsd-finance-panel'),
                'icon'        => 'building',
                'fields'      => [
                    'company_type' => [
                        'label'    => esc_html__('Company Type', 'rsd-finance-panel'),
                        'type'     => 'select',
                        'options'  => [
                            'llc'          => esc_html__('Limited Liability', 'rsd-finance-panel'),
                            'joint_stock'  => esc_html__('Public Joint Stock', 'rsd-finance-panel'),
                            'private_joint' => esc_html__('Private Joint Stock', 'rsd-finance-panel'),
                            'cooperative'  => esc_html__('Cooperative', 'rsd-finance-panel'),
                        ],
                        'required' => true,
                    ],
                    'shareholders_count' => [
                        'label'    => esc_html__('Number of Shareholders', 'rsd-finance-panel'),
                        'type'     => 'number',
                        'required' => true,
                    ],
                    'proposed_name' => [
                        'label'    => esc_html__('Proposed Name', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'city' => [
                        'label'    => esc_html__('City', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'activity_type' => [
                        'label'    => esc_html__('Activity Type', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                ],
            ],
            'brand_registration' => [
                'label'       => esc_html__('Brand Registration', 'rsd-finance-panel'),
                'description' => esc_html__('Registration of trademarks and brands with the patent office', 'rsd-finance-panel'),
                'icon'        => 'award',
                'fields'      => [
                    'brand_name' => [
                        'label'    => esc_html__('Brand Name', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'brand_type' => [
                        'label'    => esc_html__('Brand Type', 'rsd-finance-panel'),
                        'type'     => 'select',
                        'options'  => [
                            'word'       => esc_html__('Word Mark', 'rsd-finance-panel'),
                            'logo'       => esc_html__('Figurative', 'rsd-finance-panel'),
                            'composite'  => esc_html__('Composite', 'rsd-finance-panel'),
                            'slogan'     => esc_html__('Slogan', 'rsd-finance-panel'),
                        ],
                        'required' => true,
                    ],
                    'activity_field' => [
                        'label'    => esc_html__('Field of Activity', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                    'notes' => [
                        'label'    => esc_html__('Description', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                ],
            ],
            'tax_id_application' => [
                'label'       => esc_html__('Tax Number Application', 'rsd-finance-panel'),
                'description' => esc_html__('Issuance of national economic ID and business license', 'rsd-finance-panel'),
                'icon'        => 'file-text',
                'fields'      => [
                    'activity_type' => [
                        'label'    => esc_html__('Activity Type', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'activity_start_date' => [
                        'label'    => esc_html__('Activity Start Date', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'company_info' => [
                        'label'    => esc_html__('Company Information', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                    'required_docs' => [
                        'label'    => esc_html__('Required Documents', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                ],
            ],
            'new_personnel_payroll' => [
                'label'       => esc_html__('Issue Payslip for New Personnel', 'rsd-finance-panel'),
                'description' => esc_html__('Enter new personnel data to start issuing payslips', 'rsd-finance-panel'),
                'icon'        => 'user-plus',
                'fields'      => [
                    'full_name' => [
                        'label'    => esc_html__('Full Name', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'start_date' => [
                        'label'    => esc_html__('Start Date', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'salary' => [
                        'label'    => esc_html__('Salary (Toman)', 'rsd-finance-panel'),
                        'type'     => 'number',
                        'required' => true,
                    ],
                    'work_hours' => [
                        'label'    => esc_html__('Working Hours', 'rsd-finance-panel'),
                        'type'     => 'text',
                    ],
                    'bank_info' => [
                        'label'    => esc_html__('Bank Information', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                    'insurance_info' => [
                        'label'    => esc_html__('Insurance Information', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                    ],
                    'tax_id' => [
                        'label'    => esc_html__('Tax Number', 'rsd-finance-panel'),
                        'type'     => 'text',
                    ],
                ],
            ],
            'employee_termination' => [
                'label'       => esc_html__('Employee Termination', 'rsd-finance-panel'),
                'description' => esc_html__('Record employee termination amendment and settlement', 'rsd-finance-panel'),
                'icon'        => 'user-minus',
                'fields'      => [
                    'employee_name' => [
                        'label'    => esc_html__('Employee Name', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'end_date' => [
                        'label'    => esc_html__('End of Employment Date', 'rsd-finance-panel'),
                        'type'     => 'text',
                        'required' => true,
                    ],
                    'end_reason' => [
                        'label'    => esc_html__('Reason for End of Employment', 'rsd-finance-panel'),
                        'type'     => 'textarea',
                        'required' => true,
                    ],
                    'notes' => [
                        'label'    => esc_html__('Description', 'rsd-finance-panel'),
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
            'pending'     => esc_html__('Pending', 'rsd-finance-panel'),
            'in_progress' => esc_html__('In Progress', 'rsd-finance-panel'),
            'completed'   => esc_html__('Completed', 'rsd-finance-panel'),
            'rejected'    => esc_html__('Rejected', 'rsd-finance-panel'),
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
