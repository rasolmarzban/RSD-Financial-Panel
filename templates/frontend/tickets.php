<?php
/**
 * Template: Frontend - Tickets List Page
 *
 * @var array $tickets
 * @var int $client_id
 */
defined('ABSPATH') || exit;

$active = 'tickets';
$statuses = \RSD\FinancePanel\Modules\Tickets::get_statuses();
$categories = \RSD\FinancePanel\Modules\Tickets::get_categories();
$priorities = \RSD\FinancePanel\Modules\Tickets::get_priorities();
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">💬 پیام‌ها و تیکت‌ها</h2>
            <div class="spacer"></div>
            <button type="button" class="rsd-btn rsd-btn-primary" data-modal="rsd-new-ticket-modal">
                ➕ ثبت پیام / تیکت جدید
            </button>
        </div>

        <div class="rsd-tabs">
            <div class="rsd-tab active">همه تیکت‌ها (<?php echo esc_html(count($tickets)); ?>)</div>
            <div class="rsd-tab">باز (<?php
                echo esc_html(count(array_filter($tickets, fn($t) => in_array($t->status, ['new', 'in_progress', 'waiting']))));
            ?>)</div>
            <div class="rsd-tab">خوانده‌نشده</div>
            <div class="rsd-tab">بسته‌شده</div>
        </div>

        <div class="rsd-card">
            <div class="rsd-table-wrap">
                <table class="rsd-table">
                    <thead>
                    <tr>
                        <th>شماره تیکت</th>
                        <th>موضوع</th>
                        <th>دسته‌بندی</th>
                        <th>اولویت</th>
                        <th>تاریخ ثبت</th>
                        <th>آخرین پاسخ</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($tickets)) : foreach ($tickets as $t) : ?>
                        <tr>
                            <td style="font-family:monospace;font-weight:700;color:#0369a1">
                                <?php echo esc_html($t->ticket_number); ?>
                            </td>
                            <td style="font-weight:600;color:#0f172a"><?php echo esc_html($t->subject); ?></td>
                            <td><?php echo isset($categories[$t->category]) ? esc_html($categories[$t->category]) : '-'; ?></td>
                            <td><?php echo isset($priorities[$t->priority])
                                    ? '<span class="rsd-badge rsd-badge-' . ($t->priority === 'urgent' ? 'danger' : ($t->priority === 'high' ? 'warning' : ($t->priority === 'low' ? 'secondary' : 'info'))) . '">' . esc_html($priorities[$t->priority]) . '</span>'
                                    : '-'; ?></td>
                            <td><?php echo esc_html(date_i18n('j F Y', strtotime($t->created_at))); ?></td>
                            <td><?php
                                if (!empty($t->last_reply_at)) {
                                    echo esc_html(sprintf('%s پیش', human_time_diff(strtotime($t->last_reply_at))));
                                } else {
                                    echo '<span style="color:#cbd5e1">-</span>';
                                }
                            ?></td>
                            <td><?php echo rsd_fp_status_badge($t->status); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['view' => 'single', 'id' => $t->id], rsd_fp_page_url('tickets'))); ?>"
                                   class="rsd-btn rsd-btn-sm rsd-btn-outline">مشاهده</a>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="8">
                            <div class="rsd-empty"><div class="rsd-empty-icon">💬</div>
                                <h3>هنوز تیکتی ثبت نکرده‌اید</h3>
                                <p>برای ارتباط با کارشناسان، از دکمه «ثبت تیکت جدید» استفاده کنید.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- New Ticket Modal -->
<div class="rsd-modal-backdrop" id="rsd-new-ticket-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">ثبت پیام یا تیکت جدید</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-new-ticket-form">
                <div class="rsd-form-row">
                    <label>نوع درخواست <span class="required">*</span></label>
                    <select name="type">
                        <option value="question">ثبت سؤال</option>
                        <option value="issue">ثبت تیکت / مشکل</option>
                    </select>
                </div>
                <div class="rsd-form-row">
                    <label>موضوع <span class="required">*</span></label>
                    <select name="category">
                        <?php foreach ($categories as $k => $v) : ?>
                            <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsd-form-row">
                    <label>عنوان <span class="required">*</span></label>
                    <input type="text" name="subject" required placeholder="خلاصه موضوع">
                </div>
                <div class="rsd-form-row">
                    <label>اولویت</label>
                    <select name="priority">
                        <?php foreach ($priorities as $k => $v) : ?>
                            <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="rsd-form-row">
                    <label>توضیحات / پیام شما <span class="required">*</span></label>
                    <textarea name="message" rows="5" required></textarea>
                </div>
                <div class="rsd-form-row">
                    <label>فایل پیوست (اختیاری)</label>
                    <input type="file" name="ticket_attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button class="rsd-btn rsd-btn-light" data-close-modal>انصراف</button>
            <button type="submit" form="rsd-new-ticket-form" class="rsd-btn rsd-btn-primary">ارسال پیام</button>
        </div>
    </div>
</div>
