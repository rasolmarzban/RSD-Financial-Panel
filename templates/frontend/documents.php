<?php

/**
 * Template: Frontend - Documents Page
 *
 * @var array $documents
 * @var int $client_id
 * @var string $active_tab
 * @var int $count_all
 * @var int $count_incomplete
 * @var int $count_tax
 */
defined('ABSPATH') || exit;

$active = 'documents';
$categories = \RSD\FinancePanel\Modules\Documents::get_categories();
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">📁 Documents Management</h2>
            <div class="spacer"></div>
            <button type="button" class="rsd-btn rsd-btn-primary" data-modal="rsd-upload-modal">
                ➕ Upload New Document
            </button>
            <button type="button" class="rsd-btn rsd-btn-outline" data-modal="rsd-batch-upload-modal">
                📦 Batch Upload
            </button>
        </div>

        <div class="rsd-tabs">
            <a class="rsd-tab <?php echo $active_tab === 'all' ? 'active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('tab', 'all')); ?>">
                All Documents (<?php echo esc_html($count_all); ?>)
            </a>
            <a class="rsd-tab <?php echo $active_tab === 'incomplete' ? 'active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('tab', 'incomplete')); ?>">
                Incomplete Documents (<?php echo esc_html($count_incomplete); ?>)
            </a>
            <a class="rsd-tab <?php echo $active_tab === 'tax' ? 'active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('tab', 'tax')); ?>">
                Tax Authority Letters (<?php echo esc_html($count_tax); ?>)
            </a>
        </div>

        <?php if ($active_tab === 'incomplete') : ?>
            <div class="rsd-card" style="margin-bottom:20px;background:#fff1f0;border:1px solid #fed7d7">
                <div class="rsd-card-header">
                    <div class="rsd-card-title" style="color:#c53030">⚠️ Documents Requested by the Company</div>
                </div>
                <div class="rsd-list">
                    <?php
                    $incomplete = array_filter($documents, fn($d) => !empty($d->is_incomplete));
                    if (!empty($incomplete)) :
                        foreach ($incomplete as $doc) : ?>
                            <div class="rsd-list-item">
                                <div class="rsd-list-icon" style="background:#fee2e2;color:#dc2626">📄</div>
                                <div class="rsd-list-main">
                                    <div class="rsd-list-title"><?php echo esc_html($doc->title); ?></div>
                                    <div class="rsd-list-meta">
                                        Request Date: <?php echo esc_html(!empty($doc->requested_date) ? date_i18n('j F Y', strtotime($doc->requested_date)) : '—'); ?>
                                        <?php if (!empty($doc->period)) : ?>
                                            | Period: <?php echo esc_html($doc->period); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($doc->description)) : ?>
                                            <br><span style="color:#64748b"><?php echo esc_html($doc->description); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($doc->expiry_date)) : ?>
                                            <br><span style="color:#dc2626;font-weight:700">Submission Deadline: <?php echo esc_html(rsd_fp_jalali_date('j F Y', $doc->expiry_date)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="rsd-list-actions">
                                    <?php echo rsd_fp_status_badge($doc->status); ?>
                                    <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-primary"
                                        data-modal="rsd-upload-modal"
                                        data-doc-id="<?php echo (int)$doc->id; ?>">
                                        📥 Upload Document
                                    </button>
                                </div>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <div class="rsd-empty">
                            <div class="rsd-empty-icon">✅</div>
                            <h3>All documents are complete</h3>
                            <p>There are no incomplete documents awaiting upload.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="rsd-card">
            <div class="rsd-card-header">
                <div class="rsd-card-title"><span class="rsd-card-icon">📚</span> Documents Archive</div>
            </div>

            <div class="rsd-table-wrap">
                <table class="rsd-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Year / Period</th>
                            <th>File</th>
                            <th>Upload Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($documents)) : foreach ($documents as $doc) : ?>
                                <tr>
                                    <td style="font-weight:600;color:#0f172a">
                                        <?php echo esc_html($doc->title); ?>
                                    </td>
                                    <td>
                                        <?php
                                        echo isset($categories[$doc->category])
                                            ? esc_html($categories[$doc->category])
                                            : esc_html__('-', 'rsd-finance-panel');
                                        ?>
                                    </td>
                                    <td><?php echo esc_html(trim(($doc->year ?? '') . ' / ' . ($doc->period || ''), ' /')); ?></td>
                                    <td>
                                        <?php if (!empty($doc->file_path)) : ?>
                                            <a href="<?php echo esc_url($doc->file_path); ?>" target="_blank"
                                                class="rsd-btn rsd-btn-sm rsd-btn-outline">📂 Download</a>
                                        <?php else : ?>
                                            <span style="color:#cbd5e1;font-size:11px">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(date_i18n('j F Y', strtotime($doc->created_at))); ?></td>
                                    <td>
                                        <?php if (!empty($doc->expiry_date)) :
                                            $exp_ts = strtotime($doc->expiry_date);
                                            $days_left = (int)ceil(($exp_ts - time()) / 86400);
                                            $style = '';
                                            if ($days_left < 0) {
                                                $style = 'color:#dc2626;font-weight:700';
                                                $extra = ' (Expired)';
                                            } elseif ($days_left <= 7) {
                                                $style = 'color:#b45309;font-weight:700';
                                                $extra = ' (' . $days_left . ' days left)';
                                            } else {
                                                $style = 'color:#16a34a';
                                                $extra = '';
                                            }
                                        ?>
                                            <span style="<?php echo esc_attr($style); ?>">
                                                <?php echo esc_html(rsd_fp_jalali_date('j F Y', $exp_ts) . ($extra ?? '')); ?>
                                            </span>
                                        <?php else : ?>
                                            <span style="color:#cbd5e1;font-size:11px">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo rsd_fp_status_badge($doc->status); ?></td>
                                    <td>
                                        <div class="cell-actions">
                                            <?php if (!empty($doc->is_incomplete)) : ?>
                                                <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-primary"
                                                    style="margin-left:4px"
                                                    data-modal="rsd-upload-modal"
                                                    data-doc-id="<?php echo (int)$doc->id; ?>">
                                                    📥 Replace
                                                </button>
                                            <?php endif; ?>
                                            <?php if (current_user_can('manage_options') || $doc->user_id == $client_id) : ?>
                                                <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-danger rsd-confirm"
                                                    data-delete-document="<?php echo (int) $doc->id; ?>">
                                                    🗑️
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else : ?>
                            <tr>
                                <td colspan="8">
                                    <div class="rsd-empty">
                                        <div class="rsd-empty-icon">📂</div>
                                        <h3>No documents have been uploaded yet</h3>
                                        <p>Use the "Upload New Document" button.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- Upload Modal -->
<div class="rsd-modal-backdrop" id="rsd-upload-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">Upload New Document</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-upload-doc-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="rsd_doc_id" id="rsd_doc_id" value="">
                <!-- Current document preview (only rendered when re-uploading an incomplete doc) -->
                <div class="rsd-current-doc-preview" style="display:none;padding:12px 14px;border:1px dashed #fcd34d;border-radius:12px;background:#fffbeb;margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                        <span style="display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:50%;background:#fef3c7;color:#b45309;font-size:16px">ℹ️</span>
                        <div>
                            <div style="font-weight:700;color:#92400e;font-size:13px">Current Incomplete Document Info</div>
                            <div style="color:#92400e;font-size:11px;margin-top:2px">If needed, you can correct the following or just select a new file and submit.</div>
                        </div>
                    </div>
                    <div class="rsd-current-doc-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;font-size:12.5px;color:#334155">
                        <div><span style="color:#64748b">Title:</span> <strong class="rsd-cur-title"></strong></div>
                        <div><span style="color:#64748b">Category:</span> <strong class="rsd-cur-category"></strong></div>
                        <div><span style="color:#64748b">Year:</span> <strong class="rsd-cur-year"></strong></div>
                        <div><span style="color:#64748b">Month:</span> <strong class="rsd-cur-month"></strong></div>
                        <div style="grid-column:1/-1"><span style="color:#64748b">Period:</span> <strong class="rsd-cur-period"></strong></div>
                        <div><span style="color:#64748b">Expiry Date:</span> <strong style="color:#dc2626" class="rsd-cur-expiry"></strong></div>
                        <div><span style="color:#64748b">Uploaded:</span> <strong class="rsd-cur-uploaded"></strong></div>
                        <div style="grid-column:1/-1"><span style="color:#64748b">Description:</span> <strong class="rsd-cur-description"></strong></div>
                    </div>
                    <div class="rsd-cur-file-row" style="display:flex;align-items:center;gap:8px;margin-top:12px;padding:8px 10px;border-radius:10px;background:white;border:1px solid #fde68a">
                        <span style="font-size:20px">📎</span>
                        <div style="flex:1;min-width:0">
                            <div class="rsd-cur-filename" style="font-weight:600;color:#0f172a;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
                            <div class="rsd-cur-filesize" style="color:#64748b;font-size:11px;margin-top:2px"></div>
                        </div>
                        <a href="#" target="_blank" class="rsd-cur-download rsd-btn rsd-btn-sm rsd-btn-outline">📂 Download Current File</a>
                    </div>
                </div>

                <div class="rsd-form-row">
                    <label>Document Title <span class="required">*</span></label>
                    <input type="text" name="title" required>
                </div>
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>Category</label>
                        <select name="category">
                            <?php foreach ($categories as $k => $v) : ?>
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
                        <label>Related Period</label>
                        <input type="text" name="period" placeholder="e.g.: First two months of the year">
                    </div>
                    <div class="rsd-form-row">
                        <label>Month</label>
                        <select name="month">
                            <option value="">-</option>
                            <?php for ($i = 1; $i <= 12; $i++) : ?>
                                <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="rsd-form-row">
                    <label>Expiry Date / Deadline</label>
                    <?php rsd_fp_render_jalali_triple('expiry_date', 'rsd-fe-doc-expiry', false, ''); ?>
                    <span style="color:#94a3b8;font-size:11px;margin-inline-end:4px">Select the deadline year, month, and day (optional)</span>
                </div>
                <div class="rsd-form-row">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="rsd-form-row">
                    <label class="rsd-file-label">File
                        <span class="rsd-file-label-hint" style="color:#64748b;font-size:11px;margin-inline-start:6px">(To update metadata only, you can submit without selecting a new file)</span>
                    </label>
                    <div class="rsd-upload-area">
                        <div class="upload-icon">📤</div>
                        <div class="upload-text">Click or drag</div>
                        <div class="upload-hint">PDF, JPG, DOC, XLS — If no new file is selected, only the document info is updated</div>
                        <input type="file" name="document_file" style="display:none"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar,.txt,.csv">
                    </div>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button class="rsd-btn rsd-btn-light" data-close-modal>Cancel</button>
            <button type="submit" form="rsd-upload-doc-form" class="rsd-btn rsd-btn-primary">Submit Document</button>
        </div>
    </div>
</div>

<!-- Batch Upload Modal -->
<div class="rsd-modal-backdrop" id="rsd-batch-upload-modal">
    <div class="rsd-modal">
        <div class="rsd-modal-header">
            <div class="rsd-modal-title">Batch Upload Documents</div>
            <button type="button" class="rsd-modal-close">&times;</button>
        </div>
        <div class="rsd-modal-body">
            <form id="rsd-batch-upload-form" method="post" enctype="multipart/form-data">
                <div class="rsd-form-row-inline">
                    <div class="rsd-form-row">
                        <label>Default Category</label>
                        <select name="category">
                            <?php foreach ($categories as $k => $v) : ?>
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
                <div class="rsd-form-row">
                    <label>Expiry Date / Shared Deadline for All Files</label>
                    <?php rsd_fp_render_jalali_triple('expiry_date', 'rsd-fe-batch-expiry', false, ''); ?>
                    <span style="color:#94a3b8;font-size:11px;margin-inline-end:4px">Select the deadline year, month, and day (optional)</span>
                </div>
                <div class="rsd-form-row">
                    <label>Files <span class="required">*</span></label>
                    <div class="rsd-upload-area">
                        <div class="upload-icon">📦</div>
                        <div class="upload-text">Select or drag multiple files</div>
                        <div class="upload-hint">Each file is saved as a separate row</div>
                        <input type="file" name="documents[]" multiple required style="display:none">
                    </div>
                </div>
            </form>
        </div>
        <div class="rsd-modal-footer">
            <button class="rsd-btn rsd-btn-light" data-close-modal>Cancel</button>
            <button type="submit" form="rsd-batch-upload-form" class="rsd-btn rsd-btn-primary">Start Upload</button>
        </div>
    </div>
</div>