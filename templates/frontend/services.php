<?php

/**
 * Template: Frontend - New Services Page
 *
 * @var int $client_id
 * @var array $my_requests
 * @var string $active_service
 * @var array $service_types
 */
defined('ABSPATH') || exit;

$active = 'services';
$services = \RSD\FinancePanel\Modules\Services::get_service_types();
$statuses = \RSD\FinancePanel\Modules\Services::get_statuses();
?>
<div class="rsd-fp-wrapper">
    <?php include __DIR__ . '/parts/sidebar.php'; ?>
    <main class="rsd-fp-main">
        <?php include __DIR__ . '/parts/header.php'; ?>

        <div class="rsd-toolbar">
            <h2 style="font-size:18px;font-weight:700;margin:0;color:#0f172a">⚙️ New Service Request</h2>
            <div class="spacer"></div>
        </div>

        <div class="rsd-tabs">
            <div class="rsd-tab active" onclick="rsdTab(this,'available')">Available Services</div>
            <div class="rsd-tab" onclick="rsdTab(this,'my-requests')">My Requests (<?php echo esc_html(count($my_requests)); ?>)</div>
        </div>

        <div id="tab-available">
            <div class="rsd-services-grid">
                <?php foreach ($services as $key => $svc) : ?>
                    <div class="rsd-service-card" data-service-select="<?php echo esc_attr($key); ?>">
                        <div class="rsd-service-icon">
                            <?php
                            $icons = [
                                'company_registration'   => '🏛️',
                                'brand_registration'     => '🏷️',
                                'tax_id_application'     => '🧾',
                                'new_personnel_payroll'  => '👤',
                                'employee_termination'   => '👋',
                            ];
                            echo $icons[$key] ?? '⚙️';
                            ?>
                        </div>
                        <h3><?php echo esc_html($svc['label']); ?></h3>
                        <p><?php echo esc_html($svc['description']); ?></p>
                        <div class="svc-go">Select and Start Request →</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:30px">
                <?php foreach ($services as $key => $svc) : ?>
                    <div data-service-wrapper="<?php echo esc_attr($key); ?>"
                        style="display:none"
                        class="rsd-card">
                        <div class="rsd-card-header">
                            <div class="rsd-card-title">
                                <span class="rsd-card-icon">📝</span>
                                Request Form: <?php echo esc_html($svc['label']); ?>
                            </div>
                            <button type="button" class="rsd-btn rsd-btn-sm rsd-btn-light"
                                onclick="jQuery('[data-service-wrapper]').hide();jQuery('.rsd-service-card').removeClass('active');jQuery('html,body').animate({scrollTop:jQuery('.rsd-services-grid').offset().top-60},200);">
                                ← Change Service
                            </button>
                        </div>
                        <form data-service-form="<?php echo esc_attr($key); ?>" data-rsd-svc-form>
                            <input type="hidden" name="service_type" value="<?php echo esc_attr($key); ?>">
                            <input type="hidden" name="title" value="<?php echo esc_attr($svc['label']); ?>">

                            <div class="rsd-grid rsd-grid-2">
                                <?php foreach ($svc['fields'] as $fkey => $field) :
                                    $fname = 'svc_' . $fkey;
                                ?>
                                    <div class="rsd-form-row">
                                        <label>
                                            <?php echo esc_html($field['label']); ?>
                                            <?php if (!empty($field['required'])) echo '<span class="required">*</span>'; ?>
                                        </label>
                                        <?php if ($field['type'] === 'select') : ?>
                                            <select name="<?php echo esc_attr($fname); ?>" <?php if (!empty($field['required'])) echo 'required'; ?>>
                                                <option value="">- Select -</option>
                                                <?php foreach ($field['options'] as $k => $v) : ?>
                                                    <option value="<?php echo esc_attr($k); ?>"><?php echo esc_html($v); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif ($field['type'] === 'textarea') : ?>
                                            <textarea name="<?php echo esc_attr($fname); ?>" rows="3"
                                                <?php if (!empty($field['required'])) echo 'required'; ?>></textarea>
                                        <?php else : ?>
                                            <input type="<?php echo esc_attr($field['type']); ?>"
                                                name="<?php echo esc_attr($fname); ?>"
                                                <?php if (!empty($field['required'])) echo 'required'; ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="rsd-form-row">
                                <label>Additional Description</label>
                                <textarea name="description" rows="3"></textarea>
                            </div>
                            <div style="text-align:left;margin-top:10px">
                                <button type="submit" class="rsd-btn rsd-btn-primary rsd-btn-lg">
                                    Submit Request ✅
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="tab-my-requests" style="display:none">
            <div class="rsd-card">
                <div class="rsd-table-wrap">
                    <table class="rsd-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Service Type</th>
                                <th>Title</th>
                                <th>Submission Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($my_requests)) : foreach ($my_requests as $req) : ?>
                                    <tr>
                                        <td style="font-family:monospace;font-weight:700;color:#0369a1">
                                            #SRV-<?php echo esc_html($req->id); ?>
                                        </td>
                                        <td><?php echo isset($service_types[$req->service_type]) ? esc_html($service_types[$req->service_type]) : esc_html($req->service_type); ?></td>
                                        <td style="font-weight:600;color:#0f172a"><?php echo esc_html($req->title); ?></td>
                                        <td><?php echo esc_html(date_i18n('j F Y', strtotime($req->created_at))); ?></td>
                                        <td><?php echo rsd_fp_status_badge($req->status); ?></td>
                                    </tr>
                                <?php endforeach;
                            else : ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="rsd-empty">
                                            <div class="rsd-empty-icon">📝</div>
                                            <h3>You have not submitted any requests yet</h3>
                                            <p>Submit your request from the "Available Services" section.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<script type="text/javascript">
    function rsdTab(btn, tab) {
        jQuery('.rsd-tabs .rsd-tab').removeClass('active');
        jQuery(btn).addClass('active');
        jQuery('#tab-available, #tab-my-requests').hide();
        jQuery('#tab-' + tab).fadeIn(150);
    }
</script>
