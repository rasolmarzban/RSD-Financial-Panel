<?php

/**
 * Auth module class
 *
 * Handles custom authentication (register, login, lost password, OTP SMS)
 * to replace WordPress default wp-login.php for client users.
 *
 * @package RSD_Finance_Panel
 * @subpackage Modules
 */

namespace RSD\FinancePanel\Modules;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Plugin;
use RSD\FinancePanel\Core\Database;
use RSD\FinancePanel\SMS\FarazSMS;
use WP_Error;
use WP_User;

class Auth
{
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_ATTEMPT_TTL  = 900;
    const OTP_TTL            = 300;
    const OTP_MAX_WRONG      = 3;

    /**
     * Initialize module hooks
     *
     * @return void
     */
    public function init()
    {
        if (!$this->is_auth_enabled()) {
            return;
        }

        add_filter('login_url', [$this, 'filter_login_url'], 10, 2);
        add_filter('logout_url', [$this, 'filter_logout_url'], 10, 2);
        add_filter('lostpassword_url', [$this, 'filter_lostpass_url'], 10, 2);
        add_filter('login_redirect', [$this, 'filter_login_redirect'], 999, 3);
        add_filter('registration_redirect', [$this, 'filter_registration_redirect'], 999, 2);
        add_filter('show_admin_bar', [$this, 'filter_show_admin_bar_client'], 999);
        add_action('after_setup_theme', [$this, 'action_hide_admin_bar_for_clients_frontend'], 1);

        add_action('wp_logout', [$this, 'action_wp_logout_redirect_client']);
        add_action('login_init', [$this, 'action_login_init_redirect_non_admin']);
        add_action('admin_init', [$this, 'action_admin_init_block_clients'], 1);
        add_action('template_redirect', [$this, 'action_template_redirect_auth_to_dashboard_if_logged_in_client'], 5);

        add_action('init', [$this, 'maybe_create_auth_page_fallback'], 5);

        add_action('after_password_reset', [$this, 'after_password_reset_cleanup'], 10, 2);
    }

    /**
     * Check whether custom auth system is enabled via settings.
     *
     * @return bool
     */
    public function is_auth_enabled(): bool
    {
        return (bool) Plugin::get_setting('auth_enabled', '1');
    }

    /**
     * Check whether OTP SMS authentication is enabled.
     *
     * @return bool
     */
    public function is_otp_enabled(): bool
    {
        return (bool) Plugin::get_setting('auth_otp_enabled', '');
    }

    /**
     * Check whether public (self-service) registration is allowed.
     *
     * @return bool
     */
    public function is_public_registration(): bool
    {
        return (bool) Plugin::get_setting('auth_public_registration', '1');
    }

    /**
     * Get the configured minimum password length (clamped 6-32).
     *
     * @return int
     */
    public function get_password_min_length(): int
    {
        $len = (int) Plugin::get_setting('auth_password_min_length', 8);
        if ($len < 6) {
            $len = 6;
        }
        if ($len > 32) {
            $len = 32;
        }
        return $len;
    }

    /**
     * Filters the default WordPress login URL to redirect clients to our custom auth page.
     *
     * @param string $login_url
     * @param string $redirect
     * @return string
     */
    public function filter_login_url($login_url, $redirect)
    {
        $auth_url = Plugin::get_page_url('auth');
        if (!$auth_url || !$this->is_auth_enabled()) {
            return $login_url;
        }

        if (!empty($redirect)) {
            $auth_url = add_query_arg('redirect_to', urlencode($redirect), $auth_url);
        }

        return $auth_url;
    }

    /**
     * Filters the logout URL.
     *
     * @param string $logout_url
     * @param string $redirect
     * @return string
     */
    public function filter_logout_url($logout_url, $redirect)
    {
        if (is_user_logged_in() && $this->is_client_logged_in_not_admin()) {
            $auth_url = Plugin::get_page_url('auth');
            if ($auth_url) {
                if (strpos($logout_url, 'action=logout') !== false && function_exists('wp_create_nonce')) {
                    if (strpos($logout_url, '_wpnonce') === false) {
                        $logout_url = add_query_arg('_wpnonce', wp_create_nonce('log-out'), $logout_url);
                    }
                }
            }
        }
        return $logout_url;
    }

    /**
     * Filters the lost password URL to use our custom auth page.
     *
     * @param string $lostpassword_url
     * @param string $redirect
     * @return string
     */
    public function filter_lostpass_url($lostpassword_url, $redirect)
    {
        $auth_url = Plugin::get_page_url('auth');
        if (!$auth_url || !$this->is_auth_enabled()) {
            return $lostpassword_url;
        }
        $auth_url = add_query_arg('action', 'lostpassword', $auth_url);
        if (!empty($redirect)) {
            $auth_url = add_query_arg('redirect_to', urlencode($redirect), $auth_url);
        }
        return $auth_url;
    }

    /**
     * On wp_logout action — if a client user logs out, redirect to the custom
     * auth page with a friendly success notice (skips wp-login entirely).
     *
     * @return void
     */
    public function action_wp_logout_redirect_client()
    {
        if (!$this->is_auth_enabled()) {
            return;
        }

        $user_id = (int) ($GLOBALS['wp_users'] ?? 0);
        if (!$user_id && function_exists('get_current_user_id')) {
            $user_id = get_current_user_id();
        }

        if ($user_id && !Plugin::is_admin_user($user_id) && Plugin::is_client_user($user_id)) {
            $auth_url = Plugin::get_page_url('auth');
            if ($auth_url) {
                $auth_url = add_query_arg('loggedout', 'true', $auth_url);
                wp_redirect($auth_url);
                exit;
            }
        }
    }

    /**
     * Intercept wp-login.php requests. Admin users keep using core wp-login;
     * everyone else (clients, guests) is bounced to the custom auth page with
     * the correct ?action= preserved.
     *
     * @return void
     */
    public function action_login_init_redirect_non_admin()
    {
        if (!$this->is_auth_enabled()) {
            return;
        }

        $action = isset($_REQUEST['action']) ? trim((string)$_REQUEST['action']) : 'login';

        if (is_user_logged_in()) {
            $uid = get_current_user_id();
            if (Plugin::is_admin_user($uid)) {
                return;
            }
        }

        $intercept = in_array($action, ['login', 'register', 'lostpassword', 'retrievepassword', 'rp', 'resetpass'], true);
        if (!$intercept) {
            $post_action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
            if (!in_array($post_action, ['lostpassword', 'retrievepassword', 'rp', 'resetpass', 'login'], true)) {
                return;
            }
        }

        $bypass = false;
        if (isset($_GET['rsd_admin']) && trim((string)$_GET['rsd_admin']) === '1') {
            $bypass = true;
        }
        if (!$bypass && !empty($_REQUEST['redirect_to'])) {
            $rt = (string)$_REQUEST['redirect_to'];
            if (strpos($rt, 'wp-admin') !== false) {
                $bypass = true;
            }
        }
        if (!$bypass && !empty($_SERVER['HTTP_REFERER'])) {
            $ref = (string)$_SERVER['HTTP_REFERER'];
            if (strpos($ref, 'wp-admin') !== false || strpos($ref, 'wp-login.php') !== false) {
                $bypass = true;
            }
        }
        if ($bypass) {
            return;
        }

        $auth_url = Plugin::get_page_url('auth');
        if (!$auth_url) {
            return;
        }

        $allowed = ['login', 'register', 'lostpassword', 'retrievepassword', 'rp', 'resetpass'];
        $target_action = in_array($action, $allowed, true) ? $action : 'login';
        if ($target_action === 'retrievepassword') {
            $target_action = 'lostpassword';
        }
        if ($target_action === 'rp') {
            $target_action = 'resetpass';
        }

        if ($target_action !== 'login') {
            $auth_url = add_query_arg('action', $target_action, $auth_url);
        }

        if ($target_action === 'resetpass') {
            if (isset($_GET['key'])) {
                $auth_url = add_query_arg('key', trim((string)$_GET['key']), $auth_url);
            }
            if (isset($_GET['login'])) {
                $auth_url = add_query_arg('login', trim((string)$_GET['login']), $auth_url);
            }
            if (isset($_GET['error'])) {
                $auth_url = add_query_arg('error', trim((string)$_GET['error']), $auth_url);
            }
        }

        if (!empty($_REQUEST['redirect_to'])) {
            $auth_url = add_query_arg('redirect_to', urlencode(trim((string)$_REQUEST['redirect_to'])), $auth_url);
        }

        wp_redirect($auth_url);
        exit;
    }

    /**
     * Fallback page creator. Activator::create_pages only runs on activation hook.
     * If user had already activated the plugin before auth page was introduced,
     * slug=finance-auth would be missing → 404 → users can't login.
     *
     * This method runs early on every init (but is essentially a no-op after
     * the first time it succeeds because it short-circuits on existing page).
     *
     * @return void
     */
    public function maybe_create_auth_page_fallback()
    {
        if (is_admin()) {
            return;
        }

        $page_ids = get_option('rsd_fp_page_ids', []);
        if (!empty($page_ids['auth']) && get_post((int)$page_ids['auth'])) {
            return;
        }

        $existing = get_page_by_path('finance-auth');
        if ($existing && $existing instanceof WP_Post) {
            $page_ids['auth'] = (int)$existing->ID;
            update_option('rsd_fp_page_ids', $page_ids);
            return;
        }

        $page = [
            'post_title'   => __('ورود و ثبت\u200cنام پنل مالی', 'rsd-finance-panel'),
            'post_content' => '[rsd_fp_auth]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'finance-auth',
        ];

        $inserted = wp_insert_post($page);
        if ($inserted && !is_wp_error($inserted)) {
            update_post_meta($inserted, '_rsd_fp_page_type', 'auth');
            $page_ids['auth'] = (int)$inserted;
            update_option('rsd_fp_page_ids', $page_ids);
        }
    }

    /**
     * Helper: whether current (logged-in) user is a rsd_client but NOT admin.
     * Used to decide redirects on logout / auth page.
     *
     * @return bool
     */
    private function is_client_logged_in_not_admin(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }
        $uid = get_current_user_id();
        if (!$uid) {
            return false;
        }
        if (Plugin::is_admin_user($uid)) {
            return false;
        }
        return Plugin::is_client_user($uid);
    }

    // ============================================================
    //  REGISTER
    // ============================================================

    /**
     * Handle a registration request.
     *
     * @param string $phone             Mobile number (required, will be normalised to 09xxxxxxxxx)
     * @param string $email             Optional email address
     * @param string $password          Password
     * @param string $password_confirm  Password confirmation (must match)
     * @param string $otp               Optional OTP code (checked if OTP enabled)
     * @return int|WP_Error             New user ID on success, WP_Error otherwise
     */
    public function handle_register($phone, $email, $password, $password_confirm, $otp = '')
    {
        if (!$this->is_public_registration()) {
            return new WP_Error(
                'registration_disabled',
                esc_html__('ثبت\u200cنام عمومی در حال حاضر غیرفعال است. لطفاً با مدیر تماس بگیرید.', 'rsd-finance-panel')
            );
        }

        $phone = FarazSMS::normalise_phone($phone);
        if (!FarazSMS::is_valid_iran_mobile($phone)) {
            return new WP_Error(
                'invalid_phone',
                esc_html__('شماره موبایل وارد شده معتبر نیست. لطفاً شماره ۱۱ رقمی با پیشوند ۰۹ وارد کنید.', 'rsd-finance-panel')
            );
        }

        $email = $email === null ? '' : trim((string)$email);
        if ($email !== '' && !is_email($email)) {
            return new WP_Error(
                'invalid_email',
                esc_html__('ایمیل وارد شده معتبر نیست.', 'rsd-finance-panel')
            );
        }

        $dup_user = $this->find_user_by_identifier($phone);
        if ($dup_user instanceof WP_User) {
            return new WP_Error(
                'phone_exists',
                esc_html__('این شماره موبایل قبلاً در سیستم ثبت شده است. لطفاً وارد شوید.', 'rsd-finance-panel')
            );
        }

        if ($email !== '') {
            $dup_email = email_exists($email);
            if ($dup_email) {
                return new WP_Error(
                    'email_exists',
                    esc_html__('این ایمیل قبلاً در سیستم ثبت شده است.', 'rsd-finance-panel')
                );
            }
        }

        $min_len = $this->get_password_min_length();
        $password = (string)$password;
        $password_confirm = (string)$password_confirm;

        if ($password === '' || mb_strlen($password) < $min_len) {
            return new WP_Error(
                'password_short',
                sprintf(
                    esc_html__('رمز عبور باید حداقل %d کاراکتر باشد.', 'rsd-finance-panel'),
                    $min_len
                )
            );
        }

        if ($password !== $password_confirm) {
            return new WP_Error(
                'password_mismatch',
                esc_html__('رمز عبور و تکرار آن با هم مطابقت ندارند.', 'rsd-finance-panel')
            );
        }

        if ($this->is_otp_enabled()) {
            if ($otp === '' || !$this->verify_otp($phone, $otp)) {
                return new WP_Error(
                    'invalid_otp',
                    esc_html__('کد تأیید وارد شده معتبر نیست یا منقضی شده است.', 'rsd-finance-panel')
                );
            }
            $this->clear_otp($phone);
        }

        $unique_login = $this->generate_unique_user_login($phone, $email);
        $display_name  = $this->build_display_name($phone, $email);

        $userdata = [
            'user_login'   => $unique_login,
            'user_pass'    => $password,
            'user_email'   => $email !== '' ? $email : '',
            'display_name' => $display_name,
            'role'         => 'rsd_client',
            'nickname'     => $display_name,
        ];

        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        update_user_meta($user_id, 'rsd_fp_phone', $phone);
        if ($email === '') {
            update_user_meta($user_id, 'rsd_fp_email_fictional', '1');
        }

        $this->insert_empty_company_row((int)$user_id);

        do_action('rsd_fp_after_register', (int)$user_id, $phone, $email);

        return (int)$user_id;
    }

    /**
     * After registration: sign the user in automatically, respecting remember-me
     * style session lifetime.
     *
     * @param int $user_id
     * @param bool $remember
     * @return void
     */
    public function auto_login_user($user_id, $remember = true): void
    {
        $user_id = (int)$user_id;
        if (!$user_id) {
            return;
        }
        $user = get_user_by('id', $user_id);
        if (!$user instanceof WP_User) {
            return;
        }

        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, $remember);
        do_action('wp_login', $user->user_login, $user);
    }

    // ============================================================
    //  LOGIN
    // ============================================================

    /**
     * Handle a login request (identifier = phone | email | username).
     *
     * @param string $identifier
     * @param string $password
     * @param bool $remember
     * @param string $otp
     * @return WP_User|WP_Error
     */
    public function handle_login($identifier, $password, $remember = false, $otp = '')
    {
        $identifier_raw = trim((string)$identifier);
        $password       = (string)$password;

        if ($identifier_raw === '' || $password === '') {
            return new WP_Error(
                'empty_fields',
                esc_html__('لطفاً شناسه و رمز عبور را وارد کنید.', 'rsd-finance-panel')
            );
        }

        if ($this->is_brute_blocked($identifier_raw)) {
            return new WP_Error(
                'rate_limited',
                esc_html__('تعداد تلاش‌های ناموفق شما بیش از حد مجاز بوده است. لطفاً ۱۵ دقیقه دیگر دوباره تلاش کنید.', 'rsd-finance-panel')
            );
        }

        $user = $this->find_user_by_identifier($identifier_raw);
        if (!$user instanceof WP_User) {
            $this->record_failed_attempt($identifier_raw);
            return new WP_Error(
                'invalid_credentials',
                esc_html__('شناسه یا رمز عبور اشتباه است.', 'rsd-finance-panel')
            );
        }

        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            $this->record_failed_attempt($identifier_raw);
            return new WP_Error(
                'invalid_credentials',
                esc_html__('شناسه یا رمز عبور اشتباه است.', 'rsd-finance-panel')
            );
        }

        if ($this->is_otp_enabled()) {
            $phone = get_user_meta($user->ID, 'rsd_fp_phone', true);
            if (!$phone) {
                $phone = FarazSMS::normalise_phone($identifier_raw);
            }
            if (FarazSMS::is_valid_iran_mobile($phone)) {
                if ($otp === '' || !$this->verify_otp($phone, $otp)) {
                    return new WP_Error(
                        'invalid_otp',
                        esc_html__('کد تأیید وارد شده معتبر نیست یا منقضی شده است.', 'rsd-finance-panel')
                    );
                }
                $this->clear_otp($phone);
            }
        }

        $this->clear_failed_attempts($identifier_raw);

        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, (bool)$remember);
        do_action('wp_login', $user->user_login, $user);

        return $user;
    }

    // ============================================================
    //  LOST / RESET PASSWORD
    // ============================================================

    /**
     * Handle lost-password (password reset key generation) flow.
     *
     * @param string $identifier  phone | email | username
     * @return true|WP_Error
     */
    public function handle_lostpassword($identifier)
    {
        $identifier = trim((string)$identifier);
        if ($identifier === '') {
            return new WP_Error(
                'empty_identifier',
                esc_html__('لطفاً شماره موبایل یا ایمیل خود را وارد کنید.', 'rsd-finance-panel')
            );
        }

        $user = $this->find_user_by_identifier($identifier);
        if (!$user instanceof WP_User) {
            return new WP_Error(
                'user_not_found',
                esc_html__('کاربری با این مشخصات پیدا نشد.', 'rsd-finance-panel')
            );
        }

        $is_email_valid = !empty($user->user_email) && is_email($user->user_email)
            && !(bool) get_user_meta($user->ID, 'rsd_fp_email_fictional', true);

        if (!$is_email_valid) {
            return new WP_Error(
                'no_valid_email',
                esc_html__('برای این حساب کاربری ایمیل معتبری ثبت نشده است. لطفاً با مدیر سامانه تماس بگیرید تا رمز عبور برای شما بازنشانی شود.', 'rsd-finance-panel')
            );
        }

        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            return $key;
        }

        $auth_url = Plugin::get_page_url('auth');
        $reset_url = add_query_arg(
            [
                'action' => 'resetpass',
                'key'    => rawurlencode($key),
                'login'  => rawurlencode($user->user_login),
            ],
            $auth_url ? $auth_url : home_url('/')
        );

        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $subject  = sprintf(esc_html__('[%s] بازنشانی رمز عبور', 'rsd-finance-panel'), $blogname);

        $phone_meta = get_user_meta($user->ID, 'rsd_fp_phone', true);
        $phone_display = $phone_meta ? "\n" . sprintf(esc_html__('شماره موبایل ثبت‌شده: %s', 'rsd-finance-panel'), $phone_meta) . "\n" : '';

        $message  = sprintf(esc_html__('سلام %s،', 'rsd-finance-panel'), $user->display_name) . "\r\n\r\n";
        $message .= esc_html__('درخواست بازنشانی رمز عبور برای پنل مالی ارسال شده است.', 'rsd-finance-panel') . "\r\n";
        $message .= esc_html__('اگر این درخواست توسط شما ارسال نشده است، این پیام را نادیده بگیرید.', 'rsd-finance-panel') . "\r\n\r\n";
        $message .= esc_html__('برای تعیین رمز عبور جدید روی لینک زیر کلیک کنید:', 'rsd-finance-panel') . "\r\n";
        $message .= $reset_url . "\r\n\r\n";
        $message .= $phone_display;
        $message .= sprintf(esc_html__('این لینک تا %s دقیقه معتبر است.', 'rsd-finance-panel'), (int)(HOUR_IN_SECONDS / 60)) . "\r\n";

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $sent = wp_mail($user->user_email, $subject, $message, $headers);
        if (!$sent) {
            return new WP_Error(
                'email_failed',
                esc_html__('ارسال ایمیل بازنشانی با خطا مواجه شد. لطفاً بعداً دوباره تلاش کنید یا با مدیر تماس بگیرید.', 'rsd-finance-panel')
            );
        }

        return true;
    }

    /**
     * Validate the password-reset key+login combo, then set the new password.
     *
     * @param string $key
     * @param string $login
     * @param string $new_pass
     * @param string $new_confirm
     * @return true|WP_Error
     */
    public function handle_resetpass($key, $login, $new_pass, $new_confirm)
    {
        $key = trim((string)$key);
        $login = trim((string)$login);
        $new_pass = (string)$new_pass;
        $new_confirm = (string)$new_confirm;

        if ($key === '' || $login === '') {
            return new WP_Error(
                'invalid_reset_request',
                esc_html__('درخواست بازنشانی معتبر نیست. لطفاً از طریق «فراموشی رمز عبور» دوباره درخواست دهید.', 'rsd-finance-panel')
            );
        }

        $min_len = $this->get_password_min_length();
        if ($new_pass === '' || mb_strlen($new_pass) < $min_len) {
            return new WP_Error(
                'password_short',
                sprintf(
                    esc_html__('رمز عبور باید حداقل %d کاراکتر باشد.', 'rsd-finance-panel'),
                    $min_len
                )
            );
        }

        if ($new_pass !== $new_confirm) {
            return new WP_Error(
                'password_mismatch',
                esc_html__('رمز عبور جدید و تکرار آن با هم مطابقت ندارند.', 'rsd-finance-panel')
            );
        }

        $user = check_password_reset_key($key, $login);
        if (is_wp_error($user)) {
            if ($user->get_error_code() === 'expired_key') {
                return new WP_Error(
                    'expired_key',
                    esc_html__('لینک بازنشانی رمز عبور منقضی شده است. لطفاً دوباره درخواست دهید.', 'rsd-finance-panel')
                );
            }
            return new WP_Error(
                'invalid_key',
                esc_html__('لینک بازنشانی معتبر نیست. لطفاً دوباره درخواست دهید.', 'rsd-finance-panel')
            );
        }

        if (!$user instanceof WP_User) {
            return new WP_Error(
                'invalid_user',
                esc_html__('کاربر مقصد معتبر نیست.', 'rsd-finance-panel')
            );
        }

        wp_set_password($new_pass, $user->ID);

        return true;
    }

    /**
     * Optional hook after a successful password reset.
     *
     * @param WP_User $user
     * @param string $new_pass
     * @return void
     */
    public function after_password_reset_cleanup($user, $new_pass)
    {
        if (!$user instanceof WP_User) {
            return;
        }
    }

    // ============================================================
    //  USER LOOKUP
    // ============================================================

    /**
     * Find a WP_User by "identifier" which may be an Iranian mobile (normalised),
     * an email address, or a legacy username. Search order:
     *   1. usermeta rsd_fp_phone
     *   2. user_email
     *   3. user_login (legacy accounts)
     *
     * @param string $id
     * @return WP_User|null
     */
    public function find_user_by_identifier($id): ?WP_User
    {
        $id_raw = trim((string)$id);
        if ($id_raw === '') {
            return null;
        }

        $normalised = FarazSMS::normalise_phone($id_raw);
        if (FarazSMS::is_valid_iran_mobile($normalised)) {
            $user = $this->find_user_by_phone_meta($normalised);
            if ($user instanceof WP_User) {
                return $user;
            }
        }

        if (is_email($id_raw)) {
            $user = get_user_by('email', $id_raw);
            if ($user instanceof WP_User) {
                return $user;
            }
        }

        $user = get_user_by('login', $id_raw);
        if ($user instanceof WP_User) {
            return $user;
        }

        return null;
    }

    /**
     * Look up a user by rsd_fp_phone meta.
     *
     * @param string $phone  Normalised 11-digit 09xxxxxxxxx
     * @return WP_User|null
     */
    private function find_user_by_phone_meta(string $phone): ?WP_User
    {
        global $wpdb;
        $sql = "SELECT u.ID FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID
                WHERE m.meta_key = 'rsd_fp_phone' AND m.meta_value = %s
                LIMIT 1";
        $uid = (int)$wpdb->get_var($wpdb->prepare($sql, $phone));
        if (!$uid) {
            return null;
        }
        $u = get_user_by('id', $uid);
        return $u instanceof WP_User ? $u : null;
    }

    // ============================================================
    //  BRUTE FORCE PROTECTION (transient-based)
    // ============================================================

    /**
     * Build the transient key used for brute-force tracking per-identifier.
     *
     * @param string $identifier
     * @return string
     */
    private function brute_transient_key(string $identifier): string
    {
        return 'rsd_fp_login_attempts_' . md5(strtolower(trim($identifier)));
    }

    /**
     * Whether the given identifier is currently blocked due to too many failed logins.
     *
     * @param string $identifier
     * @return bool
     */
    public function is_brute_blocked(string $identifier): bool
    {
        $key = $this->brute_transient_key($identifier);
        $count = (int) get_transient($key);
        return $count >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Increment the failed-attempt counter for the given identifier.
     *
     * @param string $identifier
     * @return void
     */
    public function record_failed_attempt(string $identifier): void
    {
        $key = $this->brute_transient_key($identifier);
        $current = (int) get_transient($key);
        $new = $current + 1;
        if ($current <= 0) {
            set_transient($key, $new, self::LOGIN_ATTEMPT_TTL);
        } else {
            set_transient($key, $new, self::LOGIN_ATTEMPT_TTL);
        }
    }

    /**
     * Clear failed attempts for an identifier (e.g. after successful login).
     *
     * @param string $identifier
     * @return void
     */
    public function clear_failed_attempts(string $identifier): void
    {
        delete_transient($this->brute_transient_key($identifier));
    }

    // ============================================================
    //  OTP
    // ============================================================

    /**
     * Transient key base for OTP storage.
     */
    private function otp_transient_base(string $phone): string
    {
        return 'rsd_fp_otp_' . md5(FarazSMS::normalise_phone($phone));
    }

    /**
     * Send a 6-digit OTP to the given phone number.
     * - Generates code
     * - Stores in transient (TTL, max-wrong counter)
     * - Dispatches via FarazSMS gateway (currently stub/mock when disabled or credentials missing)
     *
     * @param string $phone
     * @param string $context  'register' | 'login'
     * @return string|WP_Error  The OTP code on mock success (for development logs); WP_Error otherwise.
     */
    public function send_otp(string $phone, string $context = 'register')
    {
        $phone = FarazSMS::normalise_phone($phone);
        if (!FarazSMS::is_valid_iran_mobile($phone)) {
            return new WP_Error(
                'invalid_phone',
                esc_html__('شماره موبایل وارد شده معتبر نیست.', 'rsd-finance-panel')
            );
        }

        $code = (string) random_int(100000, 999999);

        $storage = [
            'code'       => $code,
            'wrong'      => 0,
            'created_at' => time(),
        ];
        set_transient($this->otp_transient_base($phone) . '_data', $storage, self::OTP_TTL);
        set_transient($this->otp_transient_base($phone), 1, self::OTP_TTL);

        $provider = (string) Plugin::get_setting('auth_sms_provider', 'faraz_sms');
        if ($provider === 'faraz_sms') {
            $gateway = new FarazSMS();
            $result = $gateway->send_otp($phone, $code, $context);

            if (!empty($result['mock']) || !empty($result['success'])) {
                return $code;
            }

            return new WP_Error(
                'sms_send_failed',
                $result['message'] ?? esc_html__('ارسال پیامک با خطا مواجه شد. لطفاً بعداً دوباره تلاش کنید.', 'rsd-finance-panel')
            );
        }

        return new WP_Error(
            'unknown_provider',
            esc_html__('سرویس دهنده پیامک پشتیبانی نمی‌شود.', 'rsd-finance-panel')
        );
    }

    /**
     * Verify an OTP code against the stored transient.
     *
     * @param string $phone
     * @param string $code
     * @return bool
     */
    public function verify_otp(string $phone, string $code): bool
    {
        $phone = FarazSMS::normalise_phone($phone);
        if (!FarazSMS::is_valid_iran_mobile($phone)) {
            return false;
        }

        $data = get_transient($this->otp_transient_base($phone) . '_data');
        if (!is_array($data) || !isset($data['code'], $data['wrong'])) {
            return false;
        }

        $code = trim((string)$code);
        if (!ctype_digit($code)) {
            return false;
        }

        if ((int)$data['wrong'] >= self::OTP_MAX_WRONG) {
            $this->clear_otp($phone);
            return false;
        }

        if (hash_equals((string)$data['code'], $code)) {
            return true;
        }

        $data['wrong'] = (int)$data['wrong'] + 1;
        set_transient($this->otp_transient_base($phone) . '_data', $data, self::OTP_TTL);

        return false;
    }

    /**
     * Clear OTP transient data for a phone (used after successful use).
     *
     * @param string $phone
     * @return void
     */
    public function clear_otp(string $phone): void
    {
        $phone = FarazSMS::normalise_phone($phone);
        delete_transient($this->otp_transient_base($phone));
        delete_transient($this->otp_transient_base($phone) . '_data');
    }

    // ============================================================
    //  HELPERS
    // ============================================================

    /**
     * Ensure there is exactly one empty row in company_info for a user.
     * Uses INSERT IGNORE on the unique user_id key so it's idempotent.
     *
     * @param int $uid
     * @return void
     */
    private function insert_empty_company_row(int $uid): void
    {
        global $wpdb;
        if ($uid <= 0) {
            return;
        }
        $table = Database::table('company_info');
        $exists = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
            $uid
        ));
        if ($exists <= 0) {
            $wpdb->insert(
                $table,
                [
                    'user_id'    => $uid,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ],
                ['%d', '%s', '%s']
            );
        }
    }

    /**
     * Build a unique user_login (WP username) based on normalised mobile.
     * If the derived slug is already taken (shouldn't happen because we check
     * phone first), append -2, -3, ... until it's free.
     *
     * @param string $phone  Normalised 09xxxxxxxxx
     * @param string $email  Optional email
     * @return string
     */
    private function generate_unique_user_login(string $phone, string $email): string
    {
        $base = 'client_' . substr($phone, -8);
        $base = sanitize_user($base, true);
        if ($base === '') {
            $base = 'client_' . time();
        }
        $candidate = $base;
        $counter = 2;
        while (username_exists($candidate)) {
            $candidate = $base . '_' . $counter;
            $counter++;
            if ($counter > 999) {
                $candidate = $base . '_' . wp_generate_password(6, false);
                break;
            }
        }
        return $candidate;
    }

    /**
     * Build a display_name string for new users when no name was provided.
     * Uses the last 4 digits of the phone or the email local part.
     *
     * @param string $phone
     * @param string $email
     * @return string
     */
    private function build_display_name(string $phone, string $email): string
    {
        if ($email !== '' && is_email($email)) {
            $parts = explode('@', $email, 2);
            $local = reset($parts);
            if ($local && mb_strlen($local) >= 2) {
                return sanitize_text_field($local);
            }
        }
        if (FarazSMS::is_valid_iran_mobile($phone)) {
            return sprintf(
                esc_html__('کاربر %s', 'rsd-finance-panel'),
                substr($phone, -4)
            );
        }
        return esc_html__('کاربر جدید', 'rsd-finance-panel');
    }

    // ============================================================
    //  REDIRECTS & ACCESS CONTROL (no wp-admin for clients)
    // ============================================================

    /**
     * Filter login redirect destination. Admin users stay on the default
     * (usually /wp-admin/). Client users (rsd_client) always land on the
     * finance dashboard so they never see the WordPress backend.
     *
     * Priority 999 so we override any theme/plugin redirections.
     *
     * @param string $redirect_to
     * @param string $requested_redirect_to
     * @param WP_User|\WP_Error|null $user
     * @return string
     */
    public function filter_login_redirect($redirect_to, $requested_redirect_to, $user = null)
    {
        if (!$user instanceof WP_User) {
            return $redirect_to;
        }

        if (Plugin::is_admin_user($user->ID)) {
            return $redirect_to;
        }

        if (Plugin::is_client_user($user->ID)) {
            $dash = Plugin::get_page_url('dashboard');
            if ($dash) {
                return $dash;
            }
        }

        return $redirect_to;
    }

    /**
     * Filter registration redirect destination. For self-service client
     * registration (even if another plugin falls back to core), bounce to
     * the finance dashboard instead of wp-login or wp-admin.
     *
     * @param string $redirect_to
     * @param int|WP_Error $user_id_or_error
     * @return string
     */
    public function filter_registration_redirect($redirect_to, $user_id_or_error)
    {
        if (is_wp_error($user_id_or_error)) {
            return $redirect_to;
        }

        $uid = (int)$user_id_or_error;
        if ($uid > 0 && Plugin::is_client_user($uid) && !Plugin::is_admin_user($uid)) {
            $dash = Plugin::get_page_url('dashboard');
            if ($dash) {
                return $dash;
            }
        }

        return $redirect_to;
    }

    /**
     * Hide the WP Admin Bar from the frontend for non-admin client users.
     * They should never be aware of wp-admin; every action goes through
     * the finance portal UI.
     *
     * @param bool $show_admin_bar
     * @return bool
     */
    public function filter_show_admin_bar_client($show_admin_bar)
    {
        if (!is_user_logged_in()) {
            return $show_admin_bar;
        }

        $uid = get_current_user_id();
        if ($uid && !Plugin::is_admin_user($uid) && Plugin::is_client_user($uid)) {
            return false;
        }

        return $show_admin_bar;
    }

    /**
     * Early frontend action to explicitly hide the admin bar for client
     * users. Complements show_admin_bar filter and catches any cases where
     * themes force the bar to render.
     *
     * @return void
     */
    public function action_hide_admin_bar_for_clients_frontend()
    {
        if (is_admin()) {
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }

        $uid = get_current_user_id();
        if ($uid && !Plugin::is_admin_user($uid) && Plugin::is_client_user($uid)) {
            show_admin_bar(false);
        }
    }

    /**
     * Block non-admin client users from entering the WordPress admin area.
     * If they manually type /wp-admin/ or another plugin sends them there,
     * immediately redirect them to the finance dashboard with a friendly
     * notice via transient.
     *
     * Priority 1 so it runs before the Screen class loads and before any
     * output headers are sent by the admin.
     *
     * @return void
     */
    public function action_admin_init_block_clients()
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        $uid = get_current_user_id();
        if (!$uid) {
            return;
        }

        if (Plugin::is_admin_user($uid)) {
            return;
        }

        if (Plugin::is_client_user($uid)) {
            $dash = Plugin::get_page_url('dashboard');
            if ($dash) {
                wp_redirect($dash);
                exit;
            }
        }
    }

    /**
     * Early template_redirect action: if a logged-in client lands on the auth
     * page, send them to the dashboard BEFORE any theme/template output is
     * generated (which would cause "headers already sent" and make wp_redirect
     * a no-op inside the shortcode).
     *
     * Priority 5 so it fires before the main query renders anything.
     *
     * @return void
     */
    public function action_template_redirect_auth_to_dashboard_if_logged_in_client()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $uid = get_current_user_id();
        if (!$uid) {
            return;
        }

        if (Plugin::is_admin_user($uid)) {
            return;
        }

        if (!Plugin::is_client_user($uid)) {
            return;
        }

        $is_auth_page = false;
        $page_ids = (array) get_option('rsd_fp_page_ids', []);
        if (!empty($page_ids['auth']) && is_page((int)$page_ids['auth'])) {
            $is_auth_page = true;
        } else {
            $pid = (int) get_queried_object_id();
            if ($pid > 0) {
                $post = get_post($pid);
                if (
                    $post && is_a($post, 'WP_Post')
                    && strpos($post->post_content, '[rsd_fp_auth]') !== false
                ) {
                    $is_auth_page = true;
                }
            }
        }

        if ($is_auth_page) {
            $dash = Plugin::get_page_url('dashboard');
            if ($dash) {
                wp_redirect($dash);
                exit;
            }
        }
    }
}
