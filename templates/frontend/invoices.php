<?php
/**
 * Template: Frontend - Invoices & Billing Page
 *
 * @var array $invoices
 * @var array $payments
 * @var array $stats
 * @var int $client_id
 */
defined('ABSPATH') || exit;

$active = 'invoices';
$statuses = \RSD\FinancePanel\Modules\Invoices::get_statuses();
$methods = \RSD\FinancePanel\Modules\Invoices::get_payment_methods();
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">💰 Billing Status</h2>
            <div class="spacer"></div>
        </div>

        <!-- Summary top cards -->
        <div class="rsd-grid rsd-grid-4">
            <div class="rsd-stat-card stat-purple">
                <div class="stat-label">
                    Total Issued Invoices
                    <span class="stat-icon">📑</span>
                </div>
                <div class="stat-amount"><?php echo esc_html(rsd_fp_currency($stats['total_invoiced'] ?? 0)); ?></div>
                <div class="stat-change"><?php echo esc_html(count($invoices)); ?> invoices</div>
            </div>
            <div class="rsd-stat-card stat-green">
                <div class="stat-label">
                    Total Payments Made
                    <span class="stat-icon">💳</span>
                </div>
                <div class="stat-amount"><?php echo esc_html(rsd_fp_currency($stats['total_paid'] ?? 0)); ?></div>
                <div class="stat-change"><?php echo esc_html(count($payments)); ?> transactions</div>
            </div>
            <div class="rsd-stat-card stat-red">
                <div class="stat-label">
                    Your Current Outstanding Balance
                    <span class="stat-icon">⚠️</span>
                </div>
                <div class="stat-amount"><?php echo esc_html(rsd_fp_currency($stats['debt'] ?? 0)); ?></div>
                <div class="stat-change">
                    <?php echo ($stats['debt'] ?? 0) > 0
                        ? esc_html__('You have amounts due', 'rsd-finance-panel')
                        : '<span style="color:#16a34a">No debt</span>'; ?>
                </div>
            </div>
            <div class="rsd-stat-card stat-blue">
                <div class="stat-label">
                    Last Payment
                    <span class="stat-icon">🗓️</span>
                </div>
                <div class="stat-amount" style="font-size:16px">
                    <?php
                    $last = !empty($payments) ? $payments[0] : null;
                    if ($last) {
                        echo esc_html(rsd_fp_currency($last->amount ?? 0));
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="stat-change">
                    <?php
                    if ($last) {
                        echo esc_html(date_i18n('j F Y', strtotime($last->payment_date)));
                    } else {
                        echo '<span style="color:#94a3b8">' . esc_html('No payments recorded yet', 'rsd-finance-panel') . '</span>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="rsd-tabs">
            <div class="rsd-tab active" onclick="rsdTab(this,'invoices')">Invoices</div>
            <div class="rsd-tab" onclick="rsdTab(this,'payments')">Payments</div>
        </div>

        <div class="rsd-card" id="tab-invoices">
            <div class="rsd-card-header">
                <div class="rsd-card-title"><span class="rsd-card-icon">📑</span> Issued Invoices</div>
            </div>
            <div class="rsd-table-wrap">
                <table class="rsd-table">
                    <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Date</th>
                        <th>Year / Month</th>
                        <th>Payment Due</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($invoices)) : foreach ($invoices as $inv) : ?>
                        <tr>
                            <td style="font-family:monospace;font-weight:700;color:#0369a1">
                                <?php echo esc_html($inv->invoice_number); ?>
                            </td>
                            <td><?php echo esc_html(date_i18n('j F Y', strtotime($inv->invoice_date))); ?></td>
                            <td><?php echo esc_html(trim(($inv->year ?? '') . ' / ' . ($inv->month ?? ''), ' /')); ?></td>
                            <td><?php
                                if (!empty($inv->due_date)) {
                                    $overdue = strtotime($inv->due_date) < strtotime(current_time('mysql')) && $inv->status !== 'paid';
                                    $class = $overdue ? 'color:#dc2626' : '';
                                    echo '<span style="' . esc_attr($class) . '">' . esc_html(date_i18n('j F Y', strtotime($inv->due_date))) . '</span>';
                                } else {
                                    echo '<span style="color:#cbd5e1">-</span>';
                                }
                            ?></td>
                            <td class="cell-amount"><?php echo esc_html(rsd_fp_currency($inv->total_amount)); ?></td>
                            <td style="color:#16a34a;font-weight:700"><?php echo esc_html(rsd_fp_currency($inv->paid_amount)); ?></td>
                            <td><?php echo rsd_fp_status_badge($inv->status); ?></td>
                            <td>
                                <div class="cell-actions">
                                    <?php if (!empty($inv->file_path)) : ?>
                                        <a href="<?php echo esc_url($inv->file_path); ?>" target="_blank"
                                           class="rsd-btn rsd-btn-sm rsd-btn-outline">📄 Download</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="8">
                            <div class="rsd-empty"><div class="rsd-empty-icon">📑</div>
                                <h3>No invoices have been recorded for you yet</h3>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rsd-card" id="tab-payments" style="display:none">
            <div class="rsd-card-header">
                <div class="rsd-card-title"><span class="rsd-card-icon">💳</span> Payment History</div>
            </div>
            <div class="rsd-table-wrap">
                <table class="rsd-table">
                    <thead>
                    <tr>
                        <th>Transaction Number</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Related Invoice</th>
                        <th>Description</th>
                        <th>Receipt</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($payments)) : foreach ($payments as $p) : ?>
                        <tr>
                            <td style="font-family:monospace;font-weight:700;color:#0369a1">
                                <?php echo !empty($p->transaction_id) ? esc_html($p->transaction_id) : '#TR-' . esc_html($p->id); ?>
                            </td>
                            <td><?php echo esc_html(date_i18n('j F Y', strtotime($p->payment_date))); ?></td>
                            <td class="cell-amount"><?php echo esc_html(rsd_fp_currency($p->amount)); ?></td>
                            <td><?php
                                echo isset($methods[$p->payment_method])
                                    ? esc_html($methods[$p->payment_method])
                                    : esc_html('-');
                            ?></td>
                            <td><?php
                                if (!empty($p->invoice_id)) {
                                    $inv = \RSD\FinancePanel\Core\Database::get_row('invoices', $p->invoice_id);
                                    echo $inv ? '<span style="font-family:monospace">' . esc_html($inv->invoice_number) . '</span>' : '-';
                                } else {
                                    echo '<span style="color:#94a3b8">-</span>';
                                }
                            ?></td>
                            <td><?php echo !empty($p->description) ? esc_html(mb_substr($p->description, 0, 60)) : '-'; ?></td>
                            <td>
                                <?php if (!empty($p->receipt_path)) : ?>
                                    <a href="<?php echo esc_url($p->receipt_path); ?>" target="_blank"
                                       class="rsd-btn rsd-btn-sm rsd-btn-outline">View</a>
                                <?php else : ?>
                                    <span style="color:#cbd5e1">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="7">
                            <div class="rsd-empty"><div class="rsd-empty-icon">💳</div>
                                <h3>No payment transactions found</h3>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script type="text/javascript">
function rsdTab(btn, tab) {
    jQuery('.rsd-tab').removeClass('active');
    jQuery(btn).addClass('active');
    jQuery('#tab-invoices, #tab-payments').hide();
    jQuery('#tab-' + tab).fadeIn(150);
}
</script>
