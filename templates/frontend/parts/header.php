<?php

/**
 * Top header partial for user portal (with notification dropdown)
 *
 * @var WP_User $current_user
 * @var array $stats (optional)
 */
defined('ABSPATH') || exit;

$current_user = $current_user ?? wp_get_current_user();
$client_id    = get_current_user_id();
$stats        = $stats ?? null;

// Because header.php is included from EVERY portal page (not just dashboard), the
// $stats notifications list + count may be missing. Fallback to a direct query so
// the bell + dropdown always works regardless of the parent shortcode.
global $wpdb;
if (is_array($stats)) {
    $unread_notifs = $stats['notifications'] ?? null;
    $all_notif_count = $stats['all_notif_count'] ?? null;
} else {
    $unread_notifs = null;
    $all_notif_count = null;
}

if ($unread_notifs === null || $all_notif_count === null) {
    $notif_table = \RSD\FinancePanel\Core\Database::table('notifications');
    if ($all_notif_count === null) {
        $all_notif_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$notif_table} WHERE user_id = %d AND is_read = 0",
            $client_id
        ));
    }
    if ($unread_notifs === null) {
        $unread_notifs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$notif_table} WHERE user_id = %d AND is_read = 0
             ORDER BY created_at DESC LIMIT 10",
            $client_id
        ));
    }
}

/**
 * Helper: build client-portal URL for a notification row based on reference_type/id.
 *
 * @param stdClass $n notification row
 * @return string
 */
$notif_link_fn = function ($n) {
    $ref_type = (string) ($n->reference_type ?? '');
    $ref_id   = (int) ($n->reference_id ?? 0);
    switch ($ref_type) {
        case 'ticket':
            return add_query_arg(
                ['view' => 'single', 'id' => $ref_id],
                function_exists('rsd_fp_page_url') ? rsd_fp_page_url('tickets') : home_url('/finance-tickets/')
            );
        case 'invoice':
            return add_query_arg(
                ['view' => 'single', 'id' => $ref_id],
                function_exists('rsd_fp_page_url') ? rsd_fp_page_url('invoices') : home_url('/finance-invoices/')
            );
        case 'document':
            return (function_exists('rsd_fp_page_url') ? rsd_fp_page_url('documents') : home_url('/finance-documents/'));
        case 'report':
            return (function_exists('rsd_fp_page_url') ? rsd_fp_page_url('reports') : home_url('/finance-reports/'));
        case 'calendar_event':
        case 'event':
            return (function_exists('rsd_fp_page_url') ? rsd_fp_page_url('calendar') : home_url('/finance-calendar/'));
        case 'service_request':
            return (function_exists('rsd_fp_page_url') ? rsd_fp_page_url('services') : home_url('/finance-services/'));
        default:
            return home_url('/');
    }
};

/**
 * Helper: icon / type label (in English) for a notification type key.
 *
 * @param stdClass $n
 * @return array{0:string,1:string} tuple of (icon, label)
 */
$notif_meta_fn = function ($n) {
    $t = (string) ($n->type ?? '');
    switch ($t) {
        case 'ticket_created':
            return ['📝', esc_html__('New Ticket', 'rsd-finance-panel')];
        case 'ticket_replied':
            return ['💬', esc_html__('Ticket Reply', 'rsd-finance-panel')];
        case 'ticket_updated':
            return ['🔄', esc_html__('Ticket Updated', 'rsd-finance-panel')];
        case 'ticket_closed':
            return ['✅', esc_html__('Ticket Closed', 'rsd-finance-panel')];
        case 'invoice_created':
            return ['📄', esc_html__('New Invoice', 'rsd-finance-panel')];
        case 'invoice_paid':
            return ['💳', esc_html__('Invoice Payment', 'rsd-finance-panel')];
        case 'report_ready':
            return ['🗂️', esc_html__('Report Ready', 'rsd-finance-panel')];
        case 'doc_uploaded':
            return ['📂', esc_html__('Document Uploaded', 'rsd-finance-panel')];
        case 'doc_requested':
            return ['📨', esc_html__('Document Requested', 'rsd-finance-panel')];
        case 'event_approved':
            return ['✅', esc_html__('Meeting Approved', 'rsd-finance-panel')];
        case 'event_rejected':
            return ['❌', esc_html__('Meeting Rejected', 'rsd-finance-panel')];
        case 'service_created':
            return ['🏗️', esc_html__('Service Request Submitted', 'rsd-finance-panel')];
        case 'service_status':
            return ['🛠️', esc_html__('Service Status Changed', 'rsd-finance-panel')];
        default:
            return ['🔔', esc_html__('Notification', 'rsd-finance-panel')];
    }
};

$display_name = $current_user->display_name ?: esc_html('Dear User', 'rsd-finance-panel');
$initial = mb_substr($display_name, 0, 1);
$avatar_url = function_exists('rsd_fp_user_avatar_url') ? rsd_fp_user_avatar_url($current_user->ID) : '';
?>

<header class="rsd-fp-header">
    <div class="rsd-fp-user-menu">
        <div class="rsd-fp-avatar">
            <?php if ($avatar_url) : ?>
                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>">
            <?php else : ?>
                <?php echo esc_html($initial); ?>
            <?php endif; ?>
        </div>
        <div class="rsd-fp-user-info">
            <span class="rsd-fp-user-name"><?php echo esc_html($display_name); ?>
                <span class="rsd-fp-user-dropdown">▾</span>
            </span>
            <span class="rsd-fp-user-role">Financial Manager</span>
        </div>
    </div>

    <div class="rsd-fp-header-actions">
        <div class="rsd-fp-search">
            <input type="text" placeholder="Search the system ...">
            <span class="search-icon">🔍</span>
        </div>
        <div class="rsd-fp-notif-wrapper">
            <button type="button"
                class="rsd-fp-notif-btn rsd-notif-bell"
                id="rsd-notif-bell"
                aria-haspopup="dialog"
                aria-expanded="false"
                title="Notifications">
                🔔
                <?php if ($all_notif_count > 0) : ?>
                    <span class="rsd-fp-badge" id="rsd-notif-badge"><?php echo esc_html($all_notif_count > 99 ? '99+' : (string) $all_notif_count); ?></span>
                <?php else : ?>
                    <span class="rsd-fp-badge" id="rsd-notif-badge" style="display:none">0</span>
                <?php endif; ?>
            </button>

            <div class="rsd-notif-dropdown" id="rsd-notif-dropdown" style="display:none" role="dialog" aria-label="Notifications">
                <div class="rsd-notif-dropdown-head">
                    <strong><?php echo esc_html(sprintf(_n('%d unread notification', '%d unread notifications', $all_notif_count, 'rsd-finance-panel'), $all_notif_count)); ?></strong>
                    <?php if ($all_notif_count > 0) : ?>
                        <button type="button" class="rsd-notif-mark-all" data-mark-all-read title="Mark all as read">
                            Mark all as read
                        </button>
                    <?php endif; ?>
                </div>

                <div class="rsd-notif-list">
                    <?php if (!empty($unread_notifs)) : ?>
                        <?php foreach ($unread_notifs as $n) :
                            list($icon, $type_label) = $notif_meta_fn($n);
                            $link = $notif_link_fn($n);
                            $msg_snippet = '';
                            if (!empty($n->message)) {
                                $msg_plain = wp_strip_all_tags($n->message);
                                $msg_snippet = function_exists('mb_substr')
                                    ? (mb_substr($msg_plain, 0, 80, 'UTF-8') . (mb_strlen($msg_plain) > 80 ? '…' : ''))
                                    : (substr($msg_plain, 0, 80) . (strlen($msg_plain) > 80 ? '…' : ''));
                            }
                            $title_plain = $n->title ? wp_strip_all_tags($n->title) : $type_label;
                        ?>
                            <a href="<?php echo esc_url($link); ?>"
                                class="rsd-notif-item unread rsd-notif-clickable"
                                data-notif-id="<?php echo (int) $n->id; ?>"
                                data-navigate-to="<?php echo esc_url($link); ?>">
                                <div class="rsd-notif-icon" title="<?php echo esc_attr($type_label); ?>"><?php echo esc_html($icon); ?></div>
                                <div class="rsd-notif-body">
                                    <div class="rsd-notif-title">
                                        <?php echo esc_html($title_plain); ?>
                                        <span class="rsd-notif-time">
                                            <?php echo esc_html(sprintf('%s ago', human_time_diff(strtotime($n->created_at)))); ?>
                                        </span>
                                    </div>
                                    <?php if ($msg_snippet) : ?>
                                        <div class="rsd-notif-msg"><?php echo esc_html($msg_snippet); ?></div>
                                    <?php endif; ?>
                                </div>
                                <button type="button"
                                    class="rsd-notif-dismiss"
                                    data-dismiss-notif="<?php echo (int) $n->id; ?>"
                                    title="Delete/Mark as read">
                                    ✕
                                </button>
                            </a>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="rsd-notif-empty">
                            <div class="rsd-notif-empty-icon">🎉</div>
                            <h4>All notifications have been read</h4>
                            <p>New notifications are displayed here.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rsd-notif-dropdown-foot">
                    <a href="<?php echo esc_url(function_exists('rsd_fp_page_url') ? rsd_fp_page_url('tickets') : home_url('/finance-tickets/')); ?>"
                        style="font-size:12px;font-weight:600;color:#3b82f6">
                        View all messages &amp; tickets ←
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>