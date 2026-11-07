<?php

/**
 * Template: Frontend - Company Info Page
 *
 * @var object|null $info
 * @var int $client_id
 */
defined('ABSPATH') || exit;

$active = 'company_info';
$fields = \RSD\FinancePanel\Modules\Company_Info::get_fields();
$sections = \RSD\FinancePanel\Modules\Company_Info::get_sections();
$info = $info ?? new \stdClass();
$logo_url = !empty($info->logo_url) ? $info->logo_url : '';
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">🏢 Company Information</h2>
            <div class="spacer"></div>
            <?php if (!empty($info->pending_changes)) : ?>
                <span class="rsd-badge rsd-badge-warning">
                    ⏳ Your changes are awaiting expert approval
                </span>
            <?php endif; ?>
        </div>

        <!-- Company logo (applied immediately, shown in the dashboard sidebar) -->
        <form id="rsd-company-logo-form" enctype="multipart/form-data">
            <div class="rsd-card" style="margin-bottom:20px">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">🖼️</span>
                        Company Logo
                    </div>
                </div>

                <div class="rsd-form-row">
                    <label>Logo</label>
                    <div style="display:flex;align-items:center;gap:16px">
                        <div id="rsd-company-logo-preview"
                            style="width:88px;height:88px;border-radius:14px;overflow:hidden;flex-shrink:0;
                                   background:#f1f5f9;border:1px solid #e2e8f0;display:flex;
                                   align-items:center;justify-content:center;color:#94a3b8;font-size:26px">
                            <?php if ($logo_url) : ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="" style="width:100%;height:100%;object-fit:contain;display:block">
                            <?php else : ?>
                                🏢
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="rsd-company-logo-input" class="rsd-btn rsd-btn-sm rsd-btn-primary" style="cursor:pointer">Choose Logo</label>
                            <input type="file" id="rsd-company-logo-input" name="company_logo" accept="image/*" style="display:none">
                            <?php if ($logo_url) : ?>
                                <label style="display:block;margin-top:8px;font-size:13px;color:#64748b">
                                    <input type="checkbox" name="remove_logo" value="1"> Remove current logo
                                </label>
                            <?php endif; ?>
                            <div class="rsd-form-hint">JPG, PNG or SVG. Square or wide logos look best. Shown in the dashboard sidebar.</div>
                        </div>
                    </div>
                </div>

                <div style="text-align:left;margin-top:14px">
                    <button type="submit" class="rsd-btn rsd-btn-primary">Upload Logo 🖼️</button>
                </div>
            </div>
        </form>

        <form id="rsd-company-info-form">
            <?php foreach ($sections as $sec_key => $sec_label) : ?>
                <div class="rsd-card" style="margin-bottom:20px">
                    <div class="rsd-card-header">
                        <div class="rsd-card-title">
                            <span class="rsd-card-icon">📋</span>
                            <?php echo esc_html($sec_label); ?>
                        </div>
                    </div>

                    <div class="rsd-grid rsd-grid-2">
                        <?php
                        $sec_fields = array_filter($fields, fn($f) => ($f['section'] ?? '') === $sec_key);
                        foreach ($sec_fields as $key => $f) :
                            $val = isset($info->$key) ? $info->$key : '';
                            if (!empty($info->pending_changes)) {
                                $pending = json_decode($info->pending_changes, true);
                                if (isset($pending[$key])) $val = $pending[$key];
                            }
                        ?>
                            <div class="rsd-form-row">
                                <label>
                                    <?php echo esc_html($f['label']); ?>
                                    <?php if (!empty($f['required'])) echo '<span class="required">*</span>'; ?>
                                </label>
                                <?php if ($f['type'] === 'textarea') : ?>
                                    <textarea name="<?php echo esc_attr($key); ?>" rows="3"><?php echo esc_textarea($val); ?></textarea>
                                <?php else : ?>
                                    <input type="<?php echo esc_attr($f['type']); ?>"
                                        name="<?php echo esc_attr($key); ?>"
                                        value="<?php echo esc_attr($val); ?>">
                                <?php endif; ?>
                                <div class="rsd-form-hint">
                                    <?php if (is_array($info) && isset($info['is_editable']) && !$info['is_editable']) : ?>
                                        Changes will be applied after company approval.
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align:left;margin-top:10px">
                <button type="submit" class="rsd-btn rsd-btn-lg rsd-btn-primary">
                    Save and Submit for Approval 💾
                </button>
            </div>
        </form>

    </main>
</div>