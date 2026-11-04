<?php
/**
 * Admin Template: Tickets Management
 *
 * @var array $tickets
 * @var string $status
 * @var string $category
 * @var string $priority
 * @var int $client_id
 * @var string $s
 * @var array $summary
 */
defined('ABSPATH') || exit;

use RSD\FinancePanel\Modules\Tickets;

$statuses   = Tickets::get_statuses();
$categories = Tickets::get_categories();
$priorities = Tickets::get_priorities();
$status_colors = [
    'new'         => 'blue',
    'in_progress' => 'yellow',
    'waiting'     => 'purple',
    'completed'   => 'green',
    'closed'      => 'gray',
];
$priority_icons = [
    'low'    => ['⚪', 'gray'],
    'normal' => ['🔵', 'blue'],
    'high'   => ['🟠', 'orange'],
    'urgent' => ['🔴', 'red'],
];

$base_url = admin_url('admin.php?page=rsd-fp-tickets');
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>🎟️ مدیریت تیکت‌ها</h1>
            <p>پاسخگویی به تیکت‌های مشتریان و پیگیری وضعیت درخواست‌ها</p>
        </div>
    </div>

    <!-- Stats Pills -->
    <div class="rsd-admin-pills-row">
        <a href="<?php echo esc_url(add_query_arg(['status' => 'all', 'category' => null, 'priority' => null, 'client_id' => null, 's' => null], $base_url)); ?>"
           class="rsd-pill <?php echo ($status ?? 'all') === 'all' ? 'active' : ''; ?>">
            <span class="pill-count"><?php echo (int) ($summary['all'] ?? 0); ?></span>
            <span class="pill-label">همه</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg(['status' => 'new'], $base_url)); ?>"
           class="rsd-pill blue <?php echo $status === 'new' ? 'active' : ''; ?>">
            <span class="pill-count"><?php echo (int) ($summary['new'] ?? 0); ?></span>
            <span class="pill-label">جدید</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg(['status' => 'in_progress'], $base_url)); ?>"
           class="rsd-pill yellow <?php echo $status === 'in_progress' ? 'active' : ''; ?>">
            <span class="pill-count"><?php echo (int) ($summary['in_progress'] ?? 0); ?></span>
            <span class="pill-label">در حال بررسی</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg(['status' => 'waiting'], $base_url)); ?>"
           class="rsd-pill purple <?php echo $status === 'waiting' ? 'active' : ''; ?>">
            <span class="pill-count"><?php echo (int) ($summary['waiting'] ?? 0); ?></span>
            <span class="pill-label">منتظر مشتری</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg(['status' => 'completed'], $base_url)); ?>"
           class="rsd-pill green <?php echo $status === 'completed' ? 'active' : ''; ?>">
            <span class="pill-count"><?php echo (int) ($summary['completed'] ?? 0); ?></span>
            <span class="pill-label">انجام‌شده</span>
        </a>
        <a href="<?php echo esc_url(add_query_arg(['status' => 'closed'], $base_url)); ?>"
           class="rsd-pill gray <?php echo $status === 'closed' ? 'active' : ''; ?>">
            <span class="pill-count"><?php echo (int) ($summary['closed'] ?? 0); ?></span>
            <span class="pill-label">بسته‌شده</span>
        </a>
    </div>

    <div class="rsd-admin-filters">
        <form method="get">
            <input type="hidden" name="page" value="rsd-fp-tickets">
            <?php if (($status ?? 'all') !== 'all') : ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
            <?php endif; ?>

            <input type="search" name="s" value="<?php echo esc_attr($s ?? ''); ?>"
                   placeholder="🔎 جستجو در موضوع / شماره تیکت / نام مشتری..."
                   style="min-width:320px">

            <select name="category">
                <option value="all">همه دسته‌بندی‌ها</option>
                <?php foreach ($categories as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected(($category ?? 'all'), $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="priority">
                <option value="all">همه اولویت‌ها</option>
                <?php foreach ($priorities as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected(($priority ?? 'all'), $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php
            $clients = get_users(['role__in' => ['rsd_client', 'administrator']]);
            ?>
            <select name="client_id">
                <option value="0">همه مشتریان</option>
                <?php foreach ($clients as $c) : ?>
                    <option value="<?php echo (int)$c->ID; ?>" <?php selected(($client_id ?? 0), $c->ID); ?>>
                        <?php echo esc_html($c->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="rsd-btn-admin small primary">اعمال فیلتر</button>
            <?php if (($status ?? 'all') !== 'all' || ($category ?? 'all') !== 'all' || ($priority ?? 'all') !== 'all' || !empty($s) || !empty($client_id)) : ?>
                <a href="<?php echo esc_url($base_url); ?>"
                   class="rsd-btn-admin small secondary">پاک کردن</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="rsd-admin-card">
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table widefat">
                <thead><tr>
                    <th style="width:110px">شماره تیکت</th>
                    <th>موضوع</th>
                    <th>مشتری</th>
                    <th>دسته‌بندی</th>
                    <th>اولویت</th>
                    <th>وضعیت</th>
                    <th>آخرین فعالیت</th>
                    <th style="width:140px">عملیات</th>
                </tr></thead>
                <tbody>
                <?php if (!empty($tickets)) : foreach ($tickets as $t) :
                    $sc = $status_colors[$t->status] ?? 'gray';
                    $pi = $priority_icons[$t->priority] ?? ['⚪', 'gray'];
                    $single_url = admin_url('admin.php?page=rsd-fp-tickets&view=single&id=' . (int)$t->id);
                    ?>
                    <tr>
                        <td><span style="font-family:monospace;font-weight:700;color:#0369a1">
                            #<?php echo esc_html($t->ticket_number); ?>
                        </span></td>
                        <td>
                            <a href="<?php echo esc_url($single_url); ?>"
                               style="color:#0f172a;font-weight:600;text-decoration:none;display:block;max-width:420px">
                                <?php echo esc_html($t->subject); ?>
                            </a>
                        </td>
                        <td>
                            <div class="rsd-user-chip">
                                <?php echo get_avatar($t->user_id, 28); ?>
                                <div style="display:flex;flex-direction:column;gap:2px">
                                    <span style="font-weight:600"><?php echo esc_html($t->client_name ?? 'کاربر #' . $t->user_id); ?></span>
                                    <span style="color:#64748b;font-size:11px"><?php echo esc_html(get_userdata($t->user_id)->user_email ?? ''); ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?php echo isset($categories[$t->category]) ? esc_html($categories[$t->category]) : esc_html($t->category); ?></td>
                        <td>
                            <span class="rsd-admin-badge <?php echo $pi[1]; ?>" title="<?php echo esc_attr($priorities[$t->priority] ?? $t->priority); ?>">
                                <?php echo $pi[0]; ?>
                                <?php echo esc_html($priorities[$t->priority] ?? $t->priority); ?>
                            </span>
                        </td>
                        <td>
                            <span class="rsd-admin-badge <?php echo $sc; ?>">
                                <?php echo esc_html($statuses[$t->status] ?? $t->status); ?>
                            </span>
                        </td>
                        <td style="color:#64748b;font-size:12px">
                            <?php
                            $when = !empty($t->last_reply_at) ? $t->last_reply_at : $t->created_at;
                            echo esc_html(date_i18n('j F Y – H:i', strtotime($when)));
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($single_url); ?>"
                               class="rsd-btn-admin small primary">پاسخ</a>
                            <a href="<?php echo esc_url(get_edit_user_link($t->user_id)); ?>"
                               class="rsd-btn-admin small secondary" target="_blank" rel="noopener">مشتری</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="8"><div class="rsd-empty">
                        <div class="rsd-empty-icon">🎟️</div>
                        <h3>هنوز تیکتی وجود ندارد</h3>
                        <p>تیکت‌های ثبت‌شده توسط مشتریان در این جدول نمایش داده می‌شوند.</p>
                    </div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
