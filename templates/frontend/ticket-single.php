<?php
/**
 * Template: Frontend - Single Ticket View
 *
 * @var object $ticket
 * @var array $messages
 */
defined('ABSPATH') || exit;

$active = 'tickets';
$categories = \RSD\FinancePanel\Modules\Tickets::get_categories();
$priorities = \RSD\FinancePanel\Modules\Tickets::get_priorities();
if (empty($ticket)) {
    echo '<div class="rsd-alert rsd-alert-danger">تیکت یافت نشد.</div>';
    return;
}
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <div>
                <a href="<?php echo esc_url(rsd_fp_page_url('tickets')); ?>"
                   class="rsd-btn rsd-btn-sm rsd-btn-light">← بازگشت به لیست تیکت‌ها</a>
            </div>
            <div class="spacer"></div>
            <?php if ($ticket->status !== 'closed') : ?>
                <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-danger rsd-confirm"
                        data-close-ticket="<?php echo (int) $ticket->id; ?>">
                    ✕ بستن تیکت
                </button>
            <?php endif; ?>
        </div>

        <div class="rsd-card" style="margin-bottom:20px">
            <div class="rsd-grid rsd-grid-3" style="margin-bottom:0">
                <div>
                    <div style="font-size:11px;color:#94a3b8;margin-bottom:4px">شماره تیکت</div>
                    <div style="font-family:monospace;font-weight:700;color:#0369a1;font-size:15px"><?php echo esc_html($ticket->ticket_number); ?></div>
                </div>
                <div>
                    <div style="font-size:11px;color:#94a3b8;margin-bottom:4px">موضوع</div>
                    <div style="font-weight:700;color:#0f172a"><?php echo esc_html($ticket->subject); ?></div>
                </div>
                <div style="display:flex;gap:8px;align-items:flex-start;flex-direction:column">
                    <?php echo rsd_fp_status_badge($ticket->status); ?>
                    <?php
                    echo isset($priorities[$ticket->priority])
                        ? '<span class="rsd-badge rsd-badge-warning">اولویت: ' . esc_html($priorities[$ticket->priority]) . '</span>'
                        : '';
                    echo isset($categories[$ticket->category])
                        ? '<span class="rsd-badge rsd-badge-info">' . esc_html($categories[$ticket->category]) . '</span>'
                        : '';
                    ?>
                </div>
            </div>
        </div>

        <div class="rsd-card">
            <div class="rsd-card-header">
                <div class="rsd-card-title">💬 مکاتبه</div>
                <div style="font-size:12px;color:#64748b"><?php echo esc_html(count($messages ?? [])); ?> پیام</div>
            </div>

            <div class="rsd-chat" id="rsd-chat-area">
                <?php if (!empty($messages)) : foreach ($messages as $msg) :
                    $is_mine = (int) $msg->user_id === get_current_user_id();
                    $initial = mb_substr($msg->user_name ?: 'U', 0, 1);
                ?>
                    <div class="rsd-chat-msg <?php echo $is_mine ? 'mine' : 'theirs'; ?>">
                        <div class="rsd-chat-avatar"><?php echo esc_html($initial); ?></div>
                        <div>
                            <div style="font-size:10px;color:#94a3b8;margin-bottom:5px">
                                <?php echo esc_html($msg->user_name); ?>
                                · <?php echo esc_html(sprintf('%s پیش', human_time_diff(strtotime($msg->created_at)))); ?>
                            </div>
                            <div class="rsd-chat-bubble">
                                <?php echo nl2br(esc_html($msg->message)); ?>
                                <?php if (!empty($msg->attachments)) :
                                    $atts = json_decode($msg->attachments, true);
                                    if (!empty($atts)) : ?>
                                        <div class="rsd-chat-attachments">
                                            <?php foreach ((array) $atts as $att) :
                                                $url = is_array($att) ? ($att['url'] ?? '') : $att;
                                                $name = is_array($att) ? ($att['file'] ?? '') : basename($url);
                                            ?>
                                                <a href="<?php echo esc_url($url); ?>" target="_blank" class="rsd-chat-attachment">
                                                    📎 <?php echo esc_html($name); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif;
                                endif; ?>
                                <span class="rsd-chat-time"><?php echo esc_html(date_i18n('j F Y ، H:i', strtotime($msg->created_at))); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; else : ?>
                    <div class="rsd-empty"><div class="rsd-empty-icon">💭</div>
                        <p>هنوز پیامی در این تیکت ثبت نشده است.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($ticket->status !== 'closed') : ?>
            <div class="rsd-card" style="margin-top:20px">
                <div class="rsd-card-title" style="margin-bottom:14px">📝 ارسال پاسخ</div>
                <form id="rsd-reply-ticket-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket->id; ?>">
                    <div class="rsd-form-row">
                        <textarea name="message" rows="4" placeholder="پیام خود را بنویسید..." required></textarea>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;justify-content:space-between">
                        <div style="font-size:11px;color:#94a3b8">
                            <input type="file" name="ticket_attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        </div>
                        <button type="submit" class="rsd-btn rsd-btn-primary">ارسال پاسخ ➡</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </main>
</div>
