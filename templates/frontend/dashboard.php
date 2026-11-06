<?php

/**
 * Template: Frontend Dashboard (Client Portal Main Page)
 *
 * @var array $stats
 * @var WP_User $current_user
 * @var int $client_id
 */
defined('ABSPATH') || exit;

$active = 'dashboard';
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>

    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <!-- Top row: Welcome + Stats Cards (matches screenshot 4 cards + welcome) -->
        <div class="rsd-grid rsd-grid-4">
            <!-- Welcome card (spans 1 col, larger design like image) -->
            <div class="rsd-stats-welcome">
                <h2>Hello, <?php echo esc_html($current_user->display_name); ?></h2>
                <p>Welcome to the client portal.</p>
                <div class="welcome-go">→</div>
            </div>

            <!-- Stat 1: Total Payments -->
            <div class="rsd-stat-card stat-green">
                <div class="stat-label">
                    Total Payments
                    <span class="stat-icon">💳</span>
                </div>
                <div class="stat-amount"><?php echo esc_html(rsd_fp_currency($stats['total_paid'] ?? 0)); ?></div>
                <?php
                $paid_pct = $stats['paid_change_pct'] ?? null;
                $paid_arrow = $stats['up_down_paid'] ?? '';
                $paid_color = ($stats['paid_color_class'] ?? '') === 'stat-negative' ? 'color:#dc2626' : '';
                if ($paid_pct === null) {
                    echo '<div class="stat-change">—</div>';
                } else {
                    echo '<div class="stat-change" style="' . esc_attr($paid_color) . '">' . esc_html($paid_arrow . ' ' . number_format_i18n(abs($paid_pct), 0) . '% compared to last month') . '</div>';
                }
                ?>
            </div>

            <!-- Stat 2: Total Invoiced -->
            <div class="rsd-stat-card stat-purple">
                <div class="stat-label">
                    Total Invoices
                    <span class="stat-icon">📄</span>
                </div>
                <div class="stat-amount"><?php echo esc_html(rsd_fp_currency($stats['total_invoiced'] ?? 0)); ?></div>
                <?php
                $inv_pct = $stats['invoiced_change_pct'] ?? null;
                $inv_arrow = $stats['up_down_inv'] ?? '';
                $inv_color = ($stats['inv_color_class'] ?? '') === 'stat-negative' ? 'color:#dc2626' : '';
                if ($inv_pct === null) {
                    echo '<div class="stat-change">—</div>';
                } else {
                    echo '<div class="stat-change" style="' . esc_attr($inv_color) . '">' . esc_html($inv_arrow . ' ' . number_format_i18n(abs($inv_pct), 0) . '% compared to last month') . '</div>';
                }
                ?>
            </div>

            <!-- Stat 3: Debt / Outstanding -->
            <div class="rsd-stat-card stat-red">
                <div class="stat-label">
                    Current Balance Due
                    <span class="stat-icon">⚠️</span>
                </div>
                <div class="stat-amount"><?php echo esc_html(rsd_fp_currency($stats['debt'] ?? 0)); ?></div>
                <div class="stat-change">—</div>
            </div>
        </div>

        <!-- Second row: 3 columns -->
        <div class="rsd-grid rsd-grid-3">
            <!-- Left: Incomplete Documents -->
            <div class="rsd-card">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">📄</span>
                        Incomplete Documents
                    </div>
                    <?php if (!empty($stats['incomplete_list'])) : ?>
                        <span class="rsd-card-count-badge"><?php echo esc_html(count($stats['incomplete_list'])); ?> items</span>
                    <?php else : ?>
                        <span class="rsd-card-count-badge" style="background:#dcfce7;color:#16a34a">0</span>
                    <?php endif; ?>
                </div>

                <div class="rsd-list">
                    <?php if (!empty($stats['incomplete_list'])) : ?>
                        <?php foreach (array_slice($stats['incomplete_list'], 0, 3) as $doc) : ?>
                            <div class="rsd-list-item">
                                <div class="rsd-list-icon">📃</div>
                                <div class="rsd-list-main">
                                    <div class="rsd-list-title"><?php echo esc_html($doc->title); ?></div>
                                    <div class="rsd-list-meta">
                                        Period: <?php echo esc_html($doc->period || $doc->year ? trim(($doc->year ? $doc->year . ' - ' : '') . ($doc->period ?? '')) : '-'); ?>
                                        | <?php echo esc_html(sprintf('%s ago', human_time_diff(strtotime($doc->created_at)))); ?>
                                        <?php if (!empty($doc->expiry_date)) : ?>
                                            | <span style="color:#dc2626;font-weight:700">Due: <?php echo esc_html(rsd_fp_jalali_date('j F Y', $doc->expiry_date)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="rsd-list-actions">
                                    <span class="rsd-badge rsd-badge-danger">Upload Document</span>
                                    <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-outline" data-modal="rsd-upload-modal" data-doc-id="<?php echo (int) $doc->id; ?>">📥</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="rsd-empty" style="padding:28px 10px">
                            <div class="rsd-empty-icon">✅</div>
                            <h3>All documents uploaded</h3>
                            <p>No documents need to be submitted.</p>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(rsd_fp_page_url('documents') . '?tab=incomplete'); ?>"
                        style="display:inline-block;margin-top:6px;font-size:12px;color:#3b82f6;font-weight:600">
                        View all ←
                    </a>
                </div>
            </div>

            <!-- Middle: Upcoming Events / Important Deadlines -->
            <div class="rsd-card">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">📅</span>
                        Important Deadlines
                    </div>
                </div>

                <div class="rsd-list">
                    <?php if (!empty($stats['upcoming_events'])) : ?>
                        <?php foreach (array_slice($stats['upcoming_events'], 0, 4) as $ev) : ?>
                            <?php
                            $colors = \RSD\FinancePanel\Modules\Calendar::get_category_color($ev->category);
                            $cats = \RSD\FinancePanel\Modules\Calendar::get_categories();
                            $month = date_i18n('j F', strtotime($ev->start_date));
                            ?>
                            <div class="rsd-list-item">
                                <div class="rsd-list-icon" style="background:#fff3e0;color:#ef6c00;">📌</div>
                                <div class="rsd-list-main">
                                    <div class="rsd-list-title">
                                        <?php
                                        echo isset($cats[$ev->category])
                                            ? '<span class="rsd-badge" style="background:#f3e8ff;color:#7c3aed;margin-left:6px">' . esc_html($cats[$ev->category]) . '</span>'
                                            : '';
                                        ?>
                                        <?php echo esc_html($ev->title); ?>
                                    </div>
                                    <div class="rsd-list-meta">
                                        <?php echo esc_html($month); ?>
                                        <?php echo $ev->event_type === 'meeting_request' && !empty($ev->request_status) ? ' | ' . esc_html__('Status: Pending approval', 'rsd-finance-panel') : ''; ?>
                                    </div>
                                </div>
                                <div class="rsd-list-actions" style="flex-direction:column;gap:4px">
                                    <?php
                                    $date = strtotime($ev->start_date);
                                    $today = strtotime(current_time('mysql'));
                                    $diff = (int) round(($date - $today) / (60 * 60 * 24));
                                    ?>
                                    <span class="rsd-badge rsd-badge-info"><?php echo esc_html($diff <= 0 ? 'Today' : $diff . ' days left'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="rsd-empty" style="padding:28px 10px">
                            <div class="rsd-empty-icon">🗓️</div>
                            <h3>No upcoming deadlines</h3>
                            <p>Upcoming events are displayed here.</p>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(rsd_fp_page_url('calendar')); ?>"
                        style="display:inline-block;margin-top:6px;font-size:12px;color:#3b82f6;font-weight:600">
                        View calendar ←
                    </a>
                </div>
            </div>

            <!-- Right: Recent Messages / Latest Conversations -->
            <div class="rsd-card">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">💬</span>
                        New Messages
                    </div>
                    <?php if (!empty($stats['unread_tickets'])) : ?>
                        <span class="rsd-card-count-badge"><?php echo esc_html($stats['unread_tickets']); ?> new</span>
                    <?php endif; ?>
                </div>

                <div class="rsd-list">
                    <?php if (!empty($stats['latest_msgs'])) : ?>
                        <?php foreach (array_slice($stats['latest_msgs'], 0, 3) as $msg) :
                            $msg_plain = wp_strip_all_tags($msg->message ?? '');
                            $msg_snippet = function_exists('mb_substr')
                                ? (mb_substr($msg_plain, 0, 60, 'UTF-8') . (mb_strlen($msg_plain) > 60 ? '…' : ''))
                                : (substr($msg_plain, 0, 60) . (strlen($msg_plain) > 60 ? '…' : ''));
                            $sender_label = (user_can((int)($msg->user_id ?? 0), 'manage_options'))
                                ? esc_html__('Company Support', 'rsd-finance-panel')
                                : (get_user_by('id', (int)($msg->user_id ?? 0))->display_name ?? esc_html__('User', 'rsd-finance-panel'));
                            $title = !empty($msg->subject) ? esc_html($msg->subject) : sprintf(esc_html__('Ticket #%s', 'rsd-finance-panel'), $msg->ticket_number ?? '');
                        ?>
                            <div class="rsd-list-item">
                                <div class="rsd-list-icon">
                                    <?php
                                    $is_admin_sender = user_can((int)($msg->user_id ?? 0), 'manage_options');
                                    $is_incoming = isset($msg->is_incoming) ? (bool)$msg->is_incoming : !$is_admin_sender;
                                    echo $is_admin_sender ? '🛡️' : '👤';
                                    ?>
                                </div>
                                <div class="rsd-list-main">
                                    <div class="rsd-list-title">
                                        <?php echo wp_kses_post($title); ?>
                                        <span style="font-weight:400;color:#64748b;font-size:11px;margin-left:6px">
                                            <?php echo esc_html(sprintf('%s ago', human_time_diff(strtotime($msg->created_at)))); ?>
                                        </span>
                                        <?php if ($is_incoming && empty($msg->is_read)) : ?>
                                            <span class="rsd-badge" style="background:#fee2e2;color:#dc2626;margin-left:6px">New</span>
                                        <?php endif; ?>
                                        <?php if (!$is_incoming) : ?>
                                            <span class="rsd-badge" style="background:#e0f2fe;color:#0369a1;margin-left:6px">Sent</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rsd-list-meta" title="<?php echo esc_attr($sender_label . ': ' . $msg_plain); ?>">
                                        <span style="color:#3b82f6;font-weight:600;margin-left:6px"><?php echo esc_html($sender_label); ?>:</span>
                                        <?php echo esc_html($msg_snippet); ?>
                                    </div>
                                </div>
                                <div class="rsd-list-actions">
                                    <a href="<?php echo esc_url(rsd_fp_page_url('tickets') . '?view=single&id=' . (int) $msg->ticket_id); ?>"
                                        class="rsd-btn rsd-btn-sm rsd-btn-light">👁</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="rsd-empty" style="padding:28px 10px">
                            <div class="rsd-empty-icon">💬</div>
                            <h3>You have no new messages</h3>
                            <p>Recent conversations are displayed here.</p>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(rsd_fp_page_url('tickets')); ?>"
                        style="display:inline-block;margin-top:6px;font-size:12px;color:#3b82f6;font-weight:600">
                        View all messages ←
                    </a>
                </div>
            </div>
        </div>

        <!-- Third row: 3 columns (calendar moved below to its own full-width row) -->
        <div class="rsd-grid rsd-grid-3">
            <!-- Quick upload document -->
            <div class="rsd-card">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">☁️</span>
                        Quick Document Upload
                    </div>
                </div>

                <form id="rsd-quick-upload-form" method="post" enctype="multipart/form-data">
                    <div class="rsd-form-row">
                        <select name="category" required>
                            <option value="">Select category</option>
                            <?php foreach (\RSD\FinancePanel\Modules\Documents::get_categories() as $k => $v) : ?>
                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsd-form-row">
                        <select name="year">
                            <option value="">Select year</option>
                            <?php foreach (rsd_fp_jalali_years(6, 0) as $y) : ?>
                                <option value="<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="rsd-form-row">
                        <div class="rsd-upload-area">
                            <div class="upload-icon">☁️</div>
                            <div class="upload-text">Select or drag a file</div>
                            <div class="upload-hint">PDF, JPG, PNG, DOC, XLS (max 20 MB)</div>
                            <input type="file" name="document_file" style="display:none"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar,.txt,.csv">
                        </div>
                    </div>

                    <button type="submit" class="rsd-btn rsd-btn-primary" style="width:100%">
                        Upload File
                    </button>
                </form>
            </div>

            <!-- Recent Reports -->
            <div class="rsd-card">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">🗂️</span>
                        Latest Reports
                    </div>
                    <a href="<?php echo esc_url(rsd_fp_page_url('reports')); ?>" style="font-size:11px;color:#94a3b8">View all</a>
                </div>

                <div class="rsd-list">
                    <?php if (!empty($stats['recent_reports'])) : ?>
                        <?php foreach ($stats['recent_reports'] as $rep) :
                            $types = \RSD\FinancePanel\Modules\Reports::get_types();
                            $label = $types[$rep->report_type] ?? esc_html('Report', 'rsd-finance-panel');
                        ?>
                            <div class="rsd-list-item" style="padding:10px">
                                <div class="rsd-list-icon" style="width:32px;height:32px;border-radius:8px;font-size:14px">📄</div>
                                <div class="rsd-list-main">
                                    <div class="rsd-list-title" style="font-size:12px"><?php echo esc_html($rep->title); ?></div>
                                    <div class="rsd-list-meta">
                                        <?php echo esc_html($label); ?>
                                        <span style="color:#94a3b8;margin:0 4px">•</span>
                                        <?php echo esc_html(date_i18n('j F', strtotime($rep->created_at))); ?>
                                    </div>
                                </div>
                                <div class="rsd-list-actions">
                                    <?php if (!empty($rep->file_path)) : ?>
                                        <a href="<?php echo esc_url($rep->file_path); ?>" target="_blank"
                                            class="rsd-btn rsd-btn-sm rsd-btn-outline">⬇️</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="rsd-empty" style="padding:18px 10px">
                            <div class="rsd-empty-icon">📄</div>
                            <p>No reports have been recorded for you yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Billing Status (Donut) -->
            <div class="rsd-card">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">💼</span>
                        Billing Status
                    </div>
                </div>

                <?php
                $buckets = $stats['invoice_buckets'] ?? ['paid' => 0, 'partial' => 0, 'unpaid' => 0, 'overdue' => 0];
                $paid_sum    = (float) ($buckets['paid'] ?? 0);
                $partial_sum = (float) ($buckets['partial'] ?? 0);
                $unpaid_sum  = (float) ($buckets['unpaid'] ?? 0);
                $overdue_sum = (float) ($buckets['overdue'] ?? 0);
                $inv_total = $paid_sum + $partial_sum + $unpaid_sum + $overdue_sum;

                $radius = 50;
                $circumference = 2 * M_PI * $radius;

                $segments = [];
                if ($inv_total > 0) {
                    $segments[] = ['color' => '#10b981', 'len' => $circumference * ($paid_sum / $inv_total)];
                    $segments[] = ['color' => '#f59e0b', 'len' => $circumference * ($partial_sum / $inv_total)];
                    $segments[] = ['color' => '#ef4444', 'len' => $circumference * ($unpaid_sum / $inv_total)];
                    $segments[] = ['color' => '#a855f7', 'len' => $circumference * ($overdue_sum / $inv_total)];
                }
                $cumulative = 0;
                ?>

                <div class="rsd-donut-wrap">
                    <div class="rsd-donut">
                        <svg viewBox="0 0 120 120">
                            <circle class="donut-bg" cx="60" cy="60" r="<?php echo esc_attr($radius); ?>"></circle>
                            <?php foreach ($segments as $seg) : ?>
                                <?php
                                $len = (float) $seg['len'];
                                if ($len <= 0) continue;
                                $dasharray = number_format($len, 5, '.', '') . ' ' . number_format($circumference, 5, '.', '');
                                $dashoffset = -$cumulative;
                                $cumulative += $len;
                                ?>
                                <circle class="donut-fg" cx="60" cy="60" r="<?php echo esc_attr($radius); ?>"
                                    stroke="<?php echo esc_attr($seg['color']); ?>"
                                    stroke-dasharray="<?php echo esc_attr($dasharray); ?>"
                                    stroke-dashoffset="<?php echo esc_attr(number_format($dashoffset, 5, '.', '')); ?>"></circle>
                            <?php endforeach; ?>
                        </svg>
                    </div>

                    <div class="rsd-legend">
                        <div class="rsd-legend-item">
                            <span class="rsd-legend-dot" style="background:#10b981"></span>
                            Paid
                            <span style="font-weight:700;color:#0f172a;margin-left:auto"><?php echo esc_html(rsd_fp_currency($paid_sum)); ?></span>
                        </div>
                        <div class="rsd-legend-item">
                            <span class="rsd-legend-dot" style="background:#f59e0b"></span>
                            Partially Paid
                            <span style="font-weight:700;color:#0f172a;margin-left:auto"><?php echo esc_html(rsd_fp_currency($partial_sum)); ?></span>
                        </div>
                        <div class="rsd-legend-item">
                            <span class="rsd-legend-dot" style="background:#ef4444"></span>
                            Unpaid
                            <span style="font-weight:700;color:#0f172a;margin-left:auto"><?php echo esc_html(rsd_fp_currency($unpaid_sum)); ?></span>
                        </div>
                        <div class="rsd-legend-item">
                            <span class="rsd-legend-dot" style="background:#a855f7"></span>
                            Overdue
                            <span style="font-weight:700;color:#0f172a;margin-left:auto"><?php echo esc_html(rsd_fp_currency($overdue_sum)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fourth row: Full-width single column Calendar (expanded) -->
        <div class="rsd-card" style="margin-top:24px">
            <div class="rsd-card-header">
                <div class="rsd-card-title">
                    <span class="rsd-card-icon">📆</span>
                    Work Calendar
                </div>
                <a href="<?php echo esc_url(rsd_fp_page_url('calendar')); ?>" style="font-size:12px;color:#3b82f6;font-weight:600;text-decoration:none">
                    View calendar page ←
                </a>
            </div>

            <?php
            // Render FULL-SIZE Gregorian calendar with navigation via GET params
            $today_g = current_time('Y-m-d');
            $today_parts = rsd_fp_gregorian_to_jalali_parts(
                (int) substr($today_g, 0, 4),
                (int) substr($today_g, 5, 2),
                (int) substr($today_g, 8, 2)
            );
            $j_y_cur = (int) ($today_parts[0] ?? 1400);
            $j_m_cur = (int) ($today_parts[1] ?? 1);
            $j_d_cur = (int) ($today_parts[2] ?? 1);

            // Accept navigation via GET: mini_y (year) & mini_m (month 1-12)
            $j_y = isset($_GET['mini_y']) ? (int) $_GET['mini_y'] : $j_y_cur;
            $j_m = isset($_GET['mini_m']) ? (int) $_GET['mini_m'] : $j_m_cur;
            if ($j_y < 1380) $j_y = 1380;
            if ($j_y > 1420) $j_y = 1420;
            if ($j_m < 1) $j_m = 1;
            if ($j_m > 12) $j_m = 12;
            $is_cur_month = ($j_y === $j_y_cur && $j_m === $j_m_cur);

            // Compute prev/next month for navigation links
            $prev_y = $j_y;
            $prev_m = $j_m - 1;
            if ($prev_m < 1) {
                $prev_m = 12;
                $prev_y = $j_y - 1;
            }
            $next_y = $j_y;
            $next_m = $j_m + 1;
            if ($next_m > 12) {
                $next_m = 1;
                $next_y = $j_y + 1;
            }

            // Build self URL preserving other GET params
            $self_uri = remove_query_arg(['mini_y', 'mini_m', 'cal_y', 'cal_m'], $_SERVER['REQUEST_URI'] ?? '');

            $j_month_labels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $j_days_in_month = ($j_m <= 6) ? 31 : (($j_m <= 11) ? 30 : 29);
            // Convert month 1st to Greg to calculate weekday offset
            $j1_g = rsd_fp_parse_jalali_to_gregorian(sprintf('%04d/%02d/01', $j_y, $j_m));
            $j1_w = (int) date('w', strtotime($j1_g . ' 00:00:00'));
            $weekdays = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            // Shift to Saturday-start week (Saturday = 0).
            // PHP date('w'): Sun=0..Sat=6 → start offset = ((j1_w + 1) mod 7)
            $start_offset = ($j1_w + 1) % 7;

            // Lookup table: (Y,M,D) => events for the cell
            $mini_events = $stats['mini_calendar_events'] ?? [];
            $day_map = [];
            $cats_all = \RSD\FinancePanel\Modules\Calendar::get_categories();
            foreach ($mini_events as $mev) {
                $gd = $mev->start_date ?? '';
                if ('' === $gd) continue;
                $g_ts = strtotime($gd . ' 00:00:00');
                if (!$g_ts) continue;
                $parts = rsd_fp_gregorian_to_jalali_parts((int)date('Y', $g_ts), (int)date('n', $g_ts), (int)date('j', $g_ts));
                $key = (int)$parts[0] . '-' . (int)$parts[1] . '-' . (int)$parts[2];
                $day_map[$key][] = $mev;
            }
            ?>

            <div class="rsd-calendar-header" style="padding:14px 18px;margin-bottom:10px">
                <div>
                    <a href="<?php echo esc_url(add_query_arg(['mini_y' => $prev_y, 'mini_m' => $prev_m], $self_uri)); ?>"
                        class="rsd-cal-nav-btn" title="Previous month"
                        style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#f1f5f9;color:#0f172a;text-decoration:none;font-weight:700;font-size:20px">‹</a>
                    <a href="<?php echo esc_url(add_query_arg(['mini_y' => $next_y, 'mini_m' => $next_m], $self_uri)); ?>"
                        class="rsd-cal-nav-btn" title="Next month"
                        style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#f1f5f9;color:#0f172a;text-decoration:none;font-weight:700;font-size:20px;margin-left:6px">›</a>
                </div>
                <div class="rsd-cal-title" style="font-size:19px;font-weight:800">
                    <?php echo esc_html($j_month_labels[$j_m - 1] . ' ' . $j_y);
                    if (!$is_cur_month) {
                        echo ' <a href="' . esc_url(add_query_arg(['mini_y' => $j_y_cur, 'mini_m' => $j_m_cur], $self_uri)) . '"'
                            . ' style="font-size:12px;background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:14px;text-decoration:none;margin-left:10px;font-weight:600">Back to today</a>';
                    } ?>
                </div>
                <div class="rsd-cal-view-switcher">
                    <span class="rsd-cal-view-btn active">Monthly</span>
                </div>
            </div>

            <div class="rsd-cal-grid" style="border:none;border-radius:10px;margin:0 6px 6px">
                <div class="rsd-cal-weekdays" style="background:#fff;font-size:13px">
                    <?php foreach ($weekdays as $wd) : ?>
                        <div style="padding:10px 6px"><?php echo esc_html($wd); ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="rsd-cal-days">
                    <?php
                    for ($i = 0; $i < $start_offset; $i++) echo '<div class="rsd-cal-day other-month"></div>';
                    for ($d = 1; $d <= $j_days_in_month; $d++) :
                        $is_today = $is_cur_month && ($d === $j_d_cur);
                        $key = $j_y . '-' . $j_m . '-' . $d;
                        $day_evs = $day_map[$key] ?? [];
                    ?>
                        <div class="rsd-cal-day <?php echo $is_today ? 'today' : ''; ?>"
                            style="min-height:108px;padding:6px 8px"
                            title="<?php echo esc_attr($j_y . '/' . $j_m . '/' . $d); ?>">
                            <span class="rsd-cal-num" style="font-size:15px;font-weight:700"><?php echo esc_html($d); ?></span>
                            <?php
                            // Up to 5 events now (full-size dashboard calendar)
                            $sorted_evs = $day_evs;
                            usort($sorted_evs, function ($a, $b) {
                                $rank = function ($e) {
                                    if (!empty($e->is_incomplete)) return 0;
                                    if (!empty($e->days_left) && $e->days_left <= 7 && $e->days_left >= 0) return 1;
                                    if (!empty($e->is_past_expiry)) return 2;
                                    return 3;
                                };
                                return $rank($a) <=> $rank($b);
                            });
                            $ev_limit = 5;
                            $shown = 0;
                            foreach ($sorted_evs as $sev) {
                                if ($shown >= $ev_limit) break;
                                $color = \RSD\FinancePanel\Modules\Calendar::get_category_color($sev->category ?? 'deadline');
                                $is_doc = ($sev->source ?? '') === 'document';
                                $style = 'background:' . esc_attr($color) . ';font-size:11px;padding:3px 7px;line-height:1.5';
                                if (!empty($sev->is_past_expiry)) {
                                    $style .= 'text-decoration:line-through;opacity:.75;';
                                }
                                $title_short = $sev->title ?? '';
                                $shorten_by = 24;
                                if (function_exists('mb_substr') && mb_strlen($title_short, 'UTF-8') > $shorten_by) {
                                    $title_short = mb_substr($title_short, 0, $shorten_by, 'UTF-8') . '…';
                                } elseif (strlen($title_short) > $shorten_by) {
                                    $title_short = substr($title_short, 0, $shorten_by) . '…';
                                }
                                echo '<div class="rsd-cal-event" style="' . $style . '" title="' . esc_attr(($cats_all[$sev->category] ?? '') . ' - ' . ($sev->title ?? '')) . '">';
                                if ($is_doc) echo '📎 ';
                                echo esc_html($title_short);
                                echo '</div>';
                                $shown++;
                            }
                            if (count($sorted_evs) > $ev_limit) {
                                echo '<div style="font-size:10px;color:#64748b;margin-top:2px">+' . (count($sorted_evs) - $ev_limit) . ' more</div>';
                            }
                            ?>
                        </div>
                    <?php
                    endfor;
                    $last_pos = ($start_offset + $j_days_in_month) % 7;
                    if ($last_pos > 0) {
                        for ($i = $last_pos; $i < 7; $i++) echo '<div class="rsd-cal-day other-month"></div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Legend for calendar categories -->
            <?php
            $legend_cats = \RSD\FinancePanel\Modules\Calendar::get_categories();
            $legend_colors = [];
            foreach ($legend_cats as $lk => $lv) {
                $legend_colors[$lk] = \RSD\FinancePanel\Modules\Calendar::get_category_color($lk);
            }
            ?>
            <div style="padding:10px 16px 14px;display:flex;flex-wrap:wrap;gap:10px 18px;border-top:1px solid #f1f5f9;margin-top:8px">
                <div style="font-size:12px;color:#334155;font-weight:600">Color legend:</div>
                <?php foreach ($legend_cats as $lk => $lv) : ?>
                    <div style="display:inline-flex;align-items:center;gap:6px;font-size:11.5px;color:#475569">
                        <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?php echo esc_attr($legend_colors[$lk]); ?>"></span>
                        <?php echo esc_html($lv); ?>
                    </div>
                <?php endforeach; ?>
                <div style="display:inline-flex;align-items:center;gap:6px;font-size:11.5px;color:#475569">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:4px;background:#0f172a;color:#fff;font-size:10px;font-weight:800">22</span>
                    Current day
                </div>
            </div>
        </div>

        <!-- Footer quick actions (matches screenshot bottom bar) -->
        <div class="rsd-fp-footer">
            <div class="rsd-fp-footer-actions">
                <a href="#" class="rsd-fp-footer-action" data-modal="rsd-services-modal">
                    <span>🏗️</span> Submit company request
                </a>
                <a href="#" class="rsd-fp-footer-action" data-modal="rsd-event-modal">
                    <span>👤</span> Issue payslip
                </a>
                <a href="<?php echo esc_url(rsd_fp_page_url('documents')); ?>" class="rsd-fp-footer-action">
                    <span>📂</span> Get tax number
                </a>
                <a href="#" class="rsd-fp-footer-action" data-modal="rsd-ticket-modal">
                    <span>💳</span> Register brand
                </a>
                <a href="#" class="rsd-fp-footer-action" data-modal="rsd-meeting-modal">
                    <span>🏛️</span> Submit company request
                </a>
                <a href="<?php echo esc_url(rsd_fp_page_url('calendar')); ?>" class="rsd-fp-footer-action">
                    <span>📅</span> Submit meeting request
                </a>
            </div>
            <div class="rsd-fp-footer-info">
                Designed with <span class="heart">♥</span> | Version <?php echo esc_html(RSD_FP_VERSION); ?>
            </div>
        </div>

    </main>
</div>

<!-- Modals -->
<?php
// Upload Document
?>
<div class="rsd-modal-backdrop" id="rsd-upload-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">Upload New Document</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-upload-doc-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="rsd_doc_id" id="rsd_doc_id" value="">
                <!-- Current document preview (only visible when re-uploading an incomplete doc) -->
                <div class="rsd-current-doc-preview" style="display:none;padding:12px 14px;border:1px dashed #fcd34d;border-radius:12px;background:#fffbeb;margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                        <span style="display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:50%;background:#fef3c7;color:#b45309;font-size:16px">ℹ️</span>
                        <div>
                            <div style="font-weight:700;color:#92400e;font-size:13px">Current incomplete document info</div>
                            <div style="color:#92400e;font-size:11px;margin-top:2px">If needed, you can correct the fields below or just select a new file and submit.</div>
                        </div>
                    </div>
                    <div class="rsd-current-doc-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;font-size:12.5px;color:#334155">
                        <div><span style="color:#64748b">Title:</span> <strong class="rsd-cur-title"></strong></div>
                        <div><span style="color:#64748b">Category:</span> <strong class="rsd-cur-category"></strong></div>
                        <div><span style="color:#64748b">Year:</span> <strong class="rsd-cur-year"></strong></div>
                        <div><span style="color:#64748b">Month:</span> <strong class="rsd-cur-month"></strong></div>
                        <div style="grid-column:1/-1"><span style="color:#64748b">Period:</span> <strong class="rsd-cur-period"></strong></div>
                        <div><span style="color:#64748b">Expiry date:</span> <strong style="color:#dc2626" class="rsd-cur-expiry"></strong></div>
                        <div><span style="color:#64748b">Uploaded:</span> <strong class="rsd-cur-uploaded"></strong></div>
                        <div style="grid-column:1/-1"><span style="color:#64748b">Description:</span> <strong class="rsd-cur-description"></strong></div>
                    </div>
                    <div class="rsd-cur-file-row" style="display:flex;align-items:center;gap:8px;margin-top:12px;padding:8px 10px;border-radius:10px;background:white;border:1px solid #fde68a">
                        <span style="font-size:20px">📎</span>
                        <div style="flex:1;min-width:0">
                            <div class="rsd-cur-filename" style="font-weight:600;color:#0f172a;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
                            <div class="rsd-cur-filesize" style="color:#64748b;font-size:11px;margin-top:2px"></div>
                        </div>
                        <a href="#" target="_blank" class="rsd-cur-download rsd-btn rsd-btn-sm rsd-btn-outline">📂 Download current file</a>
                    </div>
                </div>

                <div class="rsd-form-row">
                    <label>Document Title <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="e.g., Monthly payslip">
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>Category <span class="required">*</span></label>
                        <select name="category" required>
                            <?php foreach (\RSD\FinancePanel\Modules\Documents::get_categories() as $k => $v) : ?>
                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsd-form-row">
                        <label>Year</label>
                        <select name="year">
                            <option value="">-</option>
                            <?php foreach (rsd_fp_jalali_years(7, 1) as $y) : ?>
                                <option value="<?php echo esc_attr($y); ?>"><?php echo esc_html($y); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>Month / Period</label>
                        <select name="month">
                            <option value="">-</option>
                            <?php foreach (\RSD\FinancePanel\Modules\Reports::get_months() as $k => $v) : ?>
                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsd-form-row">
                        <label>Related Period</label>
                        <input type="text" name="period" placeholder="e.g., First two months of the year">
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>Expiry Date / Deadline</label>
                    <?php rsd_fp_render_jalali_triple('expiry_date', 'rsd-fe-dash-doc-expiry', false, ''); ?>
                    <span style="color:#94a3b8;font-size:11px;margin-inline-end:4px">Select the deadline year, month, and day (optional)</span>
                </div>
                <div class="rsd-form-row">
                    <label>Description</label>
                    <textarea name="description" placeholder="More details (optional)"></textarea>
                </div>
                <div class="rsd-form-row">
                    <label class="rsd-file-label">File
                        <span class="rsd-file-label-hint" style="color:#64748b;font-size:11px;margin-inline-start:6px">(to update metadata only, you can submit without selecting a new file)</span>
                    </label>
                    <div class="rsd-upload-area">
                        <div class="upload-icon">📤</div>
                        <div class="upload-text">Click or drag a file</div>
                        <div class="upload-hint">PDF | JPG | PNG | DOC | XLS up to 20 MB — if no new file is selected, only the document info is updated</div>
                        <input type="file" name="document_file" style="display:none"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar,.txt,.csv">
                    </div>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button type="button" class="rsd-btn rsd-btn-light" data-close-modal>Cancel</button>
            <button type="submit" form="rsd-upload-doc-form" class="rsd-btn rsd-btn-primary">Save and Upload</button>
        </div>
    </div>
</div>

<!-- Ticket Modal -->
<div class="rsd-modal-backdrop" id="rsd-ticket-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">Submit New Message or Ticket</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-new-ticket-form">
                <div class="rsd-form-row">
                    <label>Request Type <span class="required">*</span></label>
                    <select name="type" required>
                        <option value="question">Submit a Question</option>
                        <option value="issue">Submit a Ticket or Issue</option>
                    </select>
                </div>
                <div class="rsd-form-row">
                    <label>Subject <span class="required">*</span></label>
                    <select name="category" required>
                        <?php foreach (\RSD\FinancePanel\Modules\Tickets::get_categories() as $k => $v) : ?>
                            <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsd-form-row">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" name="subject" required placeholder="Write a summary of the subject">
                </div>
                <div class="rsd-form-row">
                    <label>Priority</label>
                    <select name="priority">
                        <?php foreach (\RSD\FinancePanel\Modules\Tickets::get_priorities() as $k => $v) : ?>
                            <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsd-form-row">
                    <label>Your Message <span class="required">*</span></label>
                    <textarea name="message" required placeholder="Write the full details..." rows="5"></textarea>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button type="button" class="rsd-btn rsd-btn-light" data-close-modal>Cancel</button>
            <button type="submit" form="rsd-new-ticket-form" class="rsd-btn rsd-btn-primary">Send Message</button>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="rsd-modal-backdrop" id="rsd-event-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">Add New Deadline</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-event-form">
                <div class="rsd-form-row">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="e.g., Submit tax documents">
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>Category</label>
                        <select name="category">
                            <?php foreach (\RSD\FinancePanel\Modules\Calendar::get_categories() as $k => $v) : ?>
                                <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="rsd-form-row">
                        <label>Date <span class="required">*</span></label>
                        <?php rsd_fp_render_jalali_triple('start_date', 'rsd-fe-dash-event', true, ''); ?>
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button type="button" class="rsd-btn rsd-btn-light" data-close-modal>Cancel</button>
            <button type="submit" form="rsd-event-form" class="rsd-btn rsd-btn-primary">Save Deadline</button>
        </div>
    </div>
</div>

<div class="rsd-modal-backdrop" id="rsd-meeting-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">Meeting Request</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-meeting-form">
                <div class="rsd-form-row">
                    <label>Meeting Subject <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="Write the meeting subject">
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>Proposed Date <span class="required">*</span></label>
                        <?php rsd_fp_render_jalali_triple('start_date', 'rsd-fe-dash-meeting', true, ''); ?>
                    </div>
                    <div class="rsd-form-row">
                        <label>Proposed Time</label>
                        <input type="time" name="start_time">
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="More details..."></textarea>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button type="button" class="rsd-btn rsd-btn-light" data-close-modal>Cancel</button>
            <button type="submit" form="rsd-meeting-form" class="rsd-btn rsd-btn-primary">Submit Meeting Request</button>
        </div>
    </div>
</div>