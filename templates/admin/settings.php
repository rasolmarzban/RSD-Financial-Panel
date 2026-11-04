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
            <h1>⚙️ تنظیمات پلاگین</h1>
            <p>پیکربندی کلی RSD Finance Panel</p>
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
            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:18px">🔐 تنظیمات ورود و ثبت\u200cنام</div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">فعال\u200cسازی سیستم سفارشی</label>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="rsd_fp_settings[auth_enabled]" value="1"
                            <?php checked($settings['auth_enabled'] ?? '1', '1'); ?>>
                        <span>سیستم ورود / ثبت\u200cنام و ریدایرکت سفارشی RSD Finance Panel فعال باشد.</span>
                    </label>
                    <div class="rsd-settings-desc">در صورت غیرفعال بودن، رفتار پیش\u200cفرض وردپرس (wp-login.php) برگردد.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">صفحه ورود / ثبت\u200cنام</label>
                <div>
                    <?php
                    $page_ids = get_option('rsd_fp_page_ids', []);
                    $selected_page = absint($settings['auth_page_id'] ?? ($page_ids['auth'] ?? 0));
                    wp_dropdown_pages([
                        'name'             => 'rsd_fp_settings[auth_page_id]',
                        'selected'         => $selected_page,
                        'show_option_none' => '— انتخاب کنید —',
                        'option_none_value' => '0',
                        'class'            => 'regular-text',
                    ]);
                    ?>
                    <div class="rsd-settings-desc">صفحه\u200cای که شورتکد [rsd_fp_auth] را در خود جای داده است (پیش\u200cفرض: finance-auth).</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">تأیید با پیامک (OTP)</label>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="rsd_fp_settings[auth_otp_enabled]" value="1"
                            <?php checked($settings['auth_otp_enabled'] ?? '', '1'); ?>>
                        <span>ورود و ثبت\u200cنام نیازمند کد تایید پیامکی باشد (پیش\u200cفرض: خاموش).</span>
                    </label>
                    <div class="rsd-settings-desc">در نسخه فعلی، OTP واقعی از طریق پنل ارسال نمی‌شود و فقط برای تست ساختاری فعال است.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">پنل ارسال پیامک</label>
                <div>
                    <select name="rsd_fp_settings[auth_sms_provider]">
                        <option value="none" <?php selected($settings['auth_sms_provider'] ?? 'faraz_sms', 'none'); ?>>— غیرفعال —</option>
                        <option value="faraz_sms" <?php selected($settings['auth_sms_provider'] ?? 'faraz_sms', 'faraz_sms'); ?>>پنل فراز اس\u200cام\u200cاس (FarazSMS)</option>
                    </select>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">نام کاربری پنل فراز</label>
                <div>
                    <input type="text" name="rsd_fp_settings[auth_faraz_username]" class="regular-text"
                        value="<?php echo esc_attr($settings['auth_faraz_username'] ?? ''); ?>"
                        <?php echo (empty($settings['auth_otp_enabled']) ? 'disabled style="opacity:.6;background:#f1f5f9"' : ''); ?>>
                    <div class="rsd-settings-desc">نام کاربری حساب کاربری در پنل رسمی Faraz SMS.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">رمز عبور پنل فراز</label>
                <div>
                    <input type="password" name="rsd_fp_settings[auth_faraz_password]" class="regular-text"
                        value="" autocomplete="new-password"
                        <?php echo (empty($settings['auth_otp_enabled']) ? 'disabled style="opacity:.6;background:#f1f5f9"' : ''); ?>>
                    <div class="rsd-settings-desc">
                        <?php if (!empty($settings['auth_faraz_password'])) : ?>
                            <span style="color:#059669">رمز عبور قبلی ذخیره شده است (برای تغییر مقدار جدیدی وارد کنید).</span>
                        <?php else : ?>
                            <span style="color:#64748b">رمز عبور فعلاً تنظیم نشده است.</span>
                        <?php endif; ?>
                        جهت حفظ امنیت هرگز رمز عبور به صورت متن باز نمایش داده نمی‌شود.
                    </div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">شماره فرستنده فراز</label>
                <div>
                    <input type="text" name="rsd_fp_settings[auth_faraz_sender]" class="regular-text"
                        value="<?php echo esc_attr($settings['auth_faraz_sender'] ?? ''); ?>"
                        placeholder="مثلاً 3000xxx"
                        <?php echo (empty($settings['auth_otp_enabled']) ? 'disabled style="opacity:.6;background:#f1f5f9"' : ''); ?>>
                    <div class="rsd-settings-desc">شماره خط اختصاصی ارسال کننده در پنل فراز اس\u200cام\u200cاس.</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">حداقل طول رمز عبور</label>
                <div>
                    <input type="number" name="rsd_fp_settings[auth_password_min_length]" class="small-text" min="6" max="32"
                        value="<?php echo esc_attr(absint($settings['auth_password_min_length'] ?? 8)); ?>">
                    <div class="rsd-settings-desc">حداقل تعداد کاراکتر برای رمز عبور کاربران (بین ۶ تا ۳۲).</div>
                </div>
            </div>

            <div class="rsd-settings-row">
                <label class="rsd-settings-label">ثبت\u200cنام همگانی</label>
                <div>
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="rsd_fp_settings[auth_public_registration]" value="1"
                            <?php checked($settings['auth_public_registration'] ?? '1', '1'); ?>>
                        <span>بازدیدکنندگان ناشناس بتوانند برای خود حساب کاربری بسازند.</span>
                    </label>
                    <div class="rsd-settings-desc">در صورت غیرفعال بودن، تب ثبت\u200cنام در صفحه auth پنهان می‌شود و فقط مدیر می‌تواند کاربر ایجاد کند.</div>
                </div>
            </div>
        </div>

        <div class="rsd-admin-card">
            <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:18px">💶 تنظیمات مالی و نمایش</div>
            <div class="rsd-settings-row">
                <label class="rsd-settings-label">نماد ارز</label>
                <div>
                    <input type="text" name="rsd_fp_settings[currency_symbol]"
                        value="<?php echo esc_attr($settings['currency_symbol'] ?? 'تومان'); ?>" class="regular-text">
                    <div class="rsd-settings-desc">مثلاً «تومان»</div>
                </div>
            </div>
            <div class="rsd-settings-row">
                <label class="rsd-settings-label">مکان نماد ارز</label>
                <div>
                    <select name="rsd_fp_settings[currency_position]">
                        <option value="after" <?php selected($settings['currency_position'] ?? 'after', 'after'); ?>>پس از مبلغ (1000 تومان)</option>
                        <option value="before" <?php selected($settings['currency_position'] ?? 'after', 'before'); ?>>قبل از مبلغ (تومان 1000)</option>
                    </select>
                </div>
            </div>
            <div class="rsd-settings-row">
                <label class="rsd-settings-label">فرمت تاریخ</label>
                <div>
                    <select name="rsd_fp_settings[date_format]">
                        <option value="jalali" <?php selected($settings['date_format'] ?? 'jalali', 'jalali'); ?>>شمسی</option>
                        <option value="gregorian" <?php selected($settings['date_format'] ?? 'jalali', 'gregorian'); ?>>میلادی</option>
                    </select>
                    <div class="rsd-settings-desc">برای نمایش کامل تاریخ شمسی، افزونه‌هایی مثل jDateTime نیاز است.</div>
                </div>
            </div>
            <div class="rsd-settings-row">
                <label class="rsd-settings-label">تعداد آیتم در صفحه</label>
                <div>
                    <input type="number" name="rsd_fp_settings[items_per_page]"
                        value="<?php echo esc_attr($settings['items_per_page'] ?? 20); ?>" class="small-text" min="5" max="500">
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="rsd-admin-grid cols-2">
            <div class="rsd-admin-card">
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">📁 دسته‌بندی‌های مدارک</div>
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
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">💬 دسته‌بندی‌های تیکت‌ها</div>
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
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">📊 انواع گزارش‌ها</div>
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
                <div class="rsd-admin-card-title" style="font-weight:700;margin-bottom:12px">📅 دسته‌بندی‌های تقویم</div>
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
            <button type="submit" class="rsd-btn-admin primary">💾 ذخیره تنظیمات</button>
        </div>

    </form>
</div>