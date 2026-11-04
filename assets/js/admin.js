/*
 * RSD Finance Panel - Admin JavaScript
 * Version: 1.0.0
 */
(function ($) {
  "use strict";

  function adminAjax(action, data, cb) {
    if (typeof data === "string") {
      // Pre-serialized form string — append action+nonce as query params
      var params = data.length ? data.split("&") : [];
      params.push("action=" + encodeURIComponent("rsd_fp_" + action));
      params.push("nonce=" + encodeURIComponent(RSD_FP_ADMIN.nonce));
      data = params.join("&");
    } else {
      data = $.extend({ action: "rsd_fp_" + action, nonce: RSD_FP_ADMIN.nonce }, data || {});
    }
    return $.ajax({
      url: RSD_FP_ADMIN.ajax_url,
      type: "POST",
      data: data,
      dataType: "json",
    })
      .done(function (res) {
        if (typeof cb === "function") cb(res);
      })
      .fail(function () {
        if (typeof cb === "function") cb({ success: false });
      });
  }

  // --- Jalali calendar helpers (mirror dashboard.js + server PHP) ---
  function gregorianPartsToJalali(gy, gm, gd) {
    var g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    gy = gy - 1600;
    gm = gm - 1;
    gd = gd - 1;
    var gy2 = gm > 1 ? gy + 1 : gy;
    var days = 365 * gy + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm];
    var jy = -1595 + 33 * Math.floor(days / 12053);
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      jy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    var jm, jd;
    if (days < 186) {
      jm = 1 + Math.floor(days / 31);
      jd = 1 + (days % 31);
    } else {
      jm = 7 + Math.floor((days - 186) / 30);
      jd = 1 + ((days - 186) % 30);
    }
    return [jy, jm, jd];
  }
  function jalaliPartsToGregorian(jy, jm, jd) {
    jy = jy + 1595;
    var days = -355668 + 365 * jy + Math.floor((jy - 1) / 33) * 8 + Math.floor((((jy - 1) % 33) + 3) / 4) + ((jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186) + jd);
    var gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * Math.floor(--days / 36524);
      days %= 36524;
      if (days >= 365) days++;
    }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      gy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    var gd = days + 1;
    var sal_a = [0, 31, (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0 ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gm = 0;
    for (gm = 0; gm < 13 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
    return [gy, gm, gd];
  }
  function parseJalaliToGregorian(input) {
    input = String(input || "").trim();
    if (!input) return "";
    var fa = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"],
      ar = ["٠", "١", "٢", "٣", "٤", "٥", "٦", "٧", "٨", "٩"];
    for (var i = 0; i < 10; i++) {
      var re = new RegExp("[" + fa[i] + ar[i] + "]", "g");
      input = input.replace(re, String(i));
    }
    var m = /^(\d{4})[\-\/\.](\d{1,2})[\-\/\.](\d{1,2})$/.exec(input);
    if (!m) return "";
    var jy = +m[1],
      jm = +m[2],
      jd = +m[3];
    if (jy < 1200 || jy > 1600 || jm < 1 || jm > 12 || jd < 1 || jd > 31) return "";
    if (jm >= 7 && jd > 30) return "";
    if (jm === 12) {
      var tmp = jalaliPartsToGregorian(jy, 12, 30);
      var back = gregorianPartsToJalali(tmp[0], tmp[1], tmp[2]);
      if (!(back[0] === jy && back[1] === 12 && back[2] === 30) && jd > 29) return "";
    }
    var g = jalaliPartsToGregorian(jy, jm, jd);
    var pn = function (n) {
      return n < 10 ? "0" + n : "" + n;
    };
    return g[0] + "-" + pn(g[1]) + "-" + pn(g[2]);
  }

  $(document).ready(function () {
    // Color picker for settings if any
    if ($.fn.wpColorPicker) {
      $(".rsd-color").wpColorPicker();
    }

    // Simple confirm for delete links
    $(document).on("click", ".rsd-confirm", function (e) {
      if (!confirm($(this).data("confirm") || RSD_FP_ADMIN.i18n.confirm)) {
        e.preventDefault();
      }
    });

    // AJAX form submit helper
    $(document).on("submit", "[data-rsd-ajax-form]", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('[type="submit"]');
      var orig = $btn.text();

      // --- Jalali expiry_date client-side validation (for any admin AJAX form that carries the field)
      var $exp = $form.find('input[name="expiry_date"]');
      if ($exp.length) {
        var raw = $.trim($exp.val());
        if (raw) {
          // Allow legacy Gregorian YYYY-MM-DD passthrough (for backwards compat / tests)
          var isGreg = /^\d{4}-\d{2}-\d{2}$/.test(raw);
          if (!isGreg && !parseJalaliToGregorian(raw)) {
            $form.find(".rsd-form-msg").remove();
            $form.append(
              '<div class="rsd-form-msg notice notice-error inline" style="padding:8px 12px;margin-top:12px">' + "فرمت تاریخ انقضا نامعتبر است. لطفاً با قالب ۱۴۰۴/۰۶/۱۵ (شمسی) وارد کنید." + "</div>",
            );
            $exp.focus().select();
            return;
          }
        }
      }

      $btn.prop("disabled", true).text(RSD_FP_ADMIN.i18n.saving);

      adminAjax($form.data("rsd-ajax-form"), $form.serialize(), function (res) {
        $btn.prop("disabled", false).text(orig);
        if (res && res.success) {
          $form.find(".rsd-form-msg").remove();
          $form.append(
            '<div class="rsd-form-msg notice notice-success inline" style="padding:8px 12px;margin-top:12px">' + (res.data && res.data.message ? res.data.message : RSD_FP_ADMIN.i18n.saved) + "</div>",
          );
          setTimeout(function () {
            location.reload();
          }, 800);
        } else {
          alert((res.data && res.data.message) || RSD_FP_ADMIN.i18n.error);
        }
      });
    });

    // Tabs in admin
    $(document).on("click", ".rsd-admin-tabs [data-tab]", function (e) {
      e.preventDefault();
      var tab = $(this).data("tab");
      $(".rsd-admin-tabs [data-tab]").removeClass("nav-tab-active");
      $(this).addClass("nav-tab-active");
      $(".rsd-admin-tab-pane").hide();
      $('.rsd-admin-tab-pane[data-pane="' + tab + '"]').fadeIn(150);
    });

    // Documents status dropdown -> AJAX save per-row.
    function applyDocSelectColor($sel) {
      if (!$sel || !$sel.length) return;
      var v = String($sel.val() || "uploaded");
      $sel.removeClass("is-uploaded is-incomplete is-approved").addClass("is-" + v);
    }
    $(".rsd-doc-status-select").each(function () {
      applyDocSelectColor($(this));
    });

    $(document).on("change", "[data-doc-status]", function (e) {
      e.preventDefault();
      var $sel = $(this);
      var docId = parseInt($sel.data("docId") || $sel.data("doc-id") || "0", 10);
      var status = String($sel.val() || "");
      if (!docId || !status) return;

      var $badge = $('[data-doc-badge="' + docId + '"]');
      var $row = $sel.closest("[data-doc-row]");
      var $titleCell = $row.find("td:eq(2)");
      var statusMeta = {
        uploaded: { label: "ارسال‌شده", color: "green" },
        incomplete: { label: "ناقص", color: "red" },
        approved: { label: "تأییدشده", color: "purple" },
      };
      var meta = statusMeta[status] || { label: status, color: "gray" };

      $sel.prop("disabled", true);
      applyDocSelectColor($sel);

      adminAjax("admin_update_document_status", { doc_id: docId, status: status }, function (res) {
        $sel.prop("disabled", false);
        if (res && res.success) {
          // Update the colored status badge above the select
          if ($badge.length) {
            $badge.removeClass("gray green red purple").addClass(meta.color).text(meta.label);
          }
          // Sync the extra "ناقص" chip in the title column with is_incomplete
          var isIncomplete = !!(res && res.data && res.data.is_incomplete);
          var existingBadge = $titleCell.find(".rsd-admin-badge.red");
          if (isIncomplete) {
            if (!existingBadge.length) {
              $titleCell.prepend('<span class="rsd-admin-badge red" style="margin-left:6px">ناقص</span> ');
            }
          } else {
            existingBadge.remove();
          }
          if (res.data && res.data.message && window.rsdToast) {
            window.rsdToast("success", res.data.message);
          } else if (res.data && res.data.message) {
            alert(res.data.message);
          }
        } else {
          var failMsg = (res && res.data && res.data.message) || "خطا در به‌روزرسانی وضعیت.";
          if (window.rsdToast) {
            window.rsdToast("error", failMsg);
          } else {
            alert(failMsg);
          }
        }
      });
    });

    // Documents per-row DELETE button (previously only rendered confirm dialog but never sent AJAX).
    $(document).on("click", "[data-delete-document]", function (e) {
      var docId = parseInt($(this).data("deleteDocument") || $(this).data("delete-document") || "0", 10);
      if (!docId) return;
      // The generic .rsd-confirm class already confirmed; but also allow standalone click.
      if ($(this).is(".rsd-confirm")) {
        if (!confirm($(this).data("confirm") || "آیا برای حذف این مدرک اطمینان دارید؟")) {
          e.preventDefault();
          return;
        }
      }
      e.preventDefault();
      var $btn = $(this);
      $btn.prop("disabled", true);
      adminAjax("delete_document", { doc_id: docId }, function (res) {
        $btn.prop("disabled", false);
        if (res && res.success) {
          var msg = (res.data && res.data.message) || "مدرک حذف شد.";
          if (window.rsdToast) {
            window.rsdToast("success", msg);
          } else {
            alert(msg);
          }
          setTimeout(function () {
            location.reload();
          }, 600);
        } else {
          var err = (res && res.data && res.data.message) || "خطا در حذف مدرک.";
          if (window.rsdToast) {
            window.rsdToast("error", err);
          } else {
            alert(err);
          }
        }
      });
    });

    // --- Generic modal open/close via data-rsd-modal attribute ---
    function gregorianToJalali(gDateStr) {
      gDateStr = String(gDateStr || "").trim();
      if (!gDateStr) return "";
      var m = /^(\d{4})[\-\/\.](\d{1,2})[\-\/\.](\d{1,2})$/.exec(gDateStr);
      if (!m) return gDateStr;
      var parts = gregorianPartsToJalali(+m[1], +m[2], +m[3]);
      var pn = function (n) {
        return n < 10 ? "0" + n : "" + n;
      };
      return parts[0] + "/" + pn(parts[1]) + "/" + pn(parts[2]);
    }

    function rsdPrefillInvoiceForm($form, data) {
      if (!data || typeof data !== "object") return;
      var map = {
        id: "invoice_id",
        user_id: "user_id",
        invoice_number: "invoice_number",
        year: "year",
        month: "month",
        description: "description",
        subtotal: "subtotal",
        tax: "tax",
        discount: "discount",
        status: "status",
        payment_method: "payment_method",
      };
      for (var key in map) {
        if (Object.prototype.hasOwnProperty.call(map, key) && typeof data[key] !== "undefined" && data[key] !== null) {
          var $f = $form.find('[name="' + map[key] + '"]');
          if ($f.length) $f.val(data[key]);
        }
      }
      if (typeof data.client_id !== "undefined" && data.client_id !== null) {
        var $cf = $form.find('select[name="user_id"], input[name="user_id"]');
        if ($cf.length) $cf.val(data.client_id);
      }
      // Dates: convert Gregorian stored value -> Jalali for .rsd-jalali-date inputs
      var dateFields = { invoice_date: "invoice_date", due_date: "due_date", paid_at: "payment_date" };
      for (var dk in dateFields) {
        if (Object.prototype.hasOwnProperty.call(dateFields, dk) && typeof data[dk] !== "undefined") {
          var $df = $form.find('[data-jalali-field="' + dateFields[dk] + '"], .rsd-jalali-date[name="' + dateFields[dk] + '"]');
          if (!$df.length) $df = $form.find('input[name="' + dateFields[dk] + '"]');
          if ($df.length) {
            var jval = gregorianToJalali(data[dk]);
            $df.val(jval);
          }
        }
      }
      // Initial paid amount if provided
      if (typeof data.initial_paid !== "undefined" && data.initial_paid !== null && String(data.initial_paid) !== "") {
        var $pf = $form.find('input[name="initial_paid"]');
        if ($pf.length) $pf.val(data.initial_paid);
      }
      // Trigger live total recalc if that helper exists
      if (typeof window.RSD_FP_ADMIN_INV !== "undefined" && typeof window.RSD_FP_ADMIN_INV.recalc === "function") {
        window.RSD_FP_ADMIN_INV.recalc($form);
      }
    }

    $(document).on("click", "[data-rsd-modal]", function (e) {
      e.preventDefault();
      var modalId = $(this).data("rsdModal");
      if (!modalId) return;
      var $modal = $("#" + modalId);
      if (!$modal.length) return;

      // Determine mode: create or edit
      var mode = $(this).data("rsdInvoiceMode") || "create";
      var $form = $modal.find("form");
      if ($form.length) {
        $form.find(".rsd-form-msg").remove();
        if (mode === "create") {
          $form[0].reset();
          // Default empty hidden id
          $form.find('input[name="invoice_id"]').val("");
          // Default today jalali for invoice_date if empty
          var $invDate = $form.find('.rsd-jalali-date[name="invoice_date"], input[name="invoice_date"]');
          if ($invDate.length && !$.trim($invDate.val())) {
            var today = new Date();
            var jt = gregorianToJalali(today.getFullYear() + "-" + (today.getMonth() + 1) + "-" + today.getDate());
            $invDate.val(jt);
          }
        } else if (mode === "edit") {
          // Prefill from data attributes on the trigger button
          var prefill = {};
          var attrs = [
            "id",
            "client_id",
            "user_id",
            "year",
            "month",
            "invoice_date",
            "due_date",
            "subtotal",
            "tax",
            "discount",
            "total_amount",
            "paid_amount",
            "status",
            "description",
            "initial_paid",
            "payment_method",
          ];
          for (var i = 0; i < attrs.length; i++) {
            var attr = attrs[i];
            var v = $(this).data(attr);
            if (typeof v !== "undefined" && v !== null && v !== "") {
              prefill[attr] = v;
            }
          }
          rsdPrefillInvoiceForm($form, prefill);
        }
      }

      // Update modal title if data-title present
      var title = $(this).data("rsdModalTitle");
      if (title) {
        $modal.find(".rsd-modal-title").text(title);
      } else {
        // Default based on mode
        var def = mode === "edit" ? "ویرایش فاکتور" : "فاکتور جدید";
        if ($modal.find(".rsd-modal-title").length && !$modal.find(".rsd-modal-title").data("preserve")) {
          $modal.find(".rsd-modal-title").text(def);
        }
      }

      $modal.show().addClass("is-open");
      $("body").addClass("rsd-modal-open");
    });

    $(document).on("click", "[data-rsd-modal-close], .rsd-modal", function (e) {
      // If clicking the overlay itself (target equals current)
      if ($(this).hasClass("rsd-modal") && e.target !== this) return;
      e.preventDefault();
      var $modal = $(this).closest(".rsd-modal");
      if (!$modal.length) $modal = $(this);
      $modal.hide().removeClass("is-open");
      $("body").removeClass("rsd-modal-open");
    });

    // ESC closes top-most open modal
    $(document).on("keydown", function (e) {
      if (e.key === "Escape" || e.keyCode === 27) {
        var $open = $(".rsd-modal.is-open").last();
        if ($open.length) {
          $open.hide().removeClass("is-open");
          $("body").removeClass("rsd-modal-open");
        }
      }
    });

    // --- Extended Jalali validation for invoice_date / due_date / payment_date in generic AJAX submit handler ---
    // (Patch the existing handler behaviour: append validation before the existing submit runs by
    //  adding a second delegated submit handler with higher priority checks that can stop propagation.)
    function rsdValidateJalaliField($form, name, label) {
      var $f = $form.find('input[name="' + name + '"]');
      if (!$f.length) return true;
      var raw = $.trim($f.val());
      if (!raw) return true; // empty ok for optional fields; server will enforce required
      var isGreg = /^\d{4}-\d{2}-\d{2}$/.test(raw);
      if (isGreg) return true;
      if (!parseJalaliToGregorian(raw)) {
        $form.find(".rsd-form-msg").remove();
        $form.append(
          '<div class="rsd-form-msg notice notice-error inline" style="padding:8px 12px;margin-top:12px">' + "فرمت " + label + " نامعتبر است. لطفاً با قالب ۱۴۰۴/۰۶/۱۵ (شمسی) وارد کنید." + "</div>",
        );
        $f.focus().select();
        return false;
      }
      return true;
    }

    $(document).on("submit", "[data-rsd-ajax-form]", function (e) {
      var $form = $(this);
      // Also validate invoice_date (required), due_date (optional), payment_date (optional)
      if (!rsdValidateJalaliField($form, "invoice_date", "تاریخ صدور فاکتور")) {
        e.stopImmediatePropagation();
        e.preventDefault();
        return false;
      }
      if (!rsdValidateJalaliField($form, "due_date", "تاریخ سررسید")) {
        e.stopImmediatePropagation();
        e.preventDefault();
        return false;
      }
      if (!rsdValidateJalaliField($form, "payment_date", "تاریخ پرداخت")) {
        e.stopImmediatePropagation();
        e.preventDefault();
        return false;
      }
      // If this is an invoice form (has invoice_id hidden) choose correct action: create or update
      var $id = $form.find('input[name="invoice_id"]');
      if ($id.length) {
        var curId = $.trim($id.val());
        var action = curId !== "" ? "update_invoice" : "create_invoice";
        $form.attr("data-rsd-ajax-form", action);
      }
      return true;
    });

    // --- Custom submit for invoice forms that captures print_url for new invoices ---
    $(document).on("submit", ".rsd-invoice-form", function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      var $form = $(this);
      var $btn = $form.find('[type="submit"]');
      var orig = $btn.text();

      // Basic required check: user_id + invoice_date
      var $uid = $form.find('select[name="user_id"], input[name="user_id"]');
      if (!$uid.length || !$.trim($uid.val())) {
        $form.find(".rsd-form-msg").remove();
        $form.append('<div class="rsd-form-msg notice notice-error inline" style="padding:8px 12px;margin-top:12px">لطفاً مشتری را انتخاب کنید.</div>');
        return;
      }
      var $invDate = $form.find('input[name="invoice_date"]');
      if (!$invDate.length || !$.trim($invDate.val())) {
        $form.find(".rsd-form-msg").remove();
        $form.append('<div class="rsd-form-msg notice notice-error inline" style="padding:8px 12px;margin-top:12px">تاریخ صدور فاکتور الزامی است.</div>');
        return;
      }
      // Run Jalali validation
      if (!rsdValidateJalaliField($form, "invoice_date", "تاریخ صدور فاکتور")) return;
      if (!rsdValidateJalaliField($form, "due_date", "تاریخ سررسید")) return;
      if (!rsdValidateJalaliField($form, "payment_date", "تاریخ پرداخت")) return;

      // Decide action
      var $id = $form.find('input[name="invoice_id"]');
      var curId = $.trim($id.length ? $id.val() : "");
      var action = curId !== "" ? "update_invoice" : "create_invoice";

      $btn.prop("disabled", true).text(RSD_FP_ADMIN.i18n.saving);
      adminAjax(action, $form.serialize(), function (res) {
        $btn.prop("disabled", false).text(orig);
        if (res && res.success) {
          $form.find(".rsd-form-msg").remove();
          var msg = res.data && res.data.message ? res.data.message : RSD_FP_ADMIN.i18n.saved;
          var printUrl = res.data && res.data.print_url ? res.data.print_url : "";
          var html = '<div class="rsd-form-msg notice notice-success inline" style="padding:8px 12px;margin-top:12px">' + msg;
          if (printUrl) {
            html += ' <a href="' + printUrl + '" target="_blank" class="button" style="margin-right:10px">📄 دانلود / چاپ PDF</a>';
          }
          html += "</div>";
          $form.append(html);
          setTimeout(function () {
            location.reload();
          }, 1600);
        } else {
          $form.find(".rsd-form-msg").remove();
          $form.append(
            '<div class="rsd-form-msg notice notice-error inline" style="padding:8px 12px;margin-top:12px">' + ((res && res.data && res.data.message) || RSD_FP_ADMIN.i18n.error) + "</div>",
          );
        }
      });
    });

    // --- Invoice DELETE per-row button ---
    $(document).on("click", ".rsd-invoice-del", function (e) {
      e.preventDefault();
      var invoiceId = parseInt($(this).data("invoiceId") || $(this).data("invoice-id") || "0", 10);
      if (!invoiceId) return;
      var num = String($(this).data("invoiceNumber") || "#" + invoiceId);
      if (!confirm("آیا از حذف فاکتور " + num + " اطمینان دارید؟ این عملیات قابل بازگشت نیست.")) {
        return;
      }
      var $btn = $(this);
      $btn.prop("disabled", true);
      adminAjax("delete_invoice", { invoice_id: invoiceId }, function (res) {
        $btn.prop("disabled", false);
        if (res && res.success) {
          var msg = (res.data && res.data.message) || "فاکتور با موفقیت حذف شد.";
          if (window.rsdToast) window.rsdToast("success", msg);
          else alert(msg);
          setTimeout(function () {
            location.reload();
          }, 800);
        } else {
          var err = (res && res.data && res.data.message) || "خطا در حذف فاکتور.";
          if (window.rsdToast) window.rsdToast("error", err);
          else alert(err);
        }
      });
    });

    // --- Invoice PDF buttons open in new tab (default <a> behavior is fine, this just helps if someone forgets target) ---
    $(document).on("click", ".rsd-invoice-pdf", function (e) {
      var href = $(this).attr("href") || $(this).data("href") || "";
      if (!href) return;
      // If user accidentally placed button on non-anchor element
      if (!$(this).is("a")) {
        e.preventDefault();
        window.open(href, "_blank");
      }
    });

    // --- Jalali Triple-select (Year / Month / Day) wiring ---
    function rsdTripleDateToValue(y, m, d) {
      y = String(y || "").trim();
      m = String(m || "").trim();
      d = String(d || "").trim();
      if (!y || !m || !d) return "";
      // Pad m/d to 2 digits
      if (m.length < 2) m = "0" + m;
      if (d.length < 2) d = "0" + d;
      return y + "/" + m + "/" + d;
    }
    function rsdSyncTripleFromSelects($container) {
      if (!$container || !$container.length) return "";
      var y = $container.find(".rsd-triple-year").val();
      var m = $container.find(".rsd-triple-month").val();
      var d = $container.find(".rsd-triple-day").val();
      var val = rsdTripleDateToValue(y, m, d);
      var target = $container.data("rsdTripleTarget");
      if (target) {
        var $t = $("#" + target.replace(/^#/, ""));
        if ($t.length) $t.val(val).trigger("change");
      }
      return val;
    }
    function rsdSelectOptionByValue($select, value) {
      if (!$select || !$select.length) return;
      var el = $select[0];
      if (!el || !el.options) return;
      value = String(value || "");
      var foundIdx = -1;
      for (var i = 0; i < el.options.length; i++) {
        if (String(el.options[i].value) === value) {
          foundIdx = i;
          break;
        }
      }
      if (foundIdx >= 0) {
        el.selectedIndex = foundIdx;
        try {
          el.options[foundIdx].selected = true;
        } catch (_e) {}
      } else {
        el.selectedIndex = 0;
        if (el.options.length > 0) {
          try {
            el.options[0].selected = true;
          } catch (_e2) {}
        }
      }
    }
    function rsdSyncTripleSelectsFromHidden($container, val) {
      if (!$container || !$container.length) return;
      val = String(val || "").trim();
      var m = /^([1-9]\d{3})[\-\/](0?[1-9]|1[0-2])[\-\/](0?[1-9]|[12]\d|3[01])$/.exec(val);
      var y = "",
        md = "",
        d = "";
      if (m) {
        var yy = parseInt(m[1], 10);
        if (yy >= 1300 && yy <= 2000) {
          y = m[1];
          md = String(parseInt(m[2], 10));
          d = String(parseInt(m[3], 10));
        }
      }
      rsdSelectOptionByValue($container.find(".rsd-triple-year"), y);
      rsdSelectOptionByValue($container.find(".rsd-triple-month"), md);
      rsdSelectOptionByValue($container.find(".rsd-triple-day"), d);
    }
    // Listen for selects change inside every triple-date container
    $(document).on("change", ".rsd-date-triple select", function (e) {
      var $wrap = $(this).closest(".rsd-date-triple");
      rsdSyncTripleFromSelects($wrap);
    });
    // When hidden value of a triple-date is set externally (e.g. during prefill), sync selects
    $(document).on("change", ".rsd-triple-date-hidden", function (e) {
      var $hidden = $(this);
      var sel = '[data-rsd-triple-target="' + $hidden.attr("id") + '"]';
      var $wrap = $(sel);
      if ($wrap.length) {
        rsdSyncTripleSelectsFromHidden($wrap, $hidden.val());
      }
    });

    // --- Client search input wiring (datalist -> hidden user_id) ---
    function rsdFindDatalistOption(inputEl, query) {
      if (!inputEl) return null;
      var $in = $(inputEl);
      var listId = $in.attr("list");
      if (!listId) return null;
      var $opts = $("#" + listId).find("option");
      query = String(query || "").trim();
      if (!query) return null;
      // 1) Exact value match (most common, what browser sets when user picks from dropdown)
      var $match = $opts.filter(function () {
        return String($(this).attr("value") || "").trim() === query;
      });
      if ($match.length) return $match.first();
      // 2) Loose contains match (display_name, login, email)
      var $fuzzy = $opts.filter(function () {
        var v = String($(this).attr("value") || "") + " " + String($(this).text() || "");
        return v.indexOf(query) >= 0;
      });
      if ($fuzzy.length === 1) return $fuzzy.first();
      return null;
    }
    function rsdBindClientSearchInput($in, $storeInput) {
      if (!$in || !$in.length) return;
      if (!$storeInput || !$storeInput.length) {
        var targetSel = $in.closest("[data-rsd-client-store]").data("rsdClientStore");
        if (targetSel) $storeInput = $(targetSel);
      }
      if (!$storeInput || !$storeInput.length) return;
      // User chooses from datalist dropdown
      $in.on("input", function () {
        var val = $.trim($in.val());
        // If empty -> clear hidden
        if (!val) {
          $storeInput.val("");
          return;
        }
        var $opt = rsdFindDatalistOption(this, val);
        if ($opt) {
          var uid = $opt.data("user-id") || $opt.attr("data-user-id") || "";
          if (uid !== "") {
            $storeInput.val(String(uid));
            // Replace input text with exact option label so it's clean
            if ($opt.attr("value")) $in.val($opt.attr("value"));
          } else {
            $storeInput.val("");
          }
        } else {
          // Unrecognized input; clear hidden user_id so server-side validation catches it
          $storeInput.val("");
        }
      });
      $in.on("change", function () {
        // Final safety: ensure we have a valid uid after user leaves the field
        var val = $.trim($in.val());
        if (!val) {
          $storeInput.val("");
          return;
        }
        var $opt = rsdFindDatalistOption(this, val);
        if (!$opt) {
          alert("مشتری انتخاب‌شده معتبر نیست. لطفاً از لیست پیشنهاد شده انتخاب کنید.");
          $in.val("").focus();
          $storeInput.val("");
        }
      });
    }
    // Auto-wire any client search inputs present in DOM
    $(".rsd-client-search-input").each(function () {
      rsdBindClientSearchInput($(this));
    });
    // Wire any that are injected later (modals etc) via delegated ready-like check
    $(document).on("modal:open rsd-modal-open", function () {
      $(".rsd-client-search-input:not([data-rsd-wired])").each(function () {
        $(this).attr("data-rsd-wired", "1");
        rsdBindClientSearchInput($(this));
      });
    });

    // --- Extend prefill to support new fields: client search, year text, triple dates ---
    // (Override placeholder that was set in generic rsdPrefillInvoiceForm above)
    var _origRsdPrefill = window.rsdPrefillInvoiceForm || null;
    // Helper: parse hidden Jalali YYYY/MM/DD -> sync triple selects
    function rsdPrefillTripleFromGregorian($form, fieldName, gregorianValue) {
      if (!$form || !$form.length) return;
      if (!gregorianValue) return;
      var jalaliStr = gregorianToJalali(gregorianValue);
      if (!jalaliStr) return;
      // Find the hidden input for this field
      var $hidden = $form.find('input[name="' + fieldName + '"]');
      if (!$hidden.length) return;
      $hidden.val(jalaliStr).trigger("change"); // triggers sync to selects
    }
    // Helper: set client search input + hidden given user_id
    function rsdPrefillClientByUserId($form, uid) {
      uid = String(uid || "").trim();
      if (!uid) return;
      var $store = $form.find('input[name="user_id"][type="hidden"]');
      if (!$store.length) $store = $form.find('input[name="user_id"]').first();
      if ($store.length) $store.val(uid);
      var $in = $form.find(".rsd-client-search-input");
      if ($in.length) {
        // Find corresponding datalist and set the value from matching option
        var listId = $in.attr("list");
        if (listId) {
          var $opt = $("#" + listId)
            .find('option[data-user-id="' + uid + '"]')
            .first();
          if ($opt.length) {
            var lbl = $opt.attr("value") || $opt.text() || "";
            if (lbl) $in.val(lbl);
          }
        }
      }
    }
    // Hook into modal open: if we're editing, post-process the prefill that already happened.
    $(document).on("click", "[data-rsd-modal]", function (e) {
      var mode = $(this).data("rsdInvoiceMode") || "create";
      if (mode !== "edit") return;
      var modalId = $(this).data("rsdModal");
      var $modal = $("#" + modalId);
      if (!$modal.length) return;
      var $form = $modal.find("form").first();
      if (!$form.length) return;

      var uid = $(this).data("userId") || $(this).data("client-id") || $(this).data("client_id") || "";
      if (uid !== "") rsdPrefillClientByUserId($form, uid);

      var invDateGreg = $(this).data("invoice-date") || $(this).data("invoiceDate") || "";
      if (invDateGreg) rsdPrefillTripleFromGregorian($form, "invoice_date", invDateGreg);

      var dueDateGreg = $(this).data("due-date") || $(this).data("dueDate") || "";
      if (dueDateGreg) rsdPrefillTripleFromGregorian($form, "due_date", dueDateGreg);

      var yr = $(this).data("year") || "";
      if (yr !== "") {
        var $year = $form.find('input[name="year"]');
        if ($year.length) $year.val(String(yr));
      }
    });

    // --- Admin approve / reject pending company info changes ---
    $(document).on("click", "[data-rsd-company-approve]", function (e) {
      e.preventDefault();
      var uid = $(this).data("rsdUserId") || $(this).data("rsd-user-id") || "";
      if (!uid) return;
      if (!confirm(RSD_FP_ADMIN.i18n.confirm || "آیا از تأیید تغییرات اطلاعات شرکت اطمینان دارید؟ پس از تأیید مقادیر جدید در فیلدهای اصلی ثبت می‌شوند.")) return;
      var $btn = $(this);
      $btn.prop("disabled", true).text(RSD_FP_ADMIN.i18n.saving || "...");
      adminAjax("approve_company_changes", { user_id: uid }, function (res) {
        $btn.prop("disabled", false);
        if (res && res.success) {
          alert((res.data && res.data.message) || "تغییرات تأیید شد.");
          setTimeout(function () {
            location.reload();
          }, 500);
        } else {
          alert((res && res.data && res.data.message) || RSD_FP_ADMIN.i18n.error || "خطا در ثبت.");
        }
      });
    });

    $(document).on("click", "[data-rsd-company-reject]", function (e) {
      e.preventDefault();
      var uid = $(this).data("rsdUserId") || $(this).data("rsd-user-id") || "";
      if (!uid) return;
      if (!confirm(RSD_FP_ADMIN.i18n.confirm || "آیا از رد این تغییرات اطمینان دارید؟ درخواست تغییرات پاک خواهد شد.")) return;
      var $btn = $(this);
      $btn.prop("disabled", true).text(RSD_FP_ADMIN.i18n.saving || "...");
      adminAjax("reject_company_changes", { user_id: uid }, function (res) {
        $btn.prop("disabled", false);
        if (res && res.success) {
          alert((res.data && res.data.message) || "درخواست تغییرات رد شد.");
          setTimeout(function () {
            location.reload();
          }, 500);
        } else {
          alert((res && res.data && res.data.message) || RSD_FP_ADMIN.i18n.error || "خطا در ثبت.");
        }
      });
    });
  });
})(jQuery);
