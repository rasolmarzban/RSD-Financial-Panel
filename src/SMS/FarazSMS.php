<?php
/**
 * Faraz SMS Gateway class
 *
 * Bridge between the plugin and a real FarazSMS HTTP API.
 * CURRENT PHASE (2026-09-14): All send methods return mock responses — no real
 * HTTP calls are issued. The OTP code is still produced so the rest of the
 * authentication flow can be tested. When Faraz credentials are populated in
 * plugin settings, replace the mock branches in `send_otp()` and
 * `send_template()` with the real wp_remote_post calls outlined below.
 *
 * @package RSD_Finance_Panel
 * @subpackage SMS
 */

namespace RSD\FinancePanel\SMS;

defined('ABSPATH') || exit;

use RSD\FinancePanel\Core\Plugin;

class FarazSMS
{
    /**
     * FarazSMS REST endpoint (placeholder).
     *
     * The exact endpoint/URL pattern varies across FarazSMS gateway versions
     * (classic REST, Payam-Resan v1/v2, Pattern-SMS, etc.). The body template
     * below prepares the most common fields so the migration is trivial.
     */
    const REST_ENDPOINT = 'https://api.payam-resan.com/APISend.aspx';

    /**
     * Send a numeric OTP code to the given phone number.
     *
     * @param string $phone  Iranian mobile number (normalised 09xxxxxxxxx).
     * @param string $code   The generated OTP code to deliver.
     * @param string $context 'register' | 'login' - optional label for future pattern SMS.
     * @return array{success:bool, mock:bool, code?:string, http_code?:int, error?:string}
     */
    public function send_otp($phone, $code, $context = 'register')
    {
        $phone    = self::normalise_phone($phone);
        $username = Plugin::get_setting('auth_faraz_username', '');
        $password = Plugin::get_setting('auth_faraz_password', '');
        $sender   = Plugin::get_setting('auth_faraz_sender',   '');
        $otp_on   = Plugin::get_setting('auth_otp_enabled', '') === '1';

        if (!$otp_on || $username === '' || $password === '' || $sender === '') {
            // Credentials not set or OTP globally disabled → fall back to mock.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:disable WordPress.PHP.DevelopmentFunctions -- debug only
                error_log(sprintf('[RSD FarazSMS MOCK] OTP context=%s phone=%s code=%s', $context, $phone, $code));
                // phpcs:enable
            }
            return [
                'success' => false,
                'mock'    => true,
                'code'    => (string) $code,
                'error'   => 'Credentials missing or OTP disabled; message NOT sent over the wire.',
            ];
        }

        /* ------------------------------------------------------------------
         * REAL FarazSMS call (stub — uncomment and tweak when going live).
         * ------------------------------------------------------------------
        $body = http_build_query([
            'Username'    => $username,
            'Password'    => $password,
            'From'        => $sender,
            'To'          => $phone,
            'Text'        => sprintf('Your verification code: %s RSD Finance Panel', $code),
            'IsFlash'     => 'false',
        ]);

        $response = wp_remote_post(self::REST_ENDPOINT, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            return [
                'success'   => false,
                'mock'      => false,
                'http_code' => 0,
                'error'     => $response->get_error_message(),
            ];
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body_str    = (string) wp_remote_retrieve_body($response);

        return [
            'success'   => $status_code >= 200 && $status_code < 300,
            'mock'      => false,
            'http_code' => $status_code,
            'error'     => $body_str,
        ];
         * ------------------------------------------------------------------ */

        // Fall-through mock for systems with credentials but no HTTP implemented yet.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[RSD FarazSMS MOCK-LIVE] OTP context=%s phone=%s code=%s', $context, $phone, $code));
        }
        return [
            'success' => false,
            'mock'    => true,
            'code'    => (string) $code,
        ];
    }

    /**
     * Send an arbitrary template-based SMS (for future password-reset, welcome SMS, etc.).
     *
     * @param string $phone
     * @param string $template_id
     * @param array  $variables  key-value pairs injected into template.
     * @return array{success:bool, mock:bool, ...}
     */
    public function send_template($phone, $template_id, array $variables = [])
    {
        $phone = self::normalise_phone($phone);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[RSD FarazSMS MOCK TEMPLATE] phone=%s tpl=%s vars=%s',
                $phone,
                $template_id,
                wp_json_encode($variables, JSON_UNESCAPED_UNICODE)
            ));
        }

        return [
            'success'     => false,
            'mock'        => true,
            'template_id' => (string) $template_id,
        ];
    }

    /**
     * Normalise an Iranian phone number to the 09XXXXXXXXX 11-digit form.
     * Accepts +98, 0098, 98 prefixes and Arabic/Persian digits. Returns '' on fail.
     *
     * @param string $phone
     * @return string
     */
    public static function normalise_phone($phone)
    {
        $phone = (string) $phone;
        // Persian / Arabic → ASCII digits
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $ascii   = range('0','9');
        $phone   = str_replace($persian, $ascii, $phone);
        $phone   = str_replace($arabic,  $ascii, $phone);
        $phone   = preg_replace('/\D+/', '', $phone);

        if (!$phone) {
            return '';
        }

        if (strpos($phone, '0098') === 0) {
            $phone = '0' . substr($phone, 4);
        } elseif (strpos($phone, '98') === 0 && strlen($phone) === 12) {
            $phone = '0' . substr($phone, 2);
        } elseif (strpos($phone, '9') === 0 && strlen($phone) === 10) {
            $phone = '0' . $phone;
        }

        if (strlen($phone) !== 11 || strpos($phone, '09') !== 0) {
            return '';
        }

        return $phone;
    }

    /**
     * Validate the phone number is a valid Iranian mobile number (11 digits, starts with 09).
     *
     * @param string $phone
     * @return bool
     */
    public static function is_valid_iran_mobile($phone)
    {
        return (bool) preg_match('/^09[0-9]{9}$/', (string) self::normalise_phone($phone));
    }
}
