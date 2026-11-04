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
    'dashboard'    => ['label' => 'داشبورد',            'icon' => '🏠', 'url' => rsd_fp_page_url('dashboard')],
    'documents'    => ['label' => 'مدارک',               'icon' => '📁', 'url' => rsd_fp_page_url('documents')],
    'tickets'      => ['label' => 'پیام‌ها و تیکت‌ها',   'icon' => '💬', 'url' => rsd_fp_page_url('tickets')],
    'company_info' => ['label' => 'اطلاعات شرکت',         'icon' => '🏢', 'url' => rsd_fp_page_url('company_info')],
    'reports'      => ['label' => 'گزارش‌ها',            'icon' => '📊', 'url' => rsd_fp_page_url('reports')],
    'invoices'     => ['label' => 'صورتحساب',            'icon' => '💰', 'url' => rsd_fp_page_url('invoices')],
    'services'     => ['label' => 'خدمات جدید',          'icon' => '⚙️', 'url' => rsd_fp_page_url('services')],
    'calendar'     => ['label' => 'تقویم و موعدها',       'icon' => '📅', 'url' => rsd_fp_page_url('calendar')],
];
?>

<aside class="rsd-fp-sidebar">
    <div class="rsd-fp-sidebar-header">
        <div class="rsd-fp-logo">R</div>
        <h3>پرتال مشتریان</h3>
        <p>شرکت حسابداری و مشاوره مالی</p>
    </div>

    <nav class="rsd-fp-nav">
        <span class="rsd-fp-nav-label">منو اصلی</span>
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
            title="<?php esc_attr_e('خروج از حساب کاربری', 'rsd-finance-panel'); ?>">
            <span class="rsd-icon">🚪</span>
            <span><?php esc_html_e('خروج', 'rsd-finance-panel'); ?></span>
        </a>
    </div>

    <div class="rsd-fp-sidebar-footer">
        <div class="rsd-fp-version">
            نسخه <?php echo esc_html(RSD_FP_VERSION); ?> | ساخته شده با ❤️
        </div>
    </div>
</aside>