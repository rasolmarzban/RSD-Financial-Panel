<?php

/**
 * Admin Template: Documents Management
 *
 * @var array $documents
 * @var string $status
 * @var int $client_id
 */
defined('ABSPATH') || exit;

$categories = \RSD\FinancePanel\Modules\Documents::get_categories();
$statuses   = \RSD\FinancePanel\Modules\Documents::get_statuses();
$smap       = [];
$s_labels   = [];
foreach ($statuses as $k => $v) {
    $smap[$k]     = $v[1];
    $s_labels[$k] = $v[0];
}
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>📁 Document Management</h1>
            <p>Manage documents uploaded by clients</p>
        </div>
    </div>

    <div class="rsd-admin-filters">
        <form method="get">
            <input type="hidden" name="page" value="rsd-fp-documents">
            <select name="status">
                <option value="all">All Statuses</option>
                <?php foreach ($statuses as $key => $info) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($status ?? '', $key); ?>>
                        <?php echo esc_html($info[0]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
            $clients = get_users(['role__in' => ['rsd_client', 'administrator']]);
            ?>
            <select name="client_id">
                <option value="0">All Clients</option>
                <?php foreach ($clients as $c) : ?>
                    <option value="<?php echo (int)$c->ID; ?>" <?php selected($client_id ?? 0, $c->ID); ?>>
                        <?php echo esc_html($c->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="rsd-btn-admin small primary">Apply Filter</button>
        </form>
    </div>

    <div class="rsd-admin-card">
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Document Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Submission Date</th>
                        <th>Expiry Date</th>
                        <th>File</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($documents)) : foreach ($documents as $d) :
                            $current_status = $d->status ?? 'uploaded';
                            if (!isset($statuses[$current_status])) {
                                $current_status = 'uploaded';
                            }
                    ?>
                            <tr data-doc-row="<?php echo (int)$d->id; ?>">
                                <td style="font-family:monospace;color:#64748b">#<?php echo (int)$d->id; ?></td>
                                <td>
                                    <div class="rsd-user-chip">
                                        <span class="avatar"><?php echo esc_html(mb_substr($d->client_name ?? '?', 0, 1)); ?></span>
                                        <?php echo esc_html($d->client_name ?? '—'); ?>
                                    </div>
                                </td>
                                <td style="font-weight:600;color:#0f172a">
                                    <?php
                                    $incomplete_badge = '';
                                    ob_start();
                                    if (!empty($d->is_incomplete)) :
                                    ?><span class="rsd-admin-badge red" style="margin-left:6px">Incomplete</span><?php
                                                                                                        endif;
                                                                                                        $incomplete_badge = ob_get_clean();
                                                                                                        echo $incomplete_badge;
                                                                                                            ?>
                                    <span class="rsd-doc-title-text"><?php echo esc_html($d->title); ?></span>
                                </td>
                                <td><?php echo isset($categories[$d->category]) ? esc_html($categories[$d->category]) : esc_html($d->category); ?></td>
                                <td>
                                    <div class="rsd-doc-status-cell">
                                        <span class="rsd-admin-badge <?php echo esc_attr($smap[$current_status] ?? 'gray'); ?> rsd-doc-status-badge"
                                            data-doc-badge="<?php echo (int)$d->id; ?>">
                                            <?php echo esc_html($s_labels[$current_status] ?? $current_status); ?>
                                        </span>
                                        <select class="rsd-doc-status-select"
                                            data-doc-status
                                            data-doc-id="<?php echo (int)$d->id; ?>"
                                            aria-label="<?php esc_attr_e('Change document status', 'rsd-finance-panel'); ?>">
                                            <?php foreach ($statuses as $key => $info) : ?>
                                                <option value="<?php echo esc_attr($key); ?>"
                                                    data-color="<?php echo esc_attr($info[1]); ?>"
                                                    <?php selected($current_status, $key); ?>>
                                                    <?php echo esc_html($info[0]); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </td>
                                <td style="color:#64748b;font-size:12px"><?php echo esc_html(date_i18n('j F Y', strtotime($d->created_at))); ?></td>
                                <td>
                                    <?php if (!empty($d->expiry_date)) :
                                        $exp_ts = strtotime($d->expiry_date);
                                        $days_left = (int)ceil(($exp_ts - time()) / 86400);
                                        $cls = 'green';
                                        $label = '';
                                        if ($days_left < 0) {
                                            $cls = 'red';
                                            $label = ' (Expired)';
                                        } elseif ($days_left <= 7) {
                                            $cls = 'amber';
                                            $label = ' (' . $days_left . ' days)';
                                        }
                                    ?>
                                        <span style="color:#0f172a;font-size:12px"><?php echo esc_html(rsd_fp_jalali_date('j F Y', $exp_ts)); ?></span>
                                        <br>
                                        <span class="rsd-admin-badge <?php echo esc_attr($cls); ?>" style="margin-top:3px">
                                            <?php echo esc_html(trim(($days_left < 0 ? 'Expired' : 'Remaining: ' . $days_left . ' days') . $label)); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="color:#cbd5e1;font-size:11px">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($d->file_path)) : ?>
                                        <a href="<?php echo esc_url($d->file_path); ?>" target="_blank"
                                            class="rsd-btn-admin small secondary">Download</a>
                                    <?php else : ?>
                                        <span style="color:#cbd5e1">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="rsd-btn-admin small danger rsd-confirm"
                                        data-delete-document="<?php echo (int)$d->id; ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="9">
                                <div class="rsd-empty">
                                    <p>No documents found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
