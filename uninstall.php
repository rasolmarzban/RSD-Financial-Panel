<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package RSD_Finance_Panel
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up database tables
global $wpdb;
$tables = [
    $wpdb->prefix . 'rsd_fp_documents',
    $wpdb->prefix . 'rsd_fp_tickets',
    $wpdb->prefix . 'rsd_fp_ticket_messages',
    $wpdb->prefix . 'rsd_fp_invoices',
    $wpdb->prefix . 'rsd_fp_payments',
    $wpdb->prefix . 'rsd_fp_reports',
    $wpdb->prefix . 'rsd_fp_services',
    $wpdb->prefix . 'rsd_fp_calendar',
    $wpdb->prefix . 'rsd_fp_company_info',
    $wpdb->prefix . 'rsd_fp_notifications',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Clean up options
delete_option('rsd_fp_version');
delete_option('rsd_fp_db_version');
delete_option('rsd_fp_settings');
delete_option('rsd_fp_installed');
