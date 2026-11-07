<?php

/**
 * Template: Frontend - Edit Profile Page
 *
 * @var WP_User $current_user
 * @var int $client_id
 */
defined('ABSPATH') || exit;

$active = 'profile';
$current_user = $current_user ?? wp_get_current_user();
$display_name = $current_user->display_name;
$username     = $current_user->user_login;
$avatar_url   = function_exists('rsd_fp_user_avatar_url') ? rsd_fp_user_avatar_url($current_user->ID) : '';
$initial      = mb_substr($display_name ?: $username, 0, 1);
$min_password = (int) rsd_fp_setting('auth_password_min_length', 8);
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">👤 Edit Profile</h2>
        </div>

        <!-- Profile photo + name -->
        <form id="rsd-profile-form" enctype="multipart/form-data">
            <div class="rsd-card" style="margin-bottom:20px">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">🖼️</span>
                        Profile Photo &amp; Name
                    </div>
                </div>

                <div class="rsd-grid rsd-grid-2">
                    <div class="rsd-form-row">
                        <label>Profile Photo</label>
                        <div style="display:flex;align-items:center;gap:16px">
                            <div id="rsd-profile-avatar-preview"
                                style="width:88px;height:88px;border-radius:50%;overflow:hidden;flex-shrink:0;
                                       background:linear-gradient(135deg,#60a5fa,#a78bfa);display:flex;
                                       align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:30px">
                                <?php if ($avatar_url) : ?>
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block">
                                <?php else : ?>
                                    <?php echo esc_html($initial); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="rsd-avatar-input" class="rsd-btn rsd-btn-sm rsd-btn-primary" style="cursor:pointer">Choose Image</label>
                                <input type="file" id="rsd-avatar-input" name="avatar" accept="image/*" style="display:none">
                                <?php if ($avatar_url) : ?>
                                    <label style="display:block;margin-top:8px;font-size:13px;color:#64748b">
                                        <input type="checkbox" name="remove_avatar" value="1"> Remove current image
                                    </label>
                                <?php endif; ?>
                                <div class="rsd-form-hint">JPG or PNG. Square images look best.</div>
                            </div>
                        </div>
                    </div>

                    <div class="rsd-form-row">
                        <label>Display Name</label>
                        <input type="text" name="display_name" value="<?php echo esc_attr($display_name); ?>" required>
                        <div class="rsd-form-hint">This is the name shown across the portal.</div>
                    </div>

                    <div class="rsd-form-row">
                        <label>Username</label>
                        <input type="text" value="<?php echo esc_attr($username); ?>" disabled>
                        <div class="rsd-form-hint">Usernames cannot be changed.</div>
                    </div>
                </div>
            </div>

            <div style="text-align:left;margin-bottom:26px">
                <button type="submit" class="rsd-btn rsd-btn-lg rsd-btn-primary">Save Changes 💾</button>
            </div>
        </form>

        <!-- Password -->
        <form id="rsd-password-form">
            <div class="rsd-card">
                <div class="rsd-card-header">
                    <div class="rsd-card-title">
                        <span class="rsd-card-icon">🔒</span>
                        Change Password
                    </div>
                </div>

                <div class="rsd-grid rsd-grid-2">
                    <div class="rsd-form-row">
                        <label>Current Password</label>
                        <input type="password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="rsd-form-row">
                        <label>New Password</label>
                        <input type="password" name="new_password" autocomplete="new-password" required>
                    </div>
                    <div class="rsd-form-row">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" autocomplete="new-password" required>
                        <div class="rsd-form-hint">Password must be at least <?php echo esc_html($min_password); ?> characters long.</div>
                    </div>
                </div>
            </div>

            <div style="text-align:left;margin-top:16px">
                <button type="submit" class="rsd-btn rsd-btn-lg rsd-btn-primary">Update Password 🔒</button>
            </div>
        </form>

    </main>
</div>
