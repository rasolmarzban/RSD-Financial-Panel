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
            <h1>💼 داشبورد مدیریت پرتال مالی</h1>
            <p>پنل مدیریت RSD Finance Panel — کلیه آمار و کنترل‌ها</p>
        </div>
        <div style="display:flex;gap:8px">
            <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-settings')); ?>"
               class="rsd-btn-admin secondary">⚙️ تنظیمات</a>
            <a href="<?php echo esc_url(rsd_fp_page_url('dashboard')); ?>" target="_blank"
               class="rsd-btn-admin primary">🎯 پیشنمایش پرتال کاربران</a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="rsd-admin-stats-row">
        <div class="rsd-admin-stat-card blue">
            <div class="stat-top">
                <div class="stat-label">تعداد مشتریان</div>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['total_clients'] ?? 0)); ?></div>
            <div class="stat-sub">کاربران دارای دسترسی پرتال</div>
        </div>

        <div class="rsd-admin-stat-card purple">
            <div class="stat-top">
                <div class="stat-label">مجموع فاکتورها</div>
                <div class="stat-icon">📑</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['total_invoices'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(rsd_fp_currency($stats['total_invoiced'] ?? 0)); ?> مجموع</div>
        </div>

        <div class="rsd-admin-stat-card red">
            <div class="stat-top">
                <div class="stat-label">فاکتورهای باز</div>
                <div class="stat-icon">⚠️</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['unpaid_invoices'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(rsd_fp_currency($stats['total_outstanding'] ?? 0)); ?> مانده</div>
        </div>

        <div class="rsd-admin-stat-card teal">
            <div class="stat-top">
                <div class="stat-label">مجموع دریافت‌ها</div>
                <div class="stat-icon">💰</div>
            </div>
            <div class="stat-value"><?php echo esc_html(rsd_fp_currency($stats['total_paid'] ?? 0)); ?></div>
            <div class="stat-sub">مجموع پرداختی‌ها</div>
        </div>

        <div class="rsd-admin-stat-card orange">
            <div class="stat-top">
                <div class="stat-label">تیکت‌های باز</div>
                <div class="stat-icon">🎟️</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['open_tickets'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(number_format_i18n($stats['total_tickets'] ?? 0)); ?> کل تیکت‌ها</div>
        </div>

        <div class="rsd-admin-stat-card green">
            <div class="stat-top">
                <div class="stat-label">مدارک ناقص</div>
                <div class="stat-icon">📄</div>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['incomplete_docs'] ?? 0)); ?></div>
            <div class="stat-sub"><?php echo esc_html(number_format_i18n($stats['total_documents'] ?? 0)); ?> کل مدارک</div>
        </div>
    </div>

    <!-- Two Column: Recent Tickets + Recent Invoices -->
    <div class="rsd-admin-grid cols-2">
        <div class="rsd-admin-card">
            <div class="rsd-admin-card-header">
                <div class="rsd-admin-card-title">
                    <span class="rsd-icon">🎟️</span> آخرین تیکت‌ها
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-tickets')); ?>"
                   class="rsd-btn-admin small secondary">مشاهده همه ←</a>
            </div>

            <div class="rsd-admin-table-wrap">
                <table class="rsd-admin-table">
                    <thead><tr>
                        <th>شماره</th>
                        <th>مشتری</th>
                        <th>موضوع</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                    </tr></thead>
                    <tbody>
                    <?php if (!empty($stats['recent_tickets'])) : ?>
                        <?php foreach ($stats['recent_tickets'] as $t) :
                            $user = get_user_by('id', $t->user_id);
                        ?>
                            <tr>
                                <td><span style="font-family:monospace;color:#0369a1;font-weight:700"><?php echo esc_html($t->ticket_number); ?></span></td>
                                <td><div class="rsd-user-chip">
                                    <span class="avatar"><?php echo esc_html(mb_substr($user ? $user->display_name : '؟', 0, 1)); ?></span>
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
                        <tr><td colspan="5"><div class="rsd-empty"><p>هنوز تیکتی ثبت نشده.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rsd-admin-card">
            <div class="rsd-admin-card-header">
                <div class="rsd-admin-card-title">
                    <span class="rsd-icon">📑</span> آخرین فاکتورها
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-invoices')); ?>"
                   class="rsd-btn-admin small secondary">مشاهده همه ←</a>
            </div>

            <div class="rsd-admin-table-wrap">
                <table class="rsd-admin-table">
                    <thead><tr>
                        <th>شماره</th>
                        <th>مشتری</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                    </tr></thead>
                    <tbody>
                    <?php if (!empty($stats['recent_invoices'])) : ?>
                        <?php foreach ($stats['recent_invoices'] as $inv) :
                            $user = get_user_by('id', $inv->user_id);
                        ?>
                            <tr>
                                <td><span style="font-family:monospace;color:#0369a1;font-weight:700"><?php echo esc_html($inv->invoice_number); ?></span></td>
                                <td><div class="rsd-user-chip">
                                    <span class="avatar"><?php echo esc_html(mb_substr($user ? $user->display_name : '؟', 0, 1)); ?></span>
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
                        <tr><td colspan="5"><div class="rsd-empty"><p>هنوز فاکتوری ثبت نشده.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
