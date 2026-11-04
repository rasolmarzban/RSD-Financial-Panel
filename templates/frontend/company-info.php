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
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">🏢 اطلاعات شرکت</h2>
            <div class="spacer"></div>
            <?php if (!empty($info->pending_changes)) : ?>
                <span class="rsd-badge rsd-badge-warning">
                    ⏳ تغییرات شما در انتظار تأیید کارشناس است
                </span>
            <?php endif; ?>
        </div>

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
                                        تغییرات پس از تأیید شرکت، اعمال می‌شود.
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align:left;margin-top:10px">
                <button type="submit" class="rsd-btn rsd-btn-lg rsd-btn-primary">
                    ذخیره و ارسال برای تأیید 💾
                </button>
            </div>
        </form>

    </main>
</div>
