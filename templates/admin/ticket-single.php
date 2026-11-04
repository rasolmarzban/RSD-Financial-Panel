<?php

/**
 * Admin Template: Single Ticket View (Chat + Reply)
 *
 * @var object $ticket
 * @var array $messages
 * @var WP_User|null $client
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
$is_closed = $ticket && in_array($ticket->status, ['closed', 'completed'], true);

$back_url = admin_url('admin.php?page=rsd-fp-tickets');
if (isset($_GET['return_status'])) {
    $back_url = add_query_arg(['status' => sanitize_key($_GET['return_status'])], $back_url);
}
?>
<div class="rsd-admin-wrap">
    <?php if (!$ticket) : ?>
        <div class="rsd-admin-card">
            <div class="rsd-empty">
                <div class="rsd-empty-icon">🎟️</div>
                <h3>تیکت پیدا نشد</h3>
                <p>شناسه تیکت نامعتبر است یا حذف شده است.</p>
                <a href="<?php echo esc_url($back_url); ?>" class="rsd-btn-admin primary">بازگشت به لیست تیکت‌ها</a>
            </div>
        </div>
</div>
<?php return;
    endif; ?>

<!-- Header Bar -->
<div class="rsd-admin-header">
    <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap">
            <span style="font-family:monospace;font-weight:700;color:#0369a1;font-size:18px">
                #<?php echo esc_html($ticket->ticket_number); ?>
            </span>
            <?php $sc = $status_colors[$ticket->status] ?? 'gray'; ?>
            <span class="rsd-admin-badge <?php echo $sc; ?>" style="font-size:13px;padding:5px 12px">
                <?php echo esc_html($statuses[$ticket->status] ?? $ticket->status); ?>
            </span>
            <?php $pi = $priority_icons[$ticket->priority] ?? ['⚪', 'gray']; ?>
            <span class="rsd-admin-badge <?php echo $pi[1]; ?>" style="font-size:13px;padding:5px 12px">
                <?php echo $pi[0]; ?> <?php echo esc_html($priorities[$ticket->priority] ?? $ticket->priority); ?>
            </span>
            <span class="rsd-admin-badge purple" style="font-size:13px;padding:5px 12px">
                <?php echo isset($categories[$ticket->category]) ? esc_html($categories[$ticket->category]) : esc_html($ticket->category); ?>
            </span>
        </div>
        <h1 style="margin:0;font-size:24px;line-height:1.4;color:#0f172a">
            <?php echo esc_html($ticket->subject); ?>
        </h1>
        <div style="margin-top:8px;display:flex;gap:18px;flex-wrap:wrap;color:#475569;font-size:13px">
            <span>👤 <strong style="color:#0f172a"><?php echo esc_html($client ? $client->display_name : 'کاربر #' . $ticket->user_id); ?></strong></span>
            <span>📧 <?php echo esc_html($client ? $client->user_email : '—'); ?></span>
            <span>📅 ایجاد شده در <strong><?php echo esc_html(date_i18n('j F Y ساعت H:i', strtotime($ticket->created_at))); ?></strong></span>
            <?php if (!empty($ticket->last_reply_at)) : ?>
                <span>💬 آخرین پاسخ در <strong><?php echo esc_html(date_i18n('j F Y ساعت H:i', strtotime($ticket->last_reply_at))); ?></strong></span>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
        <a href="<?php echo esc_url($back_url); ?>" class="rsd-btn-admin secondary">← بازگشت</a>
        <a href="<?php echo esc_url(get_edit_user_link($ticket->user_id)); ?>" target="_blank" rel="noopener"
            class="rsd-btn-admin secondary">👥 پروفایل مشتری</a>
        <a href="<?php echo esc_url(add_query_arg(['view' => 'client', 'id' => $ticket->user_id], admin_url('admin.php?page=rsd-fp-clients'))); ?>"
            class="rsd-btn-admin secondary">🔗 پرتال مشتری</a>
        <?php if (!$is_closed) : ?>
            <button type="button" class="rsd-btn-admin danger" id="rsd-close-ticket-btn"
                data-ticket-id="<?php echo (int) $ticket->id; ?>">✖ بستن تیکت</button>
        <?php endif; ?>
    </div>
</div>

<!-- Status change row -->
<div class="rsd-admin-card" style="padding:14px 20px">
    <form method="post" id="rsd-ticket-quick-change" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin:0">
        <?php wp_nonce_field('rsd_fp_nonce', 'nonce'); ?>
        <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket->id; ?>">
        <div style="display:flex;align-items:center;gap:8px">
            <label for="q_status" style="font-weight:600;color:#0f172a">تغییر وضعیت:</label>
            <select name="status" id="q_status">
                <?php foreach ($statuses as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($ticket->status, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <label for="q_priority" style="font-weight:600;color:#0f172a">تغییر اولویت:</label>
            <select name="priority" id="q_priority">
                <?php foreach ($priorities as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($ticket->priority, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <label for="q_category" style="font-weight:600;color:#0f172a">تغییر دسته‌بندی:</label>
            <select name="category" id="q_category">
                <?php foreach ($categories as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($ticket->category, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="rsd-btn-admin small primary" id="rsd-apply-quick">اعمال تغییرات</button>
    </form>
</div>

<div class="rsd-admin-grid cols-3" style="align-items:stretch">

    <!-- LEFT: MESSAGES CHAT -->
    <div style="grid-column:span 2;display:flex;flex-direction:column;gap:16px">
        <div class="rsd-admin-card" style="flex:1;display:flex;flex-direction:column">
            <div class="rsd-admin-card-header" style="margin-bottom:16px">
                <div class="rsd-admin-card-title">
                    <span class="rsd-icon">💬</span>
                    مکالمه (<?php echo (int) count($messages); ?> پیام)
                </div>
                <button type="button" class="rsd-btn-admin small secondary" id="rsd-refresh-messages">🔄 به‌روزرسانی</button>
            </div>

            <div id="rsd-ticket-messages" class="rsd-ticket-chat"
                style="overflow-y:auto;max-height:560px;padding:14px;display:flex;flex-direction:column;gap:16px;background:linear-gradient(180deg,#f8fafc 0%, #fefefe 100%);border-radius:10px;border:1px solid #e2e8f0">
                <?php if (empty($messages)) : ?>
                    <div class="rsd-empty" style="padding:40px 20px">
                        <div class="rsd-empty-icon">💬</div>
                        <h3>هنوز پیامی ثبت نشده</h3>
                    </div>
                    <?php else :
                    $cur_user = wp_get_current_user();
                    foreach ($messages as $m) :
                        $is_admin_reply = user_can((int)$m->user_id, 'manage_options');
                        $is_me = (int)$m->user_id === (int)$cur_user->ID;
                        $msg_user = $m->user_id === (int)$ticket->user_id ? $client : get_user_by('id', $m->user_id);
                        $align = $is_admin_reply ? 'right' : 'left';
                        $user_label = $msg_user ? $msg_user->display_name : 'کاربر #' . $m->user_id;
                        $role_label = $is_admin_reply ? 'مدیر شرکت' : 'مشتری';
                        $bubble_bg = $is_admin_reply ? 'linear-gradient(135deg,#6366f1 0%, #8b5cf6 100%)' : '#ffffff';
                        $bubble_color = $is_admin_reply ? '#ffffff' : '#0f172a';
                        $meta_color = $is_admin_reply ? 'rgba(255,255,255,.75)' : '#64748b';
                        $attachments = !empty($m->attachments) ? json_decode($m->attachments, true) : [];
                    ?>
                        <div class="rsd-chat-row" style="display:flex;gap:12px;justify-content:<?php echo $align === 'right' ? 'flex-end' : 'flex-start'; ?>;align-items:flex-start">
                            <?php if ($align === 'left') : ?>
                                <div style="flex-shrink:0"><?php echo get_avatar($m->user_id, 40); ?></div>
                            <?php endif; ?>
                            <div style="max-width:72%;display:flex;flex-direction:column;gap:6px;align-items:<?php echo $align === 'right' ? 'flex-end' : 'flex-start'; ?>">
                                <div style="font-size:12px;color:#64748b;font-weight:600">
                                    <span><?php echo esc_html($user_label); ?></span>
                                    <span style="margin:0 6px;color:#cbd5e1">•</span>
                                    <span><?php echo esc_html($role_label); ?></span>
                                    <span style="margin:0 6px;color:#cbd5e1">•</span>
                                    <span><?php echo esc_html(date_i18n('j F Y – H:i', strtotime($m->created_at))); ?></span>
                                </div>
                                <div style="padding:12px 16px;border-radius:14px;border:1px solid <?php echo $is_admin_reply ? 'rgba(99,102,241,.25)' : '#e2e8f0'; ?>;background:<?php echo $bubble_bg; ?>;color:<?php echo $bubble_color; ?>;box-shadow:0 1px 2px rgba(15,23,42,.06);line-height:1.8;white-space:pre-wrap;word-break:break-word">
                                    <?php echo wp_kses_post(nl2br(make_clickable($m->message))); ?>
                                </div>
                                <?php if (!empty($attachments)) : ?>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px">
                                        <?php foreach ($attachments as $att) :
                                            $url = is_array($att) ? ($att['url'] ?? '') : $att;
                                            $name = is_array($att) ? ($att['file'] ?? basename($url)) : basename($url);
                                        ?>
                                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"
                                                class="rsd-btn-admin small secondary" style="font-size:12px;padding:4px 10px">
                                                📎 <?php echo esc_html($name); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($align === 'right') : ?>
                                <div style="flex-shrink:0"><?php echo get_avatar($m->user_id, 40); ?></div>
                            <?php endif; ?>
                        </div>
                <?php endforeach;
                endif; ?>
            </div>

            <?php if (!$is_closed) : ?>
                <hr style="border:0;border-top:1px dashed #e2e8f0;margin:16px 0 14px">
                <form id="rsd-ticket-reply-form" style="display:flex;flex-direction:column;gap:10px"
                    method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('rsd_fp_nonce', 'nonce'); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket->id; ?>">
                    <textarea name="message" rows="4" required
                        placeholder="✍️ پاسخ خود را بنویسید... (در صورت تمایل می‌توانید فایل نیز پیوست کنید)"
                        style="width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:12px 14px;line-height:1.8;resize:vertical;min-height:110px"></textarea>
                    <div style="display:flex;align-items:center;gap:12px;justify-content:space-between;flex-wrap:wrap">
                        <div style="display:flex;align-items:center;gap:14px">
                            <label class="rsd-attach-wrap" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;cursor:pointer;color:#475569">
                                <span>📎 پیوست فایل</span>
                                <input type="file" name="ticket_attachment" style="display:none"
                                    id="rsd-ticket-attachment">
                            </label>
                            <span id="rsd-att-name" style="color:#64748b;font-size:12px"></span>
                        </div>
                        <div style="display:flex;gap:10px;align-items:center">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#475569">
                                <input type="checkbox" name="as_client" value="1" id="rsd-as-client">
                                پس از ارسال، تیکت را «منتظر پاسخ مشتری» کن (پیش‌فرض)
                            </label>
                            <button type="submit" class="rsd-btn-admin primary">
                                ➤ ارسال پاسخ
                            </button>
                        </div>
                    </div>
                </form>
            <?php else : ?>
                <div style="background:#f1f5f9;padding:16px;border-radius:10px;border:1px solid #e2e8f0;color:#475569;text-align:center">
                    این تیکت در وضعیت <strong><?php echo esc_html($statuses[$ticket->status] ?? $ticket->status); ?></strong> قرار دارد و امکان ارسال پاسخ جدید وجود ندارد.
                    <button type="button" class="rsd-btn-admin small primary" style="margin-right:12px"
                        onclick="document.getElementById('q_status').value='in_progress';document.getElementById('rsd-apply-quick').click()">
                        باز کردن مجدد تیکت
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: SIDEBAR -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Client info -->
        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="margin-bottom:14px">
                <span class="rsd-icon">👤</span> اطلاعات مشتری
            </div>
            <?php if ($client) : ?>
                <div style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:8px 4px 14px;border-bottom:1px dashed #e2e8f0;margin-bottom:14px">
                    <?php echo get_avatar($client->ID, 72); ?>
                    <div style="text-align:center">
                        <div style="font-weight:700;color:#0f172a;font-size:16px"><?php echo esc_html($client->display_name); ?></div>
                        <div style="color:#64748b;font-size:13px">@<?php echo esc_html($client->user_login); ?></div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;color:#334155">
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <span style="color:#64748b">📧 ایمیل:</span>
                        <a href="mailto:<?php echo esc_attr($client->user_email); ?>" style="font-weight:600"><?php echo esc_html($client->user_email); ?></a>
                    </div>
                    <?php if (!empty($client->user_url)) : ?>
                        <div style="display:flex;justify-content:space-between;gap:10px">
                            <span style="color:#64748b">🌐 وب‌سایت:</span>
                            <a href="<?php echo esc_url($client->user_url); ?>" target="_blank" rel="noopener" style="font-weight:600"><?php echo esc_html($client->user_url); ?></a>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <span style="color:#64748b">📅 ثبت‌نام:</span>
                        <span style="font-weight:600"><?php echo esc_html(date_i18n('j F Y', strtotime($client->user_registered))); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:10px">
                        <span style="color:#64748b">🆔 شناسه:</span>
                        <span style="font-weight:600">#<?php echo (int) $client->ID; ?></span>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
                    <a href="<?php echo esc_url(add_query_arg(['view' => 'client', 'id' => $client->ID], admin_url('admin.php?page=rsd-fp-clients'))); ?>"
                        class="rsd-btn-admin small primary" style="flex:1;justify-content:center">پرتفویل مالی</a>
                    <a href="<?php echo esc_url(get_edit_user_link($client->ID)); ?>" target="_blank" rel="noopener"
                        class="rsd-btn-admin small secondary" style="flex:1;justify-content:center">ویرایش کاربر</a>
                </div>
                <a href="<?php echo esc_url(add_query_arg(['status' => 'all', 'client_id' => $client->ID], admin_url('admin.php?page=rsd-fp-tickets'))); ?>"
                    class="rsd-btn-admin small secondary" style="margin-top:8px;width:100%;justify-content:center">
                    🎟️ همه تیکت‌های این مشتری
                </a>
            <?php else : ?>
                <div class="rsd-empty" style="padding:24px 8px">
                    <p>کاربر یافت نشد.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activity Summary -->
        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="margin-bottom:14px">
                <span class="rsd-icon">📊</span> خلاصه فعالیت
            </div>
            <?php
            global $wpdb;
            $t_table = \RSD\FinancePanel\Core\Database::table('tickets');
            $d_table = \RSD\FinancePanel\Core\Database::table('documents');
            $i_table = \RSD\FinancePanel\Core\Database::table('invoices');
            $uid = (int) $ticket->user_id;
            $user_tickets  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t_table} WHERE user_id=%d", $uid));
            $user_open     = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t_table} WHERE user_id=%d AND status IN ('new','in_progress','waiting')", $uid));
            $user_docs     = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE user_id=%d", $uid));
            $user_invoices = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i_table} WHERE user_id=%d", $uid));
            $user_outstanding = (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_amount - paid_amount),0) FROM {$i_table} WHERE user_id=%d", $uid));
            ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="rsd-mini-stat purple">
                    <div class="num"><?php echo (int)$user_tickets; ?></div>
                    <div class="lbl">کل تیکت‌ها</div>
                </div>
                <div class="rsd-mini-stat blue">
                    <div class="num"><?php echo (int)$user_open; ?></div>
                    <div class="lbl">باز</div>
                </div>
                <div class="rsd-mini-stat teal">
                    <div class="num"><?php echo (int)$user_docs; ?></div>
                    <div class="lbl">مدارک</div>
                </div>
                <div class="rsd-mini-stat orange">
                    <div class="num"><?php echo (int)$user_invoices; ?></div>
                    <div class="lbl">فاکتورها</div>
                </div>
            </div>
            <div style="margin-top:12px;padding:12px 14px;border-radius:10px;background:linear-gradient(135deg,#fef3c7 0%, #fef9c3 100%);border:1px solid #fde68a;color:#78350f">
                <div style="font-size:12px;opacity:.85">💸 مجموع بدهی مشتری</div>
                <div style="font-size:18px;font-weight:800;margin-top:4px">
                    <?php echo esc_html(rsd_fp_currency($user_outstanding)); ?>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="margin-bottom:14px">
                <span class="rsd-icon">⚡</span> عملیات سریع
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <a href="<?php echo esc_url(add_query_arg(['client_id' => $uid], admin_url('admin.php?page=rsd-fp-documents'))); ?>"
                    class="rsd-btn-admin small secondary">📁 مشاهده مدارک مشتری</a>
                <a href="<?php echo esc_url(add_query_arg(['view' => 'single', 'id' => $uid, 'tab' => 'invoices'], admin_url('admin.php?page=rsd-fp-clients'))); ?>"
                    class="rsd-btn-admin small secondary">📑 فاکتورهای مشتری</a>
                <a href="<?php echo esc_url(add_query_arg(['client_id' => $uid], admin_url('admin.php?page=rsd-fp-calendar'))); ?>"
                    class="rsd-btn-admin small secondary">📅 رویدادها و جلسات</a>
                <button type="button" class="rsd-btn-admin small secondary" id="rsd-new-ticket-user-btn"
                    data-user-id="<?php echo (int) $uid; ?>">
                    🎟️ ثبت تیکت جدید برای این مشتری
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Ticket quick actions JS -->
<script type="text/javascript">
    jQuery(function($) {
        var nonce = '<?php echo wp_create_nonce('rsd_fp_nonce'); ?>';
        var ajax_url = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        var ticketId = <?php echo (int) $ticket->id; ?>;

        $('#rsd-ticket-attachment').on('change', function() {
            var f = this.files && this.files[0];
            $('#rsd-att-name').text(f ? f.name + ' (' + (f.size / 1024).toFixed(1) + ' KB)' : '');
        });

        // Ticket reply form
        var $replyForm = $('#rsd-ticket-reply-form');
        $replyForm.on('submit', function(e) {
            e.preventDefault();
            var $btn = $replyForm.find('[type="submit"]');
            var origText = $btn.text();
            $btn.prop('disabled', true).text('⏳ در حال ارسال...');

            var formData = new FormData(this);
            formData.append('action', 'rsd_fp_reply_ticket');
            formData.append('nonce', nonce);

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    $btn.prop('disabled', false).text(origText);
                    if (res && res.success) {
                        $replyForm.find('textarea').val('');
                        $('#rsd-ticket-attachment').val('');
                        $('#rsd-att-name').text('');
                        setTimeout(function() {
                            location.reload();
                        }, 400);
                    } else {
                        alert((res && res.data && res.data.message) || 'خطا در ارسال پاسخ');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text(origText);
                    alert('ارتباط با سرور قطع شد.');
                }
            });
        });

        // Close ticket
        $('#rsd-close-ticket-btn').on('click', function() {
            if (!confirm('آیا از بستن این تیکت اطمینان دارید؟')) return;
            var $b = $(this);
            $b.prop('disabled', true).text('...');
            $.post(ajax_url, {
                action: 'rsd_fp_close_ticket',
                ticket_id: ticketId,
                nonce: nonce
            }, function(res) {
                if (res && res.success) {
                    setTimeout(function() {
                        location.reload();
                    }, 300);
                } else {
                    alert((res && res.data && res.data.message) || 'خطا');
                    $b.prop('disabled', false).text('✖ بستن تیکت');
                }
            }, 'json').fail(function() {
                alert('ارتباط قطع شد');
                $b.prop('disabled', false).text('✖ بستن تیکت');
            });
        });

        // Quick status/priority/category change
        $('#rsd-ticket-quick-change').on('submit', function(e) {
            e.preventDefault();
            var btn = $('#rsd-apply-quick');
            btn.prop('disabled', true).text('⏳ در حال ثبت...');
            var data = $(this).serializeArray();
            data.push({
                name: 'action',
                value: 'rsd_fp_admin_update_ticket'
            });
            $.post(ajax_url, $.param(data), function(res) {
                btn.prop('disabled', false).text('اعمال تغییرات');
                if (res && res.success) {
                    setTimeout(function() {
                        location.reload();
                    }, 250);
                } else {
                    alert((res && res.data && res.data.message) || 'خطا در اعمال تغییرات');
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).text('اعمال تغییرات');
                alert('ارتباط با سرور قطع شد');
            });
        });

        // Refresh messages
        $('#rsd-refresh-messages').on('click', function() {
            var $b = $(this);
            var orig = $b.text();
            $b.prop('disabled', true).text('...');
            $.post(ajax_url, {
                action: 'rsd_fp_get_ticket_messages',
                ticket_id: ticketId,
                nonce: nonce
            }, function(res) {
                $b.prop('disabled', false).text(orig);
                if (res && res.success && res.data && res.data.messages) {
                    location.reload();
                }
            }, 'json');
        });
    });
</script>