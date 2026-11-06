<?php

/**
 * Template: Frontend Custom Auth Page (Login / Register / Lost / Reset)
 *
 * Design matches the client portal dashboard palette (gradient background,
 * white frosted-glass card, gradient logo 14px radius, LTR).
 *
 * @var string $action                    'login' | 'register' | 'lostpassword' | 'resetpass'
 * @var bool   $otp_enabled               Whether 2-step OTP SMS is enabled
 * @var bool   $public_registration       Whether self-sign-up is allowed
 * @var int    $password_min_length       Min length requirement for new passwords
 * @var string $redirect_to               Optional destination after login
 * @var string $reset_key                 When action=resetpass
 * @var string $reset_login               When action=resetpass
 * @var string $flash_message             Optional pre-rendered success/error
 * @var string $flash_type                'success' | 'error' | 'info'
 */
defined('ABSPATH') || exit;

$logo_initial = mb_substr(get_bloginfo('name') ?: 'R', 0, 1);
?>

<style>
    h1.entry-title {
        display: none;
    }

    /* Auth page specific additions (kept self-contained) */
    .rsd-fp-auth-portal {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
    }

    .rsd-auth-card {
        width: 100%;
        max-width: 460px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.7);
        border-radius: 20px;
        padding: 36px 32px 28px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
    }

    .rsd-auth-brand {
        text-align: center;
        margin-bottom: 26px;
    }

    .rsd-auth-logo {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 22px;
        margin-bottom: 12px;
        box-shadow: 0 6px 18px rgba(59, 130, 246, 0.35);
    }

    .rsd-auth-brand h2 {
        font-size: 18px !important;
        font-weight: 800 !important;
    }

    .rsd-auth-brand h1 {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .rsd-auth-brand p {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }

    .rsd-auth-tabs {
        display: flex;
        gap: 4px;
        background: #f1f5f9;
        border-radius: 12px;
        padding: 4px;
        margin-bottom: 22px;
    }

    .rsd-auth-tab {
        flex: 1;
        text-align: center;
        padding: 9px 10px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
        border-radius: 9px;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
        border: none;
        background: transparent;
        font-family: inherit;
    }

    .rsd-auth-tab:hover {
        color: #334155;
    }

    .rsd-auth-tab.active {
        background: #fff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }

    .rsd-auth-alert-zone {
        display: none;
        border-radius: 11px;
        padding: 11px 14px;
        margin-bottom: 18px;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.6;
        display: none;
        align-items: flex-start;
        gap: 8px;
    }

    .rsd-auth-alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
        display: flex;
    }

    .rsd-auth-alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        display: flex;
    }

    .rsd-auth-alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        display: flex;
    }

    .rsd-auth-alert-icon {
        line-height: 1.4;
    }

    .rsd-auth-form {
        margin: 0;
    }

    .rsd-auth-bottom-links {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        color: #64748b;
        margin-top: 14px;
    }

    .rsd-auth-bottom-links a {
        color: #3b82f6;
        font-weight: 600;
    }

    .rsd-auth-helper-note {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 6px;
        line-height: 1.6;
    }

    .rsd-auth-otp-step {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        padding: 12px 14px;
        font-size: 12px;
        color: #475569;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .rsd-auth-otp-resend {
        background: none;
        border: none;
        color: #3b82f6;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        font-size: 12px;
        padding: 0;
    }

    .rsd-auth-otp-resend:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .rsd-auth-divider {
        display: flex;
        align-items: center;
        margin: 22px 0 6px;
        color: #cbd5e1;
        font-size: 11px;
        gap: 10px;
    }

    .rsd-auth-divider::before,
    .rsd-auth-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }

    .rsd-auth-submit-row {
        margin-top: 20px;
    }

    .rsd-auth-submit-row .rsd-btn {
        width: 100%;
        justify-content: center;
    }

    .rsd-btn-loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }

    .rsd-btn-loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 14px;
        height: 14px;
        margin: -7px 0 0 -7px;
        border: 2px solid rgba(255, 255, 255, 0.35);
        border-top-color: #fff;
        border-radius: 50%;
        animation: rsd-auth-spin 0.8s linear infinite;
    }

    @keyframes rsd-auth-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .rsd-auth-remember-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 4px 0 0;
        font-size: 12px;
    }

    .rsd-auth-remember-row label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        color: #64748b;
        font-weight: 500;
        cursor: pointer;
    }

    .rsd-auth-remember-row input {
        accent-color: #3b82f6;
        margin: 0;
    }

    @media (max-width: 480px) {
        .rsd-auth-card {
            padding: 26px 20px;
        }
    }
</style>

<div class="rsd-fp-auth-portal rsd-fp-portal">
    <div class="rsd-auth-card">

        <!-- Brand / Logo -->
        <div class="rsd-auth-brand">
            <!-- <div class="rsd-auth-logo"><?php // echo esc_html($logo_initial); 
                                            ?></div> -->
            <h2><?php esc_html_e('Account Login and Client Sign Up', 'rsd-finance-panel'); ?></h2>
        </div>

        <!-- Tabs -->
        <div class="rsd-auth-tabs" role="tablist" aria-label="Select form">
            <button type="button" class="rsd-auth-tab" data-auth-tab="login" role="tab">
                <?php esc_html_e('Login', 'rsd-finance-panel'); ?>
            </button>
            <?php if ($public_registration) : ?>
                <button type="button" class="rsd-auth-tab" data-auth-tab="register" role="tab">
                    <?php esc_html_e('Sign Up', 'rsd-finance-panel'); ?>
                </button>
            <?php endif; ?>
            <button type="button" class="rsd-auth-tab" data-auth-tab="lostpassword" role="tab">
                <?php esc_html_e('Forgot Password', 'rsd-finance-panel'); ?>
            </button>
        </div>

        <!-- Alert zone -->
        <div class="rsd-auth-alert-zone" aria-live="polite"></div>

        <!-- ================= LOGIN FORM ================= -->
        <form class="rsd-auth-form" data-auth-form="login" method="post" style="display:none" autocomplete="on" novalidate>
            <div class="rsd-form-row rsd-auth-step1 rsd-auth-step-wrap">
                <label for="rsd-auth-login-identifier">
                    <?php esc_html_e('Mobile Number or Email', 'rsd-finance-panel'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text"
                    id="rsd-auth-login-identifier"
                    name="identifier"
                    dir="ltr"
                    spellcheck="false"
                    placeholder="e.g.,  info@example.com , 09123456789"
                    autocomplete="username">
                <div class="rsd-auth-helper-note">
                    <?php esc_html_e('You can use your registered mobile number or email.', 'rsd-finance-panel'); ?>
                </div>
            </div>

            <div class="rsd-form-row rsd-auth-step1 rsd-auth-step-wrap">
                <label for="rsd-auth-login-password">
                    <?php esc_html_e('Password', 'rsd-finance-panel'); ?>
                    <span class="required">*</span>
                </label>
                <input type="password"
                    id="rsd-auth-login-password"
                    name="password"
                    dir="ltr"
                    placeholder="••••••••"
                    autocomplete="current-password">
            </div>

            <?php if ($otp_enabled) : ?>
                <div class="rsd-auth-step1 rsd-auth-step-wrap" style="margin-bottom:16px">
                    <button type="button"
                        class="rsd-btn rsd-btn-outline rsd-auth-send-otp"
                        data-context="login"
                        style="width:100%">
                        <?php esc_html_e('Send Verification Code (SMS)', 'rsd-finance-panel'); ?>
                    </button>
                </div>

                <div class="rsd-auth-step2 rsd-auth-step-wrap" style="display:none">
                    <div class="rsd-auth-otp-step">
                        <span>
                            <?php esc_html_e('A 6-digit code for number', 'rsd-finance-panel'); ?>
                            <span class="rsd-auth-otp-phone" dir="ltr" style="font-weight:700;color:#0f172a">-</span>
                            <?php esc_html_e('was sent.', 'rsd-finance-panel'); ?>
                        </span>
                        <button type="button" class="rsd-auth-otp-resend" data-context="login">
                            <?php esc_html_e('Resend Code', 'rsd-finance-panel'); ?>
                        </button>
                    </div>
                    <div class="rsd-form-row" style="margin-bottom:14px">
                        <label for="rsd-auth-login-otp">
                            <?php esc_html_e('6-Digit Verification Code', 'rsd-finance-panel'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text"
                            id="rsd-auth-login-otp"
                            name="otp"
                            dir="ltr"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="\d{6}"
                            placeholder="000000"
                            class="rsd-auth-otp-input"
                            autocomplete="one-time-code">
                    </div>
                </div>
            <?php else : ?>
                <input type="hidden" name="otp" value="">
            <?php endif; ?>

            <div class="rsd-auth-remember-row">
                <label for="rsd-auth-login-remember">
                    <input type="checkbox"
                        id="rsd-auth-login-remember"
                        name="remember"
                        value="1">
                    <?php esc_html_e('Remember me', 'rsd-finance-panel'); ?>
                </label>
                <button type="button"
                    class="rsd-auth-tab rsd-auth-inline-link"
                    data-auth-tab="lostpassword"
                    style="background:none;border:none;padding:0;width:auto;box-shadow:none;color:#3b82f6;font-weight:700;font-size:12px">
                    <?php esc_html_e('Forgot your password?', 'rsd-finance-panel'); ?>
                </button>
            </div>

            <input type="hidden" id="rsd-auth-login-redirect" name="redirect_to"
                value="<?php echo esc_attr($redirect_to); ?>">

            <div class="rsd-auth-submit-row">
                <button type="submit"
                    class="rsd-btn rsd-btn-primary rsd-btn-lg"
                    data-loading-text="<?php esc_attr_e('Signing in ...', 'rsd-finance-panel'); ?>">
                    <?php esc_html_e('Sign in to your panel', 'rsd-finance-panel'); ?>
                </button>
            </div>

            <?php if ($public_registration) : ?>
                <div class="rsd-auth-bottom-links">
                    <span><?php esc_html_e("Don't have an account?", 'rsd-finance-panel'); ?></span>
                    <button type="button"
                        class="rsd-auth-tab"
                        data-auth-tab="register"
                        style="background:none;border:none;padding:0;width:auto;box-shadow:none;color:#3b82f6;font-weight:700;font-size:12px">
                        <?php esc_html_e('Quick Sign Up', 'rsd-finance-panel'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </form>

        <!-- ================= REGISTER FORM ================= -->
        <?php if ($public_registration) : ?>
            <form class="rsd-auth-form" data-auth-form="register" method="post" style="display:none" autocomplete="on" novalidate>
                <div class="rsd-form-row rsd-auth-step-wrap rsd-auth-step1">
                    <label for="rsd-auth-register-phone">
                        <?php esc_html_e('Mobile Number', 'rsd-finance-panel'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text"
                        id="rsd-auth-register-phone"
                        name="phone"
                        dir="ltr"
                        spellcheck="false"
                        placeholder="09123456789"
                        maxlength="15"
                        autocomplete="tel">
                    <div class="rsd-auth-helper-note">
                        <?php esc_html_e('The mobile number must be 11 digits and start with the 09 prefix. It is used for account recovery and notifications.', 'rsd-finance-panel'); ?>
                    </div>
                </div>

                <div class="rsd-form-row rsd-auth-step-wrap rsd-auth-step1">
                    <label for="rsd-auth-register-email">
                        <?php esc_html_e('Email (optional)', 'rsd-finance-panel'); ?>
                    </label>
                    <input type="email"
                        id="rsd-auth-register-email"
                        name="email"
                        dir="ltr"
                        placeholder="info@example.com"
                        autocomplete="email">
                    <div class="rsd-auth-helper-note">
                        <?php esc_html_e('If you do not enter an email, password recovery via email will not be possible.', 'rsd-finance-panel'); ?>
                    </div>
                </div>

                <?php if ($otp_enabled) : ?>
                    <div class="rsd-auth-step1 rsd-auth-step-wrap" style="margin-bottom:16px">
                        <button type="button"
                            class="rsd-btn rsd-btn-outline rsd-auth-send-otp"
                            data-context="register"
                            style="width:100%">
                            <?php esc_html_e('Send Verification Code (SMS)', 'rsd-finance-panel'); ?>
                        </button>
                    </div>

                    <div class="rsd-auth-step2 rsd-auth-step-wrap" style="display:none">
                        <div class="rsd-auth-otp-step">
                            <span>
                                <?php esc_html_e('A 6-digit code for number', 'rsd-finance-panel'); ?>
                                <span class="rsd-auth-otp-phone" dir="ltr" style="font-weight:700;color:#0f172a">-</span>
                                <?php esc_html_e('was sent.', 'rsd-finance-panel'); ?>
                            </span>
                            <button type="button" class="rsd-auth-otp-resend" data-context="register">
                                <?php esc_html_e('Resend Code', 'rsd-finance-panel'); ?>
                            </button>
                        </div>
                        <div class="rsd-form-row" style="margin-bottom:14px">
                            <label for="rsd-auth-register-otp">
                                <?php esc_html_e('6-Digit Verification Code', 'rsd-finance-panel'); ?>
                                <span class="required">*</span>
                            </label>
                            <input type="text"
                                id="rsd-auth-register-otp"
                                name="otp"
                                dir="ltr"
                                maxlength="6"
                                inputmode="numeric"
                                pattern="\d{6}"
                                placeholder="000000"
                                class="rsd-auth-otp-input"
                                autocomplete="one-time-code">
                        </div>
                    </div>
                <?php else : ?>
                    <input type="hidden" name="otp" value="">
                <?php endif; ?>

                <div class="rsd-form-row rsd-auth-step-wrap rsd-auth-step2" style="display:<?php echo $otp_enabled ? 'none' : 'block'; ?>">
                    <label for="rsd-auth-register-password">
                        <?php esc_html_e('Password', 'rsd-finance-panel'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="password"
                        id="rsd-auth-register-password"
                        name="password"
                        dir="ltr"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        minlength="<?php echo esc_attr($password_min_length); ?>">
                    <div class="rsd-auth-helper-note">
                        <?php echo esc_html(sprintf(esc_html__('At least %d characters including letters and numbers is recommended.', 'rsd-finance-panel'), $password_min_length)); ?>
                    </div>
                </div>

                <div class="rsd-form-row rsd-auth-step-wrap rsd-auth-step2" style="display:<?php echo $otp_enabled ? 'none' : 'block'; ?>">
                    <label for="rsd-auth-register-password-confirm">
                        <?php esc_html_e('Confirm Password', 'rsd-finance-panel'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="password"
                        id="rsd-auth-register-password-confirm"
                        name="password_confirm"
                        dir="ltr"
                        placeholder="••••••••"
                        autocomplete="new-password">
                </div>

                <input type="hidden" id="rsd-auth-register-redirect" name="redirect_to"
                    value="<?php echo esc_attr($redirect_to); ?>">

                <div class="rsd-auth-submit-row">
                    <button type="submit"
                        class="rsd-btn rsd-btn-primary rsd-btn-lg">
                        <?php esc_html_e('Create Account and Sign In', 'rsd-finance-panel'); ?>
                    </button>
                </div>

                <div class="rsd-auth-bottom-links">
                    <span><?php esc_html_e('Already registered?', 'rsd-finance-panel'); ?></span>
                    <button type="button"
                        class="rsd-auth-tab"
                        data-auth-tab="login"
                        style="background:none;border:none;padding:0;width:auto;box-shadow:none;color:#3b82f6;font-weight:700;font-size:12px">
                        <?php esc_html_e('Sign in to account', 'rsd-finance-panel'); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <!-- ================= LOST PASSWORD FORM ================= -->
        <form class="rsd-auth-form" data-auth-form="lostpassword" method="post" style="display:none" novalidate>
            <div class="rsd-form-row">
                <label for="rsd-auth-lost-identifier">
                    <?php esc_html_e('Account Mobile Number or Email', 'rsd-finance-panel'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text"
                    id="rsd-auth-lost-identifier"
                    name="identifier"
                    dir="ltr"
                    spellcheck="false"
                    placeholder="Registered mobile number or email">
                <div class="rsd-auth-helper-note">
                    <?php esc_html_e('If a valid email is registered for your account, a password reset link will be sent to that address.', 'rsd-finance-panel'); ?>
                    <br>
                    <strong style="color:#b91c1c">
                        <?php esc_html_e('Note: If you did not enter an email during sign up, please contact the system administrator.', 'rsd-finance-panel'); ?>
                    </strong>
                </div>
            </div>

            <div class="rsd-auth-submit-row">
                <button type="submit"
                    class="rsd-btn rsd-btn-primary rsd-btn-lg">
                    <?php esc_html_e('Send Password Reset Link', 'rsd-finance-panel'); ?>
                </button>
            </div>

            <div class="rsd-auth-bottom-links">
                <span><?php esc_html_e('Remembered it?', 'rsd-finance-panel'); ?></span>
                <button type="button"
                    class="rsd-auth-tab"
                    data-auth-tab="login"
                    style="background:none;border:none;padding:0;width:auto;box-shadow:none;color:#3b82f6;font-weight:700;font-size:12px">
                    <?php esc_html_e('Back to login page', 'rsd-finance-panel'); ?>
                </button>
            </div>
        </form>

        <!-- ================= RESET PASSWORD FORM ================= -->
        <form class="rsd-auth-form" data-auth-form="resetpass" method="post" style="display:none" novalidate>
            <input type="hidden" id="rsd-auth-resetpass-key" name="key" value="<?php echo esc_attr($reset_key); ?>">
            <input type="hidden" id="rsd-auth-resetpass-login" name="login" value="<?php echo esc_attr($reset_login); ?>">

            <div class="rsd-form-row">
                <label for="rsd-auth-resetpass-new">
                    <?php esc_html_e('New Password', 'rsd-finance-panel'); ?>
                    <span class="required">*</span>
                </label>
                <input type="password"
                    id="rsd-auth-resetpass-new"
                    name="new_password"
                    dir="ltr"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    minlength="<?php echo esc_attr($password_min_length); ?>">
                <div class="rsd-auth-helper-note">
                    <?php echo esc_html(sprintf(esc_html__('At least %d characters.', 'rsd-finance-panel'), $password_min_length)); ?>
                </div>
            </div>

            <div class="rsd-form-row">
                <label for="rsd-auth-resetpass-new-confirm">
                    <?php esc_html_e('Confirm New Password', 'rsd-finance-panel'); ?>
                    <span class="required">*</span>
                </label>
                <input type="password"
                    id="rsd-auth-resetpass-new-confirm"
                    name="new_password_confirm"
                    dir="ltr"
                    placeholder="••••••••"
                    autocomplete="new-password">
            </div>

            <div class="rsd-auth-submit-row">
                <button type="submit"
                    class="rsd-btn rsd-btn-primary rsd-btn-lg">
                    <?php esc_html_e('Save New Password', 'rsd-finance-panel'); ?>
                </button>
            </div>

            <div class="rsd-auth-bottom-links">
                <span>&nbsp;</span>
                <button type="button"
                    class="rsd-auth-tab"
                    data-auth-tab="login"
                    style="background:none;border:none;padding:0;width:auto;box-shadow:none;color:#3b82f6;font-weight:700;font-size:12px">
                    <?php esc_html_e('Login Page', 'rsd-finance-panel'); ?>
                </button>
            </div>
        </form>

        <?php if (!empty($flash_message)) : ?>
            <script>
                /* Flash message passed from server-side (e.g. loggedout=true) */
                jQuery(function($) {
                    var text = <?php echo wp_json_encode($flash_message); ?>;
                    var type = <?php echo wp_json_encode($flash_type ?: 'success'); ?>;
                    var $z = $('.rsd-auth-alert-zone');
                    if ($z.length && text) {
                        var icon = type === 'success' ? '✅' : (type === 'error' ? '⚠️' : 'ℹ️');
                        $z.removeClass().addClass('rsd-auth-alert-zone rsd-auth-alert-' + type)
                            .html('<span class="rsd-auth-alert-icon">' + icon + '</span><span class="rsd-auth-alert-text">' + text + '</span>')
                            .show();
                    }
                });
            </script>
        <?php endif; ?>

    </div>
</div>