<?php

/**
 * Admin Template: Calendar / Events Management
 *
 * @var array $events
 */
defined('ABSPATH') || exit;

use RSD\FinancePanel\Modules\Calendar;

$cats        = Calendar::get_categories();
$statuses    = Calendar::get_statuses();
$cat_colors  = [
    'meeting'      => '#6366f1',
    'deadline'     => '#dc2626',
    'tax'          => '#f59e0b',
    'payroll'      => '#7c3aed',
    'reminder'     => '#0ea5e9',
    'company'      => '#14b8a6',
    'personal'     => '#94a3b8',
    'other'        => '#64748b',
];
$status_colors = [
    'confirmed'  => 'green',
    'pending'    => 'yellow',
    'cancelled'  => 'red',
    'tentative'  => 'purple',
    'completed'  => 'teal',
    'rejected'   => 'gray',
];
$req_status_labels = [
    'pending'   => 'Pending approval',
    'approved'  => 'Approved',
    'rejected'  => 'Rejected',
];
$req_colors = [
    'pending'   => 'yellow',
    'approved'  => 'green',
    'rejected'  => 'red',
];
$today = date('Y-m-d');
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>📅 Calendar &amp; Events Management</h1>
            <p>Manage financial meetings, deadlines, appointments and reminders</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-clients')); ?>"
                class="rsd-btn-admin secondary">👥 Client List</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=rsd-fp-services')); ?>"
                class="rsd-btn-admin secondary">🗂️ Services</a>
        </div>
    </div>

    <!-- Legend -->
    <div class="rsd-admin-card" style="padding:14px 20px;margin-bottom:16px">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;color:#334155;font-size:13px">
            <?php foreach ($cats as $ck => $cl) :
                $c = $cat_colors[$ck] ?? '#64748b';
            ?>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:<?php echo esc_attr($c); ?>"></span>
                    <span><?php echo esc_html($cl); ?></span>
                </div>
            <?php endforeach; ?>
            <div style="margin-left:auto;display:flex;gap:14px;flex-wrap:wrap">
                <span style="color:#64748b">
                    📊 Total events: <strong style="color:#0f172a"><?php echo (int) count($events); ?></strong>
                </span>
            </div>
        </div>
    </div>

    <div class="rsd-admin-card">
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table widefat">
                <thead>
                    <tr>
                        <th style="width:80px">ID</th>
                        <th>Event Title</th>
                        <th>Client / Owner</th>
                        <th>Category</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Duration</th>
                        <th>Event Status</th>
                        <th>Request</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($events)) : foreach ($events as $e) :
                            $sc = $status_colors[$e->status] ?? 'gray';
                            $cc = $cat_colors[$e->category] ?? '#64748b';
                            $start_ts = strtotime($e->start_date);
                            $end_ts = !empty($e->end_date) ? strtotime($e->end_date) : null;
                            $is_past = $end_ts ? $end_ts < strtotime('today midnight') : $start_ts < strtotime('today midnight');
                            $is_today = date('Y-m-d', $start_ts) === $today;
                            $duration = '';
                            if ($start_ts && $end_ts && $end_ts > $start_ts) {
                                $diff_mins = (int) round(($end_ts - $start_ts) / 60);
                                if ($diff_mins < 60) $duration = $diff_mins . ' min';
                                else {
                                    $h = floor($diff_mins / 60);
                                    $m = $diff_mins % 60;
                                    $duration = $h . ' h' . ($m ? ' ' . $m . ' min' : '');
                                }
                            } elseif (!empty($e->is_all_day)) {
                                $duration = 'All day';
                            }
                            $is_doc  = (int)$e->id < 0;
                            $start_j = $start_ts ? rsd_fp_jalali_date('Y/m/d', date('Y-m-d', $start_ts)) : '';
                            $end_j   = $end_ts   ? rsd_fp_jalali_date('Y/m/d', date('Y-m-d', $end_ts))   : '';
                    ?>
                            <tr style="<?php echo $is_today ? 'background:linear-gradient(90deg,#ecfeff 0%,#faf5ff 100%)' : ($is_past ? 'opacity:.7' : ''); ?>">
                                <td><span style="font-family:monospace;font-weight:700;color:#0369a1">#<?php echo (int)$e->id; ?></span></td>
                                <td style="color:#0f172a;font-weight:600">
                                    <?php if ($is_today) echo '<span class="rsd-admin-badge blue" style="margin-left:6px;font-size:11px">Today</span>'; ?>
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:<?php echo esc_attr($cc); ?>;margin-left:6px"></span>
                                    <?php echo esc_html($e->title); ?>
                                    <?php if (!empty($e->description)) : ?>
                                        <div style="color:#64748b;font-weight:400;font-size:12px;margin-top:3px;line-height:1.6">
                                            <?php echo esc_html(mb_substr($e->description, 0, 75)); ?>...
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rsd-user-chip">
                                        <?php echo get_avatar($e->user_id, 28); ?>
                                        <div style="display:flex;flex-direction:column;gap:2px">
                                            <span style="font-weight:600"><?php echo esc_html($e->client_name ?? 'User #' . $e->user_id); ?></span>
                                            <span style="color:#64748b;font-size:11px"><?php echo esc_html(get_userdata($e->user_id)->user_email ?? ''); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:600;color:#475569">
                                        <?php echo isset($cats[$e->category]) ? esc_html($cats[$e->category]) : esc_html($e->category); ?>
                                    </span>
                                </td>
                                <td style="color:#0f172a;font-weight:600">
                                    <?php echo esc_html(date_i18n('j F Y', $start_ts)); ?>
                                    <div style="color:#64748b;font-weight:400;font-size:11px;margin-top:2px">
                                        Time <?php echo esc_html(date('H:i', $start_ts)); ?>
                                        <?php if (!empty($e->is_all_day)) echo ' (All day)'; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($end_ts) : ?>
                                        <div style="color:#475569;font-weight:600"><?php echo esc_html(date_i18n('j F Y', $end_ts)); ?></div>
                                        <div style="color:#64748b;font-size:11px;margin-top:2px">Time <?php echo esc_html(date('H:i', $end_ts)); ?></div>
                                    <?php else : ?>
                                        <span style="color:#cbd5e1">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#64748b;font-size:13px">
                                    <?php echo !empty($duration) ? esc_html($duration) : '<span style="color:#cbd5e1">-</span>'; ?>
                                </td>
                                <td>
                                    <span class="rsd-admin-badge <?php echo $sc; ?>">
                                        <?php echo isset($statuses[$e->status]) ? esc_html($statuses[$e->status]) : esc_html($e->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($e->event_type) && in_array($e->event_type, ['meeting_request', 'client_request'], true)) {
                                        $rs = $e->request_status ?? 'pending';
                                        $rc = $req_colors[$rs] ?? 'gray';
                                        echo '<span class="rsd-admin-badge ' . $rc . '">' . esc_html($req_status_labels[$rs] ?? $rs) . '</span>';
                                    } elseif ($is_doc) {
                                        echo '<span class="rsd-admin-badge purple" style="font-size:12px">Document expiry</span>';
                                    } else {
                                        echo '<span style="color:#cbd5e1;font-size:12px">Direct</span>';
                                    }
                                    ?>
                                </td>
                                <td style="display:flex;gap:6px;flex-wrap:wrap">
                                    <?php if (!empty($e->request_status) && $e->request_status === 'pending') : ?>
                                        <button type="button" class="rsd-btn-admin small primary" style="padding:4px 10px;font-size:12px">Approve</button>
                                        <button type="button" class="rsd-btn-admin small danger" style="padding:4px 10px;font-size:12px">Reject</button>
                                    <?php endif; ?>
                                    <button type="button"
                                        class="rsd-btn-admin small primary rsd-edit-event-btn"
                                        style="padding:4px 10px;font-size:12px"
                                        data-edit-event="<?php echo (int)$e->id; ?>"
                                        data-event-title="<?php echo esc_attr($e->title); ?>"
                                        data-is-doc="<?php echo $is_doc ? '1' : '0'; ?>"
                                        data-start-j="<?php echo esc_attr($start_j); ?>"
                                        data-end-j="<?php echo esc_attr($end_j); ?>">Edit</button>
                                    <button type="button" class="rsd-btn-admin small secondary" style="padding:4px 10px;font-size:12px"
                                        data-delete-event="<?php echo (int)$e->id; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="10">
                                <div class="rsd-empty">
                                    <div class="rsd-empty-icon">📅</div>
                                    <h3>No events registered in the calendar yet</h3>
                                    <p>Financial meetings, deadlines and appointments are shown in this table.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Event Dates Modal -->
<div id="rsd-edit-event-modal"
    style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:999999;align-items:center;justify-content:center">
    <div style="width:520px;max-width:92%;background:#fff;border-radius:12px;box-shadow:0 25px 50px -12px rgba(0,0,0,.35);overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:linear-gradient(90deg,#eff6ff,#f5f3ff);border-bottom:1px solid #e2e8f0">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:20px">📅</span>
                <h3 id="rsd-edit-event-title"
                    style="margin:0;font-size:16px;font-weight:700;color:#0f172a">Edit Event Date</h3>
            </div>
            <button type="button" id="rsd-edit-event-close"
                style="background:transparent;border:0;font-size:20px;cursor:pointer;color:#64748b;padding:0 4px">×</button>
        </div>
        <div style="padding:20px">
            <input type="hidden" id="rsd-edit-event-id" value="0">
            <input type="hidden" id="rsd-edit-event-is-doc" value="0">

            <div id="rsd-edit-start-row" style="margin-bottom:16px">
                <label id="rsd-edit-start-label"
                    style="display:block;margin-bottom:6px;font-weight:600;color:#334155;font-size:13px">Start Date (Gregorian)</label>
                <?php rsd_fp_render_jalali_triple('', 'rsd-edit-start', true, ''); ?>
                <div style="margin-top:4px;font-size:11px;color:#64748b">Select year, month and day from the lists</div>
            </div>

            <div id="rsd-edit-end-row" style="margin-bottom:20px">
                <label style="display:block;margin-bottom:6px;font-weight:600;color:#334155;font-size:13px">End Date (Gregorian — optional)</label>
                <?php rsd_fp_render_jalali_triple('', 'rsd-edit-end', false, ''); ?>
            </div>

            <div id="rsd-edit-error-msg"
                style="display:none;margin-bottom:14px;padding:8px 12px;background:#fef2f2;color:#b91c1c;border-radius:6px;font-size:12px"></div>

            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" id="rsd-edit-event-cancel"
                    class="rsd-btn-admin secondary">Cancel</button>
                <button type="button" id="rsd-edit-event-save"
                    class="rsd-btn-admin primary">💾 Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function($) {
        var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        var nonceVal = '<?php echo esc_js(wp_create_nonce('rsd_fp_nonce')); ?>';

        var $modal = $('#rsd-edit-event-modal');
        var $evTitle = $('#rsd-edit-event-title');
        var $evId = $('#rsd-edit-event-id');
        var $evIsDoc = $('#rsd-edit-event-is-doc');
        var $startJ = $('#rsd-edit-start-hidden');
        var $endJ = $('#rsd-edit-end-hidden');
        var $endRow = $('#rsd-edit-end-row');
        var $startLabel = $('#rsd-edit-start-label');
        var $errBox = $('#rsd-edit-error-msg');

        function showErr(msg) {
            $errBox.text(msg).stop(true, true).fadeIn(120);
            setTimeout(function() {
                $errBox.fadeOut(250);
            }, 3800);
        }

        function closeModal() {
            $modal.css('display', 'none');
            $errBox.hide();
        }

        function openModal() {
            $modal.css('display', 'flex');
        }

        // Gregorian regex: 1900-2099 / 01-12 / 01-31
        var jalaliRe = /^(19|20)\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/;

        $(document).on('click', '.rsd-edit-event-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var evId = $btn.data('edit-event');
            var title = $btn.data('event-title') || '';
            var isDoc = $btn.data('is-doc') == 1;
            var startJ = $btn.data('start-j') || '';
            var endJ = $btn.data('end-j') || '';

            $evId.val(evId);
            $evIsDoc.val(isDoc ? 1 : 0);

            // Update triple-select hidden values and trigger change to sync selects
            if (startJ && jalaliRe.test(startJ)) {
                $startJ.val(startJ).trigger('change');
            } else {
                $startJ.val('').trigger('change');
            }
            if (endJ && jalaliRe.test(endJ)) {
                $endJ.val(endJ).trigger('change');
            } else {
                $endJ.val('').trigger('change');
            }

            if (isDoc) {
                $evTitle.text('Edit document expiry date: ' + title);
                $startLabel.text('Expiry Date (Gregorian)');
                $endRow.hide();
            } else {
                $evTitle.text('Edit event dates: ' + title);
                $startLabel.text('Start Date (Gregorian)');
                $endRow.show();
            }
            openModal();
        });

        $('#rsd-edit-event-close, #rsd-edit-event-cancel').on('click', closeModal);

        $modal.on('click', function(e) {
            if (e.target === $modal[0]) closeModal();
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.css('display') !== 'none') closeModal();
        });

        $('#rsd-edit-event-save').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var origT = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            var evId = parseInt($evId.val(), 10) || 0;
            var isDoc = $evIsDoc.val() == 1;
            var sj = $.trim($startJ.val());
            var ej = isDoc ? '' : $.trim($endJ.val());

            if (!jalaliRe.test(sj)) {
                $btn.prop('disabled', false).text(origT);
                showErr(isDoc ? 'Invalid Gregorian expiry date.' : 'Invalid Gregorian start date.');
                return;
            }
            if (!isDoc && ej !== '' && !jalaliRe.test(ej)) {
                $btn.prop('disabled', false).text(origT);
                showErr('Invalid Gregorian end date.');
                return;
            }

            $.post(ajaxUrl, {
                action: 'rsd_fp_update_calendar_event',
                nonce: nonceVal,
                event_id: evId,
                start_jalali: sj,
                end_jalali: ej
            }, function(resp) {
                $btn.prop('disabled', false).text(origT);
                if (resp && resp.success) {
                    closeModal();
                    var msg = (resp && resp.data && resp.data.message) || 'Saved successfully.';
                    $('<div>').css({
                            position: 'fixed',
                            top: 32,
                            left: '50%',
                            transform: 'translateX(-50%)',
                            zIndex: 9999999,
                            background: '#059669',
                            color: '#fff',
                            padding: '10px 18px',
                            borderRadius: 6,
                            fontSize: 13,
                            fontWeight: 600,
                            boxShadow: '0 10px 15px -3px rgba(0,0,0,.2)'
                        }).text('✓ ' + msg).appendTo('body')
                        .delay(2600).fadeOut(400, function() {
                            $(this).remove();
                        });
                    setTimeout(function() {
                        location.reload();
                    }, 900);
                } else {
                    var em = (resp && resp.data && resp.data.message) || 'Error while saving.';
                    showErr(em);
                }
            }, 'json').fail(function() {
                $btn.prop('disabled', false).text(origT);
                showErr('Error communicating with the server.');
            });
        });

        // Handle existing delete buttons (UX)
        $(document).on('click', '[data-delete-event]', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this event?')) return;
            var $btn = $(this);
            var evId = $btn.data('delete-event');
            $btn.prop('disabled', true).text('...');
            $.post(ajaxUrl, {
                action: 'rsd_fp_delete_calendar_event',
                nonce: nonceVal,
                event_id: evId
            }, function(resp) {
                if (resp && resp.success) location.reload();
                else alert((resp && resp.data && resp.data.message) || 'Error deleting.');
            }, 'json').fail(function() {
                alert('Error communicating with the server.');
            });
        });
    })(jQuery);
</script>
