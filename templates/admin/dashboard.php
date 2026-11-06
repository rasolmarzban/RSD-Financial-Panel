<?php
/**
 * Admin Template: Dashboard
 *
 * @var array $stats
 */
defined('ABSPATH') || exit;
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>💼 Financial Portal Management Dashboard</h1>
            <p>RSD Finance Panel admin panel — all statistics and controls</p>
        </div>
        <div style="display:flex;gap:8px">
            <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-settings')); ?>"
               class="rsd-btn-admin secondary">⚙️ Settings</a>
            <a href="<?php echo esc_url(rsd_fp_page_url('dashboard')); ?>" target="_blank"
               class="rsd-btn-admin primary">🎯 User Portal Preview</a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="rsd-admin-stats-row">
        <div class="rsd-admin-stat-card blue">
            <div class="stat-top">
                <div class="stat-label">Number of Clients</div>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['total_clients'] ?? 0)); ?></div>
            <div class="stat-sub">Users with portal access</div>
        </div>

        <div class="rsd-admin-stat-card purple">
            <div class="stat-top">
                <div class="stat-label">Total Invoices</div>
                <div class="stat-icon">📑</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['total_invoices'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(rsd_fp_currency($stats['total_invoiced'] ?? 0)); ?> Total</div>
        </div>

        <div class="rsd-admin-stat-card red">
            <div class="stat-top">
                <div class="stat-label">Open Invoices</div>
                <div class="stat-icon">⚠️</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['unpaid_invoices'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(rsd_fp_currency($stats['total_outstanding'] ?? 0)); ?> Balance</div>
        </div>

        <div class="rsd-admin-stat-card teal">
            <div class="stat-top">
                <div class="stat-label">Total Receipts</div>
                <div class="stat-icon">💰</div>
            </div>
            <div class="stat-value"><?php echo esc_html(rsd_fp_currency($stats['total_paid'] ?? 0)); ?></div>
            <div class="stat-sub">Total payments</div>
        </div>

        <div class="rsd-admin-stat-card orange">
            <div class="stat-top">
                <div class="stat-label">Open Tickets</div>
                <div class="stat-icon">🎟️</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['open_tickets'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(number_format_i18n($stats['total_tickets'] ?? 0)); ?> Total Tickets</div>
        </div>

        <div class="rsd-admin-stat-card green">
            <div class="stat-top">
                <div class="stat-label">Incomplete Documents</div>
                <div class="stat-icon">📄</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['incomplete_docs'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(number_format_i18n($stats['total_documents'] ?? 0)); ?> Total Documents</div>
        </div>
    </div>

    <!-- Two Column: Recent Tickets + Recent Invoices -->
    <div class="rsd-admin-grid cols-2">
        <div class="rsd-admin-card">
            <div class="rsd-admin-card-header">
                <div class="rsd-admin-card-title">
                    <span class="rsd-icon">🎟️</span> Latest Tickets
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-tickets')); ?>"
                   class="rsd-btn-admin small secondary">View all ←</a>
            </div>

            <div class="rsd-admin-table-wrap">
                <table class="rsd-admin-table">
                    <thead><tr>
                        <th>Number</th>
                        <th>Client</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr></thead>
                    <tbody>
                    <?php if (!empty($stats['recent_tickets'])) : ?>
                        <?php foreach ($stats['recent_tickets'] as $t) :
                            $user = get_user_by('id', $t->user_id);
                        ?>
                            <tr>
                                <td><span style="font-family:monospace;color:#0369a1;font-weight:700"><?php echo esc_html($t->ticket_number); ?></span></td>
                                <td><div class="rsd-user-chip">
                                    <span class="avatar"><?php echo esc_html(mb_substr($user ? $user->display_name : '?', 0, 1)); ?></span>
                                    <?php echo esc_html($user ? $user->display_name : '#'.$t->user_id); ?>
                                </div></td>
                                <td><a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-tickets&view=single&id=' . (int)$t->id)); ?>" style="color:#0f172a;font-weight:600">
                                    <?php echo esc_html(mb_substr($t->subject, 0, 35)); ?>
                                </a></td>
                                <td><?php
                                    $map = ['new' => 'blue', 'in_progress' => 'yellow', 'waiting' => 'purple', 'completed' => 'green', 'closed' => 'gray'];
                                    $c = $map[$t->status] ?? 'gray';
                                    $s = \RSD\FinancePanel\Modules\Tickets::get_statuses()[$t->status] ?? $t->status;
                                    echo '<span class="rsd-admin-badge ' . $c . '">' . esc_html($s) . '</span>';
                                ?></td>
                                <td style="font-size:12px;color:#64748b"><?php echo esc_html(date_i18n('j F Y', strtotime($t->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="5"><div class="rsd-empty"><p>No tickets recorded yet.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rsd-admin-card">
            <div class="rsd-admin-card-header">
                <div class="rsd-admin-card-title">
                    <span class="rsd-icon">📑</span> Latest Invoices
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-invoices')); ?>"
                   class="rsd-btn-admin small secondary">View all ←</a>
            </div>

            <div class="rsd-admin-table-wrap">
                <table class="rsd-admin-table">
                    <thead><tr>
                        <th>Number</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr></thead>
                    <tbody>
                    <?php if (!empty($stats['recent_invoices'])) : ?>
                        <?php foreach ($stats['recent_invoices'] as $inv) :
                            $user = get_user_by('id', $inv->user_id);
                        ?>
                            <tr>
                                <td><span style="font-family:monospace;color:#0369a1;font-weight:700"><?php echo esc_html($inv->invoice_number); ?></span></td>
                                <td><div class="rsd-user-chip">
                                    <span class="avatar"><?php echo esc_html(mb_substr($user ? $user->display_name : '?', 0, 1)); ?></span>
                                    <?php echo esc_html($user ? $user->display_name : '#'.$inv->user_id); ?>
                                </div></td>
                                <td class="amount"><?php echo esc_html(rsd_fp_currency($inv->total_amount)); ?></td>
                                <td><?php
                                    $map = ['paid' => 'green', 'partial' => 'yellow', 'unpaid' => 'red', 'overdue' => 'red'];
                                    $c = $map[$inv->status] ?? 'gray';
                                    $s = \RSD\FinancePanel\Modules\Invoices::get_statuses()[$inv->status] ?? $inv->status;
                                    echo '<span class="rsd-admin-badge ' . $c . '">' . esc_html($s) . '</span>';
                                ?></td>
                                <td style="font-size:12px;color:#64748b"><?php echo esc_html(date_i18n('j F Y', strtotime($inv->invoice_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="5"><div class="rsd-empty"><p>No invoices recorded yet.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
