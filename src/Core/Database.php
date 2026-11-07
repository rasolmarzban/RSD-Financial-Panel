<?php

/**
 * Database class
 *
 * Handles custom database table creation and operations
 *
 * @package RSD_Finance_Panel
 * @subpackage Core
 */

namespace RSD\FinancePanel\Core;

defined('ABSPATH') || exit;

class Database
{
    /**
     * Current database version
     */
    const DB_VERSION = '1.1';

    /**
     * Tables that do NOT have updated_at column (so we don't try to set it,
     * otherwise MySQL strict mode throws Unknown column and INSERT fails).
     *
     * @var array
     */
    private static $no_updated_at = [
        'ticket_messages',
        'notifications',
        'payments',
    ];

    /**
     * Memoised per-table checks whether the table actually has updated_at.
     *
     * @var array<string,bool>
     */
    private static $has_updated_at_cache = [];

    /**
     * Whether table supports standard updated_at timestamp column
     *
     * @param string $table short table name (without prefix)
     * @return bool
     */
    private static function table_has_updated_at($table)
    {
        if (isset(self::$has_updated_at_cache[$table])) {
            return self::$has_updated_at_cache[$table];
        }
        if (in_array($table, self::$no_updated_at, true)) {
            return self::$has_updated_at_cache[$table] = false;
        }
        global $wpdb;
        $table_name = self::table($table);
        $suppress = $wpdb->suppress_errors(true);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- metadata read
        $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table_name} LIKE %s", 'updated_at'));
        $wpdb->suppress_errors($suppress);
        return self::$has_updated_at_cache[$table] = !empty($col);
    }

    /**
     * Get prefixed table name
     *
     * @param string $table
     * @return string
     */
    public static function table($table)
    {
        global $wpdb;
        return $wpdb->prefix . 'rsd_fp_' . $table;
    }

    /**
     * Create all required database tables
     *
     * @return void
     */
    public static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $tables = [];

        // Documents table
        $tables[] = "CREATE TABLE " . self::table('documents') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            title varchar(255) NOT NULL,
            category varchar(100) DEFAULT NULL,
            year varchar(10) DEFAULT NULL,
            month varchar(20) DEFAULT NULL,
            period varchar(100) DEFAULT NULL,
            description text DEFAULT NULL,
            expiry_date date DEFAULT NULL,
            file_path varchar(500) DEFAULT NULL,
            file_name varchar(255) DEFAULT NULL,
            file_size bigint(20) DEFAULT 0,
            file_type varchar(100) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'uploaded',
            is_incomplete tinyint(1) NOT NULL DEFAULT 0,
            requested_by bigint(20) UNSIGNED DEFAULT NULL,
            requested_date datetime DEFAULT NULL,
            uploaded_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_category (category),
            KEY idx_status (status),
            KEY idx_year (year),
            KEY idx_is_incomplete (is_incomplete),
            KEY idx_expiry_date (expiry_date)
        ) $charset_collate;";

        // Tickets table
        $tables[] = "CREATE TABLE " . self::table('tickets') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            ticket_number varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            category varchar(100) DEFAULT NULL,
            type varchar(50) NOT NULL DEFAULT 'question',
            priority varchar(50) NOT NULL DEFAULT 'normal',
            status varchar(50) NOT NULL DEFAULT 'new',
            assigned_to bigint(20) UNSIGNED DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            closed_at datetime DEFAULT NULL,
            last_reply_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_ticket_number (ticket_number),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_category (category),
            KEY idx_priority (priority),
            KEY idx_type (type)
        ) $charset_collate;";

        // Ticket messages table
        $tables[] = "CREATE TABLE " . self::table('ticket_messages') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            message text NOT NULL,
            attachments text DEFAULT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            read_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ticket_id (ticket_id),
            KEY idx_user_id (user_id),
            KEY idx_is_read (is_read)
        ) $charset_collate;";

        // Invoices table
        $tables[] = "CREATE TABLE " . self::table('invoices') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            invoice_number varchar(50) NOT NULL,
            invoice_date date NOT NULL,
            due_date date DEFAULT NULL,
            year varchar(10) DEFAULT NULL,
            month varchar(20) DEFAULT NULL,
            subtotal decimal(15,2) NOT NULL DEFAULT 0.00,
            tax decimal(15,2) NOT NULL DEFAULT 0.00,
            discount decimal(15,2) NOT NULL DEFAULT 0.00,
            total_amount decimal(15,2) NOT NULL DEFAULT 0.00,
            paid_amount decimal(15,2) NOT NULL DEFAULT 0.00,
            status varchar(50) NOT NULL DEFAULT 'unpaid',
            description text DEFAULT NULL,
            file_path varchar(500) DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_invoice_number (invoice_number),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_invoice_date (invoice_date),
            KEY idx_year (year)
        ) $charset_collate;";

        // Payments table
        $tables[] = "CREATE TABLE " . self::table('payments') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            invoice_id bigint(20) UNSIGNED DEFAULT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            amount decimal(15,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(100) DEFAULT NULL,
            payment_date datetime NOT NULL,
            description text DEFAULT NULL,
            receipt_path varchar(500) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'completed',
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_invoice_id (invoice_id),
            KEY idx_payment_date (payment_date),
            KEY idx_status (status)
        ) $charset_collate;";

        // Reports table
        $tables[] = "CREATE TABLE " . self::table('reports') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            title varchar(255) NOT NULL,
            report_type varchar(100) DEFAULT NULL,
            year varchar(10) DEFAULT NULL,
            month varchar(20) DEFAULT NULL,
            period varchar(100) DEFAULT NULL,
            description text DEFAULT NULL,
            file_path varchar(500) DEFAULT NULL,
            file_name varchar(255) DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_report_type (report_type),
            KEY idx_year (year)
        ) $charset_collate;";

        // Services table (service requests)
        $tables[] = "CREATE TABLE " . self::table('services') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            service_type varchar(100) NOT NULL,
            title varchar(255) NOT NULL,
            request_data longtext DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            assigned_to bigint(20) UNSIGNED DEFAULT NULL,
            description text DEFAULT NULL,
            admin_notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_service_type (service_type),
            KEY idx_status (status)
        ) $charset_collate;";

        // Calendar events table
        $tables[] = "CREATE TABLE " . self::table('calendar') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            event_type varchar(50) NOT NULL DEFAULT 'event',
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            is_all_day tinyint(1) NOT NULL DEFAULT 0,
            location varchar(255) DEFAULT NULL,
            reminder varchar(50) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'confirmed',
            request_status varchar(50) DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_category (category),
            KEY idx_start_date (start_date),
            KEY idx_event_type (event_type),
            KEY idx_status (status)
        ) $charset_collate;";

        // Company info table
        $tables[] = "CREATE TABLE " . self::table('company_info') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            company_name varchar(255) DEFAULT NULL,
            brand_name varchar(255) DEFAULT NULL,
            logo_url varchar(500) DEFAULT NULL,
            address text DEFAULT NULL,
            registration_number varchar(100) DEFAULT NULL,
            tax_id varchar(100) DEFAULT NULL,
            vat_number varchar(100) DEFAULT NULL,
            ceo_name varchar(100) DEFAULT NULL,
            shareholders text DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            bank_name varchar(100) DEFAULT NULL,
            bank_account varchar(100) DEFAULT NULL,
            financial_contact_name varchar(100) DEFAULT NULL,
            financial_contact_phone varchar(50) DEFAULT NULL,
            financial_contact_email varchar(100) DEFAULT NULL,
            extra_fields longtext DEFAULT NULL,
            pending_changes longtext DEFAULT NULL,
            is_editable tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_user_id (user_id)
        ) $charset_collate;";

        // Notifications table
        $tables[] = "CREATE TABLE " . self::table('notifications') . " (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            type varchar(50) NOT NULL DEFAULT 'info',
            title varchar(255) NOT NULL,
            message text DEFAULT NULL,
            reference_type varchar(50) DEFAULT NULL,
            reference_id bigint(20) UNSIGNED DEFAULT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            read_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_is_read (is_read),
            KEY idx_type (type),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        foreach ($tables as $sql) {
            dbDelta($sql);
        }

        update_option('rsd_fp_db_version', self::DB_VERSION);
    }

    /**
     * Check if database tables need updating
     *
     * @return bool
     */
    public static function needs_update()
    {
        $current_version = get_option('rsd_fp_db_version', '0');
        return version_compare($current_version, self::DB_VERSION, '<');
    }

    /**
     * Get single row from table
     *
     * @param string $table
     * @param int $id
     * @return object|null
     */
    public static function get_row($table, $id)
    {
        global $wpdb;
        $table_name = self::table($table);
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id));
    }

    /**
     * Insert data into table
     *
     * @param string $table
     * @param array $data
     * @return int|false
     */
    public static function insert($table, $data)
    {
        global $wpdb;
        $table_name = self::table($table);

        $data['created_at'] = current_time('mysql');
        if (self::table_has_updated_at($table)) {
            $data['updated_at'] = current_time('mysql');
        }

        if ($wpdb->insert($table_name, $data)) {
            return $wpdb->insert_id;
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RSD-FP DB insert failed for table ' . $table . ': ' . $wpdb->last_error);
        }
        return false;
    }

    /**
     * Update table row
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return bool
     */
    public static function update($table, $data, $where)
    {
        global $wpdb;
        $table_name = self::table($table);
        if (self::table_has_updated_at($table)) {
            $data['updated_at'] = current_time('mysql');
        }
        $result = $wpdb->update($table_name, $data, $where);
        if ($result === false && (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log('RSD-FP DB update failed for table ' . $table . ': ' . $wpdb->last_error);
        }
        return $result !== false;
    }

    /**
     * Delete from table
     *
     * @param string $table
     * @param array $where
     * @return bool
     */
    public static function delete($table, $where)
    {
        global $wpdb;
        $table_name = self::table($table);
        return (bool) $wpdb->delete($table_name, $where);
    }

    /**
     * Get rows with simple where clause
     *
     * @param string $table
     * @param array $where
     * @param string $order_by
     * @param string $order
     * @param int $limit
     * @return array
     */
    public static function get_rows($table, $where = [], $order_by = 'created_at', $order = 'DESC', $limit = 0)
    {
        global $wpdb;
        $table_name = self::table($table);

        $sql = "SELECT * FROM {$table_name}";
        $where_clauses = [];
        $prepare_args = [];

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '%s'));
                    $where_clauses[] = "{$key} IN ({$placeholders})";
                    $prepare_args = array_merge($prepare_args, $value);
                } else {
                    $where_clauses[] = "{$key} = %s";
                    $prepare_args[] = $value;
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
        }

        $sql .= " ORDER BY {$order_by} {$order}";

        if ($limit > 0) {
            $sql .= " LIMIT %d";
            $prepare_args[] = $limit;
        }

        if (!empty($prepare_args)) {
            return $wpdb->get_results($wpdb->prepare($sql, ...$prepare_args));
        }

        return $wpdb->get_results($sql);
    }
}
