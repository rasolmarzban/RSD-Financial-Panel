<?php
/**
 * Admin Template: Clients List
 *
 * @var array $clients
 */
defined('ABSPATH') || exit;
?>
<div class="rsd-admin-wrap">
    <div class="rsd-admin-header">
        <div>
            <h1>👥 مدیریت مشتریان</h1>
            <p>لیست کاربران دارای دسترسی به پرتال مالی</p>
        </div>
    </div>

    <div class="rsd-admin-card">
        <div class="rsd-admin-table-wrap">
            <table class="rsd-admin-table">
                <thead><tr>
                    <th>آواتار</th>
                    <th>نام مشتری</th>
                    <th>ایمیل</th>
                    <th>نام کاربری</th>
                    <th>نقش</th>
                    <th>تاریخ عضویت</th>
                    <th>عملیات</th>
                </tr></thead>
                <tbody>
                <?php if (!empty($clients)) : foreach ($clients as $u) : ?>
                    <tr>
                        <td><?php echo get_avatar($u->ID, 32); ?></td>
                        <td style="font-weight:600;color:#0f172a"><?php echo esc_html($u->display_name); ?></td>
                        <td><?php echo esc_html($u->user_email); ?></td>
                        <td style="font-family:monospace"><?php echo esc_html($u->user_login); ?></td>
                        <td><?php
                            $roles = (array) $u->roles;
                            foreach ($roles as $r) {
                                if ($r === 'rsd_client') {
                                    echo '<span class="rsd-admin-badge purple">مشتری</span>';
                                } elseif ($r === 'administrator') {
                                    echo '<span class="rsd-admin-badge blue">مدیر</span>';
                                } else {
                                    echo '<span class="rsd-admin-badge gray">' . esc_html($r) . '</span>';
                                }
                            }
                        ?></td>
                        <td style="color:#64748b;font-size:12px"><?php echo esc_html(date_i18n('j F Y', strtotime($u->user_registered))); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg(['view'=>'client','id'=>$u->ID])); ?>"
                               class="rsd-btn-admin small primary">جزئیات</a>
                            <a href="<?php echo esc_url(get_edit_user_link($u->ID)); ?>"
                               class="rsd-btn-admin small secondary">ویرایش</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="7"><div class="rsd-empty"><p>مشتری یافت نشد. کاربرانی با نقش «RSD Client» ایجاد کنید.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
