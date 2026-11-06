/* global jQuery auth.js
 * RSD Finance Panel — custom authentication UI handlers
 * Login / Register / Lost password / Reset password / OTP
 * Deliberately written as IIFE jQuery delegated handlers to stay consistent
 * with the rest of the portal.
 */
(function ($) {
  "use strict";

  if (!window.RSD_FP_AUTH) {
    window.RSD_FP_AUTH = {};
  }

  var AJAX_URL = window.RSD_FP_AUTH.ajax_url || (window.RSD_FP ? window.RSD_FP.ajax_url : "");
  var NONCE = window.RSD_FP_AUTH.nonce || (window.RSD_FP ? window.RSD_FP.nonce : "");
  var I18N = window.RSD_FP_AUTH.i18n || {};
  var DASH_URL = window.RSD_FP_AUTH.dashboard_url || (window.RSD_FP ? window.RSD_FP.dashboard_url : "/");
  var OTP_ENABLED = !!window.RSD_FP_AUTH.otp_enabled || false;
  var PUB_REG = typeof window.RSD_FP_AUTH.public_registration !== "undefined" ? !!window.RSD_FP_AUTH.public_registration : true;
  var PW_MIN = parseInt(window.RSD_FP_AUTH.password_min_length || 8, 10);

  var $ROOT;

  function escHTML(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  function qs(key) {
    var p = new RegExp("[?&]" + key + "=([^&#]*)");
    var r = window.location.search.match(p);
    return r ? decodeURIComponent(r[1]) : "";
  }

  function showMsg(text, type) {
    var $z = $ROOT.find(".rsd-auth-alert-zone").first();
    type = type || "info";
    text = text || "";
    if (!text) {
      $z.empty().hide();
      return;
    }
    var cls = "rsd-auth-alert rsd-auth-alert-" + type;
    var icon = type === "success" ? "✅" : type === "error" ? "⚠️" : "ℹ️";
    $z.removeClass()
      .addClass("rsd-auth-alert-zone " + cls)
      .html('<span class="rsd-auth-alert-icon">' + icon + '</span><span class="rsd-auth-alert-text">' + escHTML(text) + "</span>")
      .show();
    try {
      $z[0].scrollIntoView({ behavior: "smooth", block: "center" });
    } catch (e) {}
  }

  function clearMsg() {
    showMsg("", "info");
  }

  function switchTab(name) {
    $ROOT.find(".rsd-auth-tab").removeClass("active");
    $ROOT.find('[data-auth-tab="' + name + '"]').addClass("active");
    $ROOT.find(".rsd-auth-form").hide();
    var $target = $ROOT.find('[data-auth-form="' + name + '"]');
    $target.show();
    if (name === "resetpass") {
      var $rp = $ROOT.find("#rsd-auth-resetpass-key");
      var $rl = $ROOT.find("#rsd-auth-resetpass-login");
      if ($rp.length && qs("key")) {
        $rp.val(qs("key"));
      }
      if ($rl.length && qs("login")) {
        $rl.val(qs("login"));
      }
    }
    clearMsg();
  }

  function setLoading($btn, loading) {
    if (!$btn.length) return;
    if (loading) {
      $btn.data("orig-text", $btn.text());
      $btn.prop("disabled", true).addClass("rsd-btn-loading");
      $btn.text(I18N.processing || "Processing ...");
    } else {
      $btn.prop("disabled", false).removeClass("rsd-btn-loading");
      var t = $btn.data("orig-text");
      if (t) $btn.text(t);
    }
  }

  function ajax(action, data, cbOk, cbErr) {
    data = $.extend({ action: "rsd_fp_" + action, nonce: NONCE }, data || {});
    $.ajax({
      url: AJAX_URL,
      type: "POST",
      dataType: "json",
      data: data,
    })
      .done(function (resp) {
        if (resp && resp.success) {
          if (typeof cbOk === "function") cbOk(resp.data || {});
        } else {
          var msg = resp && resp.data && resp.data.message ? resp.data.message : I18N.error || "An error occurred.";
          if (typeof cbErr === "function") cbErr(msg);
          else showMsg(msg, "error");
        }
      })
      .fail(function () {
        var msg = I18N.network_error || "Error connecting to the server. Please try again.";
        if (typeof cbErr === "function") cbErr(msg);
        else showMsg(msg, "error");
      });
  }

  // ============================================================
  //  OTP 2-step flow (register / login)
  // ============================================================
  function otpStartFlow(formName) {
    var $form = $ROOT.find('[data-auth-form="' + formName + '"]');
    var phoneInput = formName === "register" ? "#rsd-auth-register-phone" : "#rsd-auth-login-identifier";
    var $phone = $form.find(phoneInput);
    var phone = $.trim($phone.val());
    if (!phone) {
      showMsg(I18N.phone_required || "Please enter your mobile number.", "error");
      $phone.focus();
      return;
    }
    var $step1 = $form.find(".rsd-auth-step1");
    var $step2 = $form.find(".rsd-auth-step2");
    var $sendBtn = $form.find(".rsd-auth-send-otp");
    setLoading($sendBtn, true);
    ajax(
      "auth_send_otp",
      {
        phone: phone,
        context: formName === "register" ? "register" : "login",
      },
      function (data) {
        setLoading($sendBtn, false);
        $step1.hide();
        $step2.show();
        $form.find(".rsd-auth-otp-phone").text(phone);
        $form.find(".rsd-auth-otp-input").val("").focus();
        showMsg(data.message || "", "success");
        startOtpCountdown($form, data.otp_ttl_seconds || 300);
      },
      function (err) {
        setLoading($sendBtn, false);
        showMsg(err, "error");
      },
    );
  }

  function startOtpCountdown($form, ttl) {
    var $resend = $form.find(".rsd-auth-otp-resend");
    if (!$resend.length) return;
    $resend.prop("disabled", true).addClass("rsd-btn-disabled");
    var seconds = Math.max(1, parseInt(ttl, 10));
    var t;
    function tick() {
      if (seconds <= 0) {
        clearInterval(t);
        $resend
          .prop("disabled", false)
          .removeClass("rsd-btn-disabled")
          .text(I18N.resend_code || "Resend code");
        return;
      }
      var m = Math.floor(seconds / 60);
      var s = seconds % 60;
      $resend.text((I18N.resend_in || "Resend in") + " " + (m > 0 ? m + ":" : "") + (s < 10 ? "0" + s : s));
      seconds--;
    }
    tick();
    t = setInterval(tick, 1000);
  }

  // ============================================================
  //  FORMS SUBMIT
  // ============================================================
  function submitLogin(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('[type="submit"]');
    clearMsg();

    var identifier = $.trim($form.find("#rsd-auth-login-identifier").val());
    var password = $form.find("#rsd-auth-login-password").val();
    var remember = $form.find("#rsd-auth-login-remember").is(":checked") ? 1 : 0;
    var otp = $.trim($form.find("#rsd-auth-login-otp").val());
    var redirectTo = $.trim($form.find("#rsd-auth-login-redirect").val()) || "";

    if (!identifier || !password) {
      showMsg(I18N.fill_required || "Please fill in the required fields.", "error");
      return;
    }
    if (OTP_ENABLED && !$form.find(".rsd-auth-step2").is(":visible") && !otp) {
      showMsg(I18N.enter_otp || "Please enter the verification code.", "error");
      return;
    }

    setLoading($btn, true);
    ajax(
      "auth_login",
      {
        identifier: identifier,
        password: password,
        remember: remember,
        otp: otp,
        redirect_to: redirectTo,
      },
      function (data) {
        setLoading($btn, false);
        showMsg(data.message || "", "success");
        var url = data.redirect_to || DASH_URL || "/";
        setTimeout(function () {
          window.location.href = url;
        }, 700);
      },
      function (err) {
        setLoading($btn, false);
        showMsg(err, "error");
      },
    );
  }

  function submitRegister(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('[type="submit"]');
    clearMsg();

    var phone = $.trim($form.find("#rsd-auth-register-phone").val());
    var email = $.trim($form.find("#rsd-auth-register-email").val());
    var pw = $form.find("#rsd-auth-register-password").val();
    var pwc = $form.find("#rsd-auth-register-password-confirm").val();
    var otp = $.trim($form.find("#rsd-auth-register-otp").val());
    var redirectTo = $.trim($form.find("#rsd-auth-register-redirect").val()) || "";

    if (!phone) {
      showMsg(I18N.phone_required || "Mobile number is required.", "error");
      return;
    }
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showMsg(I18N.invalid_email || "The email entered is not valid.", "error");
      return;
    }
    if (!pw || pw.length < PW_MIN) {
      showMsg((I18N.password_short || "Password must be at least %d characters.").replace("%d", PW_MIN), "error");
      return;
    }
    if (pw !== pwc) {
      showMsg(I18N.password_mismatch || "Password and its confirmation do not match.", "error");
      return;
    }
    if (OTP_ENABLED && !$form.find(".rsd-auth-step2").is(":visible") && !otp) {
      showMsg(I18N.enter_otp || "Please enter the verification code.", "error");
      return;
    }

    setLoading($btn, true);
    ajax(
      "auth_register",
      {
        phone: phone,
        email: email,
        password: pw,
        password_confirm: pwc,
        otp: otp,
        redirect_to: redirectTo,
      },
      function (data) {
        setLoading($btn, false);
        showMsg(data.message || "", "success");
        var url = data.redirect_to || DASH_URL || "/";
        setTimeout(function () {
          window.location.href = url;
        }, 900);
      },
      function (err) {
        setLoading($btn, false);
        showMsg(err, "error");
      },
    );
  }

  function submitLost(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('[type="submit"]');
    clearMsg();
    var id = $.trim($form.find("#rsd-auth-lost-identifier").val());
    if (!id) {
      showMsg(I18N.enter_id || "Please enter your mobile number or email.", "error");
      return;
    }
    setLoading($btn, true);
    ajax(
      "auth_lostpassword",
      { identifier: id },
      function (data) {
        setLoading($btn, false);
        showMsg(data.message || "", "success");
      },
      function (err) {
        setLoading($btn, false);
        showMsg(err, "error");
      },
    );
  }

  function submitReset(e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('[type="submit"]');
    clearMsg();

    var key = $.trim($form.find("#rsd-auth-resetpass-key").val());
    var login = $.trim($form.find("#rsd-auth-resetpass-login").val());
    var npw = $form.find("#rsd-auth-resetpass-new").val();
    var npwc = $form.find("#rsd-auth-resetpass-new-confirm").val();

    if (!key || !login) {
      showMsg(I18N.invalid_reset_request || "The reset request is not valid.", "error");
      return;
    }
    if (!npw || npw.length < PW_MIN) {
      showMsg((I18N.password_short || "Password must be at least %d characters.").replace("%d", PW_MIN), "error");
      return;
    }
    if (npw !== npwc) {
      showMsg(I18N.password_mismatch || "Password and its confirmation do not match.", "error");
      return;
    }
    setLoading($btn, true);
    ajax(
      "auth_reset_password",
      {
        key: key,
        login: login,
        new_password: npw,
        new_password_confirm: npwc,
      },
      function (data) {
        setLoading($btn, false);
        showMsg(data.message || "", "success");
        var url = data.redirect_to || window.location.pathname + "?passwordreset=true";
        setTimeout(function () {
          window.location.href = url;
        }, 1100);
      },
      function (err) {
        setLoading($btn, false);
        showMsg(err, "error");
      },
    );
  }

  // ============================================================
  //  BOOT
  // ============================================================
  function boot() {
    $ROOT = $(".rsd-fp-auth-portal");
    if (!$ROOT.length) return;

    var initialAction = qs("action") || "login";
    var allowed = ["login", "register", "lostpassword", "lostpassword", "resetpass"];
    if ($.inArray(initialAction, allowed) < 0) initialAction = "login";
    if (initialAction === "retrievepassword") initialAction = "lostpassword";
    if (initialAction === "rp") initialAction = "resetpass";

    if (!PUB_REG) {
      $ROOT.find('[data-auth-tab="register"]').hide();
      if (initialAction === "register") initialAction = "login";
    }
    if (qs("loggedout") === "true") {
      showMsg(I18N.logged_out || "You have been logged out successfully.", "success");
    }
    if (qs("passwordreset") === "true") {
      showMsg(I18N.pw_reset_success || "Your password has been changed successfully. You can now log in.", "success");
      if (initialAction !== "resetpass") initialAction = "login";
    }
    if (qs("error") === "expiredkey") {
      showMsg(I18N.key_expired || "The reset link has expired. Please request a new one.", "error");
      initialAction = "lostpassword";
    }
    if (qs("error") === "invalidkey") {
      showMsg(I18N.key_invalid || "The reset link is not valid.", "error");
      initialAction = "lostpassword";
    }

    switchTab(initialAction);

    $ROOT.on("click", ".rsd-auth-tab", function () {
      var tab = $(this).data("auth-tab");
      switchTab(tab);
    });

    $ROOT.on("submit", '[data-auth-form="login"]', submitLogin);
    $ROOT.on("submit", '[data-auth-form="register"]', submitRegister);
    $ROOT.on("submit", '[data-auth-form="lostpassword"]', submitLost);
    $ROOT.on("submit", '[data-auth-form="resetpass"]', submitReset);

    $ROOT.on("click", ".rsd-auth-send-otp", function (e) {
      e.preventDefault();
      var $btn = $(this);
      var ctx = $btn.data("context") || "register";
      otpStartFlow(ctx);
    });

    $ROOT.on("click", ".rsd-auth-otp-resend", function (e) {
      e.preventDefault();
      var ctx = $(this).data("context") || "register";
      otpStartFlow(ctx);
    });
  }

  $(document).ready(boot);
})(jQuery);
