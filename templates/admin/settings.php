<?php

/**
 * Admin Template: Settings Page
 *
 * @var array $settings
 */
defined('ABSPATH') || exit;

$doc_cats = $settings['document_categories'] ?? [];
$tkt_cats = $settings['ticket_categories'] ?? [];
$rep_types = $settings['report_types'] ?? [];
$cal_cats = $settings['calendar_categories'] ?? [];
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>⚙️ Plugin Settings</h1>
            <p>General configuration for RSD Finance Panel</p>
        </div>
    </div>

    <form method="post" action="options.php">
        <?php
        settings_fields('rsd_fp_settings_group');
        ?>
        <input type="hidden" name="rsd_fp_settings[document_categories]" value="">
        <input type="hidden" name="rsd_fp_settings[ticket_categories]" value="">
        <input type="hidden" name="rsd_fp_settings[report_types]" value="">
        <input type="hidden" name="rsd_fp_settings[calendar_categories]" value="">

        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:18px">🔐 Login & Registration Settings</div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Enable Custom System</label>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="rsd_fp_settings[auth_enabled]" value="1"
                            <?php checked($settings['auth_enabled'] ?? '1', '1'); ?>>
                        <span>Enable the custom RSD Finance Panel login / registration and redirect system.</span>
                    </label>
                    <div class="rsd-settings-desc">If disabled, the WordPress default behavior (wp-login.php) is restored.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Login / Registration Page</label>
                <div>
                    <?php
                    $page_ids = get_option('rsd_fp_page_ids', []);
                    $selected_page = absint($settings['auth_page_id'] ?? ($page_ids['auth'] ?? 0));
                    wp_dropdown_pages([
                        'name'             => 'rsd_fp_settings[auth_page_id]',
                        'selected'         => $selected_page,
                        'show_option_none' => '— Select —',
                        'option_none_value' => '0',
                        'class'            => 'regular-text',
                    ]);
                    ?>
                    <div class="rsd-settings-desc">The page that contains the [rsd_fp_auth] shortcode (default: finance-auth).</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">SMS Verification (OTP)</label>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="rsd_fp_settings[auth_otp_enabled]" value="1"
                            <?php checked($settings['auth_otp_enabled'] ?? '', '1'); ?>>
                        <span>Require an SMS verification code for login and registration (default: off).</span>
                    </label>
                    <div class="rsd-settings-desc">In the current version, no real OTP is sent via the panel; this option is enabled only for structural testing.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">SMS Sending Provider</label>
                <div>
                    <select name="rsd_fp_settings[auth_sms_provider]">
                        <option value="none" <?php selected($settings['auth_sms_provider'] ?? 'faraz_sms', 'none'); ?>>— Disabled —</option>
                        <option value="faraz_sms" <?php selected($settings['auth_sms_provider'] ?? 'faraz_sms', 'faraz_sms'); ?>>Faraz SMS Panel (FarazSMS)</option>
                    </select>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Faraz Panel Username</label>
                <div>
                    <input type="text" name="rsd_fp_settings[auth_faraz_username]" class="regular-text"
                        value="<?php echo esc_attr($settings['auth_faraz_username'] ?? ''); ?>"
                        <?php echo (empty($settings['auth_otp_enabled']) ? 'disabled style="opacity:.6;background:#f1f5f9"' : ''); ?>>
                    <div class="rsd-settings-desc">Account username for the official Faraz SMS panel.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Faraz Panel Password</label>
                <div>
                    <input type="password" name="rsd_fp_settings[auth_faraz_password]" class="regular-text"
                        value="" autocomplete="new-password"
                        <?php echo (empty($settings['auth_otp_enabled']) ? 'disabled style="opacity:.6;background:#f1f5f9"' : ''); ?>>
                    <div class="rsd-settings-desc">
                        <?php if (!empty($settings['auth_faraz_password'])) : ?>
                            <span style="color:#059669">A previous password is saved (enter a new value to change it).</span>
                        <?php else : ?>
                            <span style="color:#64748b">No password is set yet.</span>
                        <?php endif; ?>
                        For security, the password is never displayed in plain text.
                    </div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Faraz Sender Number</label>
                <div>
                    <input type="text" name="rsd_fp_settings[auth_faraz_sender]" class="regular-text"
                        value="<?php echo esc_attr($settings['auth_faraz_sender'] ?? ''); ?>"
                        placeholder="e.g. 3000xxx"
                        <?php echo (empty($settings['auth_otp_enabled']) ? 'disabled style="opacity:.6;background:#f1f5f9"' : ''); ?>>
                    <div class="rsd-settings-desc">Dedicated sender line number in the Faraz SMS panel.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Minimum Password Length</label>
                <div>
                    <input type="number" name="rsd_fp_settings[auth_password_min_length]" class="small-text" min="6" max="32"
                        value="<?php echo esc_attr(absint($settings['auth_password_min_length'] ?? 8)); ?>">
                    <div class="rsd-settings-desc">Minimum number of characters for user passwords (between 6 and 32).</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Public Registration</label>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="rsd_fp_settings[auth_public_registration]" value="1"
                            <?php checked($settings['auth_public_registration'] ?? '1', '1'); ?>>
                        <span>Allow anonymous visitors to create their own accounts.</span>
                    </label>
                    <div class="rsd-settings-desc">If disabled, the registration tab on the auth page is hidden and only administrators can create users.</div>
                </div>
            </div>
        </div>

        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:18px">💶 Financial & Display Settings</div>
            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Currency</label>
                <div>
                    <select name="rsd_fp_settings[currency_symbol]">
                        <option value="$" <?php selected($settings['currency_symbol'] ?? '$', '$'); ?>>Dollar ($)</option>
                        <option value="€" <?php selected($settings['currency_symbol'] ?? '$', '€'); ?>>Euro (€)</option>
                    </select>
                    <div class="rsd-settings-desc">Default currency used for all amounts.</div>
                </div>
            </div>
            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Currency Symbol Position</label>
                <div>
                    <select name="rsd_fp_settings[currency_position]">
                        <option value="before" <?php selected($settings['currency_position'] ?? 'before', 'before'); ?>>Before amount ($1000)</option>
                        <option value="after" <?php selected($settings['currency_position'] ?? 'before', 'after'); ?>>After amount (1000 $)</option>
                    </select>
                </div>
            </div>
            <div class="rsd-settings-row">
                <label class="rsd-settings-label">Items Per Page</label>
                <div>
                    <input type="number" name="rsd_fp_settings[items_per_page]"
                        value="<?php echo esc_attr($settings['items_per_page'] ?? 20); ?>" class="small-text" min="5" max="500">
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="rsd-admin-grid cols-2">
            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">📁 Document Categories</div>
                <div class="rsd-category-list" id="rsd-doc-cats">
                    <?php foreach ($doc_cats as $k => $v) : ?>
                        <div style="display:flex;gap:6px;margin-bottom:6px">
                            <input type="text" readonly value="<?php echo esc_attr($k); ?>" style="width:110px;background:#f1f5f9">
                            <input type="text" name="rsd_fp_settings[document_categories][<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr($v); ?>" style="flex:1">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">💬 Ticket Categories</div>
                <div>
                    <?php foreach ($tkt_cats as $k => $v) : ?>
                        <div style="display:flex;gap:6px;margin-bottom:6px">
                            <input type="text" readonly value="<?php echo esc_attr($k); ?>" style="width:110px;background:#f1f5f9">
                            <input type="text" name="rsd_fp_settings[ticket_categories][<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr($v); ?>" style="flex:1">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">📊 Report Types</div>
                <div>
                    <?php foreach ($rep_types as $k => $v) : ?>
                        <div style="display:flex;gap:6px;margin-bottom:6px">
                            <input type="text" readonly value="<?php echo esc_attr($k); ?>" style="width:110px;background:#f1f5f9">
                            <input type="text" name="rsd_fp_settings[report_types][<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr($v); ?>" style="flex:1">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">📅 Calendar Categories</div>
                <div>
                    <?php foreach ($cal_cats as $k => $v) : ?>
                        <div style="display:flex;gap:6px;margin-bottom:6px">
                            <input type="text" readonly value="<?php echo esc_attr($k); ?>" style="width:110px;background:#f1f5f9">
                            <input type="text" name="rsd_fp_settings[calendar_categories][<?php echo esc_attr($k); ?>]" value="<?php echo esc_attr($v); ?>" style="flex:1">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="rsd-admin-card">
            <button type="submit" class="rsd-btn-admin primary">💾 Save Settings</button>
        </div>

    </form>
</div>