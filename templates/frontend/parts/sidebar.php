<?php

/**
 * Sidebar partial for user portal
 *
 * @var string $active
 * @var WP_User $current_user
 */
defined('ABSPATH') || exit;

$active = $active ?? 'dashboard';
$current_user = $current_user ?? wp_get_current_user();

$company_logo = class_exists('\RSD\FinancePanel\Modules\Company_Info')
    ? \RSD\FinancePanel\Modules\Company_Info::get_logo_url($current_user->ID)
    : '';

$nav_items = [
    'dashboard'    => ['label' => 'Dashboard',            'icon' => '🏠', 'url' => rsd_fp_page_url('dashboard')],
    'documents'    => ['label' => 'Documents',            'icon' => '📁', 'url' => rsd_fp_page_url('documents')],
    'tickets'      => ['label' => 'Messages & Tickets',   'icon' => '💬', 'url' => rsd_fp_page_url('tickets')],
    'company_info' => ['label' => 'Company Information',  'icon' => '🏢', 'url' => rsd_fp_page_url('company_info')],
    'reports'      => ['label' => 'Reports',              'icon' => '📊', 'url' => rsd_fp_page_url('reports')],
    'invoices'     => ['label' => 'Billing',              'icon' => '💰', 'url' => rsd_fp_page_url('invoices')],
    'services'     => ['label' => 'New Services',         'icon' => '⚙️', 'url' => rsd_fp_page_url('services')],
    'calendar'     => ['label' => 'Calendar & Deadlines', 'icon' => '📅', 'url' => rsd_fp_page_url('calendar')],
    'profile'      => ['label' => 'Edit Profile',         'icon' => '👤', 'url' => rsd_fp_page_url('profile')],
];
?>

<aside class="rsd-fp-sidebar">
    <div class="rsd-fp-sidebar-header">
        <div class="rsd-fp-logo">
            <?php if ($company_logo) : ?>
                <img src="<?php echo esc_url($company_logo); ?>" alt="">
            <?php else : ?>
                R
            <?php endif; ?>
        </div>
        <h3>Client Portal</h3>
        <p>Accounting &amp; Financial Consulting Company</p>
    </div>

    <nav class="rsd-fp-nav">
        <span class="rsd-fp-nav-label">Main Menu</span>
        <?php foreach ($nav_items as $key => $item) : ?>
            <a href="<?php echo esc_url($item['url']); ?>"
                class="rsd-fp-nav-item <?php echo $active === $key ? 'active' : ''; ?>">
                <span class="rsd-icon"><?php echo esc_html($item['icon']); ?></span>
                <span><?php echo esc_html($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="rsd-fp-sidebar-logout">
        <?php $logout_url = rsd_fp_logout_url(); ?>
        <a href="<?php echo esc_url($logout_url); ?>"
            class="rsd-fp-nav-item rsd-fp-logout"
            title="<?php esc_attr_e('Log out of account', 'rsd-finance-panel'); ?>">
            <span class="rsd-icon">🚪</span>
            <span><?php esc_html_e('Logout', 'rsd-finance-panel'); ?></span>
        </a>
    </div>

    <div class="rsd-fp-sidebar-footer">
        <div class="rsd-fp-version">
            Version <?php echo esc_html(RSD_FP_VERSION); ?> | Made with "RSD"
        </div>
    </div>
</aside>