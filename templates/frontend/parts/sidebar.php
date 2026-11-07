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
        <div class="rsd-fp-logo">R</div>
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
        <?php
        $logout_url = '';
        if (function_exists('wp_logout_url')) {
            $logout_url = wp_logout_url();
        }
        if ($logout_url === '' || strpos($logout_url, 'action=logout') === false) {
            $base = function_exists('wp_login_url') ? wp_login_url() : home_url('/wp-login.php');
            $logout_url = add_query_arg('action', 'logout', $base);
        }
        if (strpos($logout_url, '_wpnonce') === false && function_exists('wp_create_nonce')) {
            $logout_url = add_query_arg('_wpnonce', wp_create_nonce('log-out'), $logout_url);
        }
        ?>
        <a href="<?php echo esc_url($logout_url); ?>"
            class="rsd-fp-nav-item rsd-fp-logout"
            title="<?php esc_attr_e('Log out of account', 'rsd-finance-panel'); ?>">
            <span class="rsd-icon">🚪</span>
            <span><?php esc_html_e('Logout', 'rsd-finance-panel'); ?></span>
        </a>
    </div>

    <div class="rsd-fp-sidebar-footer">
        <div class="rsd-fp-version">
            Version <?php echo esc_html(RSD_FP_VERSION); ?> | Made with ❤️
        </div>
    </div>
</aside>