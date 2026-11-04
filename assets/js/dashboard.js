/*
 * RSD Finance Panel - Dashboard JavaScript
 * User Portal Interactions
 * Version: 1.0.0
 */
(function ($) {
  "use strict";

  // Shorthand for ajax (accepts both object data AND pre-serialized query-string)
  function rsdAjax(action, data, cb, method = "POST") {
    if (typeof data === "string") {
      // Pre-serialized form string (e.g. from $form.serialize()) — just append action+nonce
      var params = data.length ? data.split("&") : [];
      params.push("action=" + encodeURIComponent("rsd_fp_" + action));
      params.push("nonce=" + encodeURIComponent(RSD_FP.nonce));
      data = params.join("&");
    } else {
      // Plain object — merge defaults
      data = $.extend({ action: "rsd_fp_" + action, nonce: RSD_FP.nonce }, data || {});
    }
    return $.ajax({
      url: RSD_FP.ajax_url,
      type: method,
      data: data,
      dataType: "json",
    })
      .done(function (res) {
        if (typeof cb === "function") cb(res);
      })
      .fail(function () {
        if (typeof cb === "function") cb({ success: false, data: { message: "خطا در برقراری ارتباط با سرور." } });
      });
  }

  // Toast notifications
  function rsdToast(message, type = "success") {
    var container = $(".rsd-toasts");
    if (!container.length) {
      container = $('<div class="rsd-toasts"></div>').appendTo("body");
    }
    var toast = $('<div class="rsd-toast ' + type + '"></div>').text(message);
    container.append(toast);
    setTimeout(function () {
      toast.fadeOut(300, function () {
        $(this).remove();
      });
    }, 3800);
  }

  // --- Jalali calendar helpers (U.M.A 33-year leap cycle, server PHP mirrors these exactly) ---
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
  var jalaliMonthNames = ["", "فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];

  // Accepts Gregorian string "YYYY-MM-DD" (or timestamp) → returns Jalali date string in requested format
  //   Supported: j=day-no-zero, d=day-zero, n=month-no-zero, m=month-zero, F=month-name, Y=4-digit year
  function gregorianToJalali(gInput, format) {
    format = format || "Y/m/d";
    var y, m, d;
    if (typeof gInput === "string" && /^\d{4}-\d{2}-\d{2}$/.test(gInput)) {
      var s = gInput.split("-");
      y = parseInt(s[0], 10);
      m = parseInt(s[1], 10);
      d = parseInt(s[2], 10);
    } else if (typeof gInput === "number" || (typeof gInput === "string" && /^\d+$/.test(gInput))) {
      var t = new Date(typeof gInput === "number" ? gInput * 1000 : parseInt(gInput, 10) * 1000);
      if (isNaN(t.getTime())) t = new Date();
      y = t.getUTCFullYear();
      m = t.getUTCMonth() + 1;
      d = t.getUTCDate();
    } else {
      return "";
    }
    var j = gregorianPartsToJalali(y, m, d);
    var jy = j[0],
      jm = j[1],
      jd = j[2];
    var out = "",
      pad = function (n) {
        return n < 10 ? "0" + n : "" + n;
      };
    for (var i = 0, L = format.length; i < L; i++) {
      var c = format.charAt(i);
      if (c === "Y") out += String(jy);
      else if (c === "y") out += String(jy).slice(-2);
      else if (c === "m") out += pad(jm);
      else if (c === "n") out += String(jm);
      else if (c === "F") out += jalaliMonthNames[jm] || "";
      else if (c === "d") out += pad(jd);
      else if (c === "j") out += String(jd);
      else out += c;
    }
    return out;
  }

  // Parse Jalali input "YYYY/MM/DD" (accepts slashes, dashes, dots, Persian/Arabic numerals)
  // Returns Gregorian "YYYY-MM-DD" on success, or "" on failure.
  function parseJalaliToGregorian(input) {
    input = String(input || "").trim();
    if (!input) return "";
    // Persian/Arabic → ASCII
    var fa = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
    var ar = ["٠", "١", "٢", "٣", "٤", "٥", "٦", "٧", "٨", "٩"];
    for (var i = 0; i < 10; i++) {
      var re = new RegExp("[" + fa[i] + ar[i] + "]", "g");
      input = input.replace(re, String(i));
    }
    var m = /^(\d{4})[\-\/\.](\d{1,2})[\-\/\.](\d{1,2})$/.exec(input);
    if (!m) return "";
    var jy = parseInt(m[1], 10),
      jm = parseInt(m[2], 10),
      jd = parseInt(m[3], 10);
    if (jy < 1200 || jy > 1600 || jm < 1 || jm > 12 || jd < 1 || jd > 31) return "";
    if (jm >= 7 && jd > 30) return "";
    if (jm === 12) {
      // Verify round-trip of Esfand 30 (leap year check)
      var tmp = jalaliPartsToGregorian(jy, 12, 30);
      var back = gregorianPartsToJalali(tmp[0], tmp[1], tmp[2]);
      if (!(back[0] === jy && back[1] === 12 && back[2] === 30)) {
        if (jd > 29) return "";
      }
    }
    var g = jalaliPartsToGregorian(jy, jm, jd);
    var pn = function (n) {
      return n < 10 ? "0" + n : "" + n;
    };
    return g[0] + "-" + pn(g[1]) + "-" + pn(g[2]);
  }

  function formatSize(bytes) {
    bytes = parseInt(bytes, 10) || 0;
    if (!bytes) return "";
    if (bytes < 1024) return bytes + " بایت";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1).replace(/\.0$/, "") + " KB";
    return (bytes / (1024 * 1024)).toFixed(2).replace(/\.00$/, "") + " MB";
  }

  // Server-generated dates: "YYYY-MM-DD HH:MM:SS" local WP time → localized Jalali j F Y
  function formatJalaliFull(dbDate) {
    if (!dbDate) return "";
    // Parse local MySQL date as local time
    var d = new Date(String(dbDate).replace(/-/g, "/"));
    if (isNaN(d.getTime())) return String(dbDate).slice(0, 10);
    return gregorianToJalali(d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate(), "j F Y");
  }

  // Modal open/close
  function openModal(id) {
    $("#" + id)
      .addClass("show")
      .find("input, textarea, select")
      .first()
      .focus();
    $("body").css("overflow", "hidden");
  }

  function closeModal(id) {
    $("#" + id).removeClass("show");
    $("body").css("overflow", "");
  }

  $(document).ready(function () {
    // --- Render current-document preview panel (in upload modal) from a doc object.
    //     Also fills all form fields with the same values (converting expiry_date → Jalali text)
    function renderCurrentDocPreview(doc, $modal) {
      $modal = $modal || $("#rsd-upload-modal");
      var $preview = $modal.find(".rsd-current-doc-preview");
      var $form = $modal.find("#rsd-upload-doc-form");
      if (!doc) {
        $preview.hide();
        return;
      }
      var jalaliExpiry = doc.expiry_date ? gregorianToJalali(String(doc.expiry_date), "Y/m/d") : "";
      var jalaliExpiryLong = doc.expiry_date ? gregorianToJalali(String(doc.expiry_date), "j F Y") : "-";
      var jalaliUploaded = doc.created_at ? formatJalaliFull(doc.created_at) : "-";

      // Populate read-only preview
      $preview.find(".rsd-cur-title").text(doc.title || "-");
      $preview.find(".rsd-cur-category").text(doc.category_label || doc.category || "-");
      $preview.find(".rsd-cur-year").text(doc.year || "-");
      $preview.find(".rsd-cur-month").text(doc.month || "-");
      $preview.find(".rsd-cur-period").text(doc.period || "-");
      $preview.find(".rsd-cur-expiry").text(jalaliExpiryLong);
      $preview.find(".rsd-cur-uploaded").text(jalaliUploaded);
      $preview.find(".rsd-cur-description").text(doc.description || "-");

      var $fileRow = $preview.find(".rsd-cur-file-row");
      if (doc.file_path) {
        $fileRow.show();
        $preview.find(".rsd-cur-filename").text(doc.file_name || (doc.file_path ? doc.file_path.split("/").pop() : ""));
        $preview.find(".rsd-cur-filesize").text(formatSize(doc.file_size));
        $preview.find(".rsd-cur-download").attr("href", doc.file_path);
      } else {
        $fileRow.hide();
      }
      $preview.show();

      // Fill editable form fields (same values, user can change them before submit)
      $form.find("#rsd_doc_id").val(String(doc.id || ""));
      $form.find('input[name="title"]').val(doc.title || "");
      $form.find('select[name="category"]').val(doc.category || "");
      $form.find('select[name="year"]').val(doc.year || "");
      $form.find('select[name="month"]').val(doc.month || "");
      $form.find('input[name="period"]').val(doc.period || "");
      $form.find('textarea[name="description"]').val(doc.description || "");
      $form.find('input[name="expiry_date"]').val(jalaliExpiry);
    }

    function hideCurrentDocPreview() {
      var $modal = $("#rsd-upload-modal");
      $modal.find(".rsd-current-doc-preview").hide();
      $modal.find("#rsd-upload-doc-form #rsd_doc_id").val("");
    }

    // --- Modal triggers ---
    $(document).on("click", "[data-modal]", function (e) {
      var $btn = $(this);
      var modalId = $btn.data("modal");
      var docId = parseInt($btn.data("docId") || $btn.data("doc-id") || "0", 10);
      var $modal = $("#rsd-upload-modal");
      var $form = $modal.find("#rsd-upload-doc-form");
      var $heading = $modal.find(".rsd-modal-title").first();

      // Stash the original heading the first time we touch it, so we can safely
      // reset back to it for "new document" paths.
      if ($heading.length && !$heading.data("original")) {
        $heading.data("original", $heading.text());
      }

      // Re-upload for an existing incomplete document: pre-fill every field so the
      // user can just choose a new file & submit (this triggers the UPDATE path
      // in Ajax::upload_document, which sets status=uploaded & is_incomplete=0).
      if (modalId === "rsd-upload-modal" && docId > 0) {
        var doc = null;
        if (window.RSD_FP && window.RSD_FP.documentsById) {
          doc = window.RSD_FP.documentsById[docId];
        }
        // Always set the hidden rsd_doc_id. Even if doc object is unavailable
        // for preview (e.g. script load race), the server still needs this id
        // to UPDATE the existing row instead of inserting a new one.
        if ($form.length) {
          $form.find("#rsd_doc_id").val(String(docId));
        }
        if (doc) {
          renderCurrentDocPreview(doc, $modal);
          if ($heading.data("original")) {
            $heading.text("جایگزینی مدرک ناقص: " + (doc.title || "#" + docId));
          }
        } else {
          // Doc unavailable in JS map (shouldn't happen now but be safe):
          // keep the default heading but show a small notice via preview hide.
          hideCurrentDocPreview();
        }
      } else if (modalId === "rsd-upload-modal") {
        // Plain "new document" button: reset any leftovers from a previous re-upload.
        if ($form.length) {
          $form[0].reset();
          hideCurrentDocPreview();
          if ($heading.data("original")) {
            $heading.text($heading.data("original"));
          }
        }
      }

      e.preventDefault();
      openModal(modalId);
    });

    $(document).on("click", ".rsd-modal-close, [data-close-modal]", function () {
      closeModal($(this).closest(".rsd-modal-backdrop").attr("id") || $(this).data("close-modal"));
    });

    $(document).on("click", ".rsd-modal-backdrop", function (e) {
      if (e.target === this) ($(this).removeClass("show"), $("body").css("overflow", ""));
    });

    // --- Validate Jalali date fields on forms that carry expiry_date.
    //     (Server-side `normalize_expiry` accepts either format, but JS error is faster feedback.)
    function validateExpiryDate($form, allowEmpty) {
      var $field = $form.find('input[name="expiry_date"]');
      if (!$field.length) return true;
      var raw = $.trim($field.val());
      if (!raw) return !!allowEmpty;
      // Legacy Gregorian dash-format (YYYY-MM-DD) is also acceptable.
      if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return true;
      var ok = !!parseJalaliToGregorian(raw);
      if (!ok) {
        rsdToast("فرمت تاریخ انقضا نامعتبر است. لطفاً با قالب ۱۴۰۴/۰۶/۱۵ وارد کنید.", "error");
        $field.focus().select();
      }
      return ok;
    }

    // --- Upload document form ---
    $(document).on("submit", "#rsd-upload-doc-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      if (!validateExpiryDate($form, true)) return;

      var $btn = $form.find('button[type="submit"]');
      var origBtn = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      var formData = new FormData(this);
      formData.append("action", "rsd_fp_upload_document");
      formData.append("nonce", RSD_FP.nonce);

      $.ajax({
        url: RSD_FP.ajax_url,
        type: "POST",
        data: formData,
        dataType: "json",
        processData: false,
        contentType: false,
      })
        .done(function (res) {
          if (res.success) {
            rsdToast(res.data.message || "با موفقیت ثبت شد.", "success");
            $form[0].reset();
            closeModal($form.closest(".rsd-modal-backdrop").attr("id"));
            setTimeout(function () {
              location.reload();
            }, 1000);
          } else {
            rsdToast(res.data.message || "خطا رخ داد.", "error");
          }
        })
        .fail(function () {
          rsdToast("خطا در برقراری ارتباط.", "error");
        })
        .always(function () {
          $btn.prop("disabled", false).html(origBtn);
        });
    });

    // --- Quick upload on dashboard ---
    $(document).on("submit", "#rsd-quick-upload-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var origBtn = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      var formData = new FormData(this);
      formData.append("action", "rsd_fp_upload_document");
      formData.append("nonce", RSD_FP.nonce);

      $.ajax({
        url: RSD_FP.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
      })
        .done(function (res) {
          if (res.success) {
            rsdToast(res.data.message || "مدرک با موفقیت بارگذاری شد.", "success");
            $form[0].reset();
            setTimeout(function () {
              location.reload();
            }, 900);
          } else {
            rsdToast(res.data.message || "خطا در بارگذاری.", "error");
          }
        })
        .fail(function () {
          rsdToast("خطا در ارتباط.", "error");
        })
        .always(function () {
          $btn.prop("disabled", false).html(origBtn);
        });
    });

    // --- Delete document ---
    $(document).on("click", "[data-delete-document]", function (e) {
      e.preventDefault();
      if (!confirm(RSD_FP.i18n.confirm)) return;
      var docId = $(this).data("delete-document");
      rsdAjax("delete_document", { doc_id: docId }, function (res) {
        if (res.success) {
          rsdToast(res.data.message || "حذف شد.", "success");
          setTimeout(function () {
            location.reload();
          }, 700);
        } else {
          rsdToast(res.data.message || "خطا در حذف.", "error");
        }
      });
    });

    // --- Create ticket form ---
    $(document).on("submit", "#rsd-new-ticket-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var orig = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      rsdAjax("create_ticket", $form.serialize(), function (res) {
        $btn.prop("disabled", false).html(orig);
        if (res.success) {
          rsdToast(res.data.message || "تیکت ثبت شد.", "success");
          closeModal($form.closest(".rsd-modal-backdrop").attr("id"));
          setTimeout(function () {
            location.reload();
          }, 900);
        } else {
          rsdToast(res.data.message || "خطا رخ داد.", "error");
        }
      });
    });

    // --- Reply ticket form ---
    $(document).on("submit", "#rsd-reply-ticket-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var orig = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      var formData = new FormData(this);
      formData.append("action", "rsd_fp_reply_ticket");
      formData.append("nonce", RSD_FP.nonce);

      $.ajax({
        url: RSD_FP.ajax_url,
        type: "POST",
        data: formData,
        dataType: "json",
        processData: false,
        contentType: false,
      })
        .done(function (res) {
          $btn.prop("disabled", false).html(orig);
          if (res.success) {
            rsdToast(res.data.message || "پاسخ ارسال شد.", "success");
            setTimeout(function () {
              location.reload();
            }, 800);
          } else {
            rsdToast(res.data.message || "خطا در ارسال.", "error");
          }
        })
        .fail(function () {
          $btn.prop("disabled", false).html(orig);
          rsdToast("خطا در ارتباط.", "error");
        });
    });

    // --- Close ticket ---
    $(document).on("click", "[data-close-ticket]", function (e) {
      e.preventDefault();
      if (!confirm(RSD_FP.i18n.confirm)) return;
      var tId = $(this).data("close-ticket");
      rsdAjax("close_ticket", { ticket_id: tId }, function (res) {
        if (res.success) {
          rsdToast(res.data.message || "تیکت بسته شد.", "success");
          setTimeout(function () {
            location.reload();
          }, 700);
        } else {
          rsdToast(res.data.message || "خطا.", "error");
        }
      });
    });

    // --- Company info save (pending changes mode) ---
    $(document).on("submit", "#rsd-company-info-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var orig = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      rsdAjax("submit_company_changes", $form.serialize(), function (res) {
        $btn.prop("disabled", false).html(orig);
        if (res.success) {
          rsdToast(res.data.message || "تغییرات برای تأیید ارسال شد.", "success");
          setTimeout(function () {
            location.reload();
          }, 600);
        } else {
          rsdToast(res.data.message || "خطا در ذخیره.", "error");
        }
      });
    });

    // --- Service request form ---
    $(document).on("submit", "[data-service-form]", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var orig = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      rsdAjax("submit_service_request", $form.serialize(), function (res) {
        $btn.prop("disabled", false).html(orig);
        if (res.success) {
          rsdToast(res.data.message || "درخواست شما ثبت شد.", "success");
          setTimeout(function () {
            location.reload();
          }, 1000);
        } else {
          rsdToast(res.data.message || "خطا در ثبت درخواست.", "error");
        }
      });
    });

    // --- Service type selector (services page) ---
    $(document).on("click", "[data-service-select]", function (e) {
      e.preventDefault();
      var type = $(this).data("service-select");
      $("[data-service-wrapper]").hide();
      $('[data-service-wrapper="' + type + '"]').fadeIn(200);
      $(".rsd-service-card").removeClass("active");
      $(this).closest(".rsd-service-card").addClass("active");
      $("html, body").animate({ scrollTop: $('[data-service-wrapper="' + type + '"]').offset().top - 80 }, 300);
    });

    // --- Meeting request form ---
    $(document).on("submit", "#rsd-meeting-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var orig = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      rsdAjax("request_meeting", $form.serialize(), function (res) {
        $btn.prop("disabled", false).html(orig);
        if (res.success) {
          rsdToast(res.data.message || "درخواست جلسه ثبت شد.", "success");
          closeModal($form.closest(".rsd-modal-backdrop").attr("id"));
          setTimeout(function () {
            location.reload();
          }, 800);
        } else {
          rsdToast(res.data.message || "خطا.", "error");
        }
      });
    });

    // --- Quick add event form ---
    $(document).on("submit", "#rsd-event-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var orig = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span>');

      rsdAjax("create_calendar_event", $form.serialize(), function (res) {
        $btn.prop("disabled", false).html(orig);
        if (res.success) {
          rsdToast(res.data.message || "موعد برای تأیید ثبت شد.", "success");
          closeModal($form.closest(".rsd-modal-backdrop").attr("id"));
          setTimeout(function () {
            location.reload();
          }, 800);
        } else {
          rsdToast(res.data.message || "خطا در ثبت.", "error");
        }
      });
    });

    // --- Delete event ---
    $(document).on("click", "[data-delete-event]", function (e) {
      e.preventDefault();
      if (!confirm(RSD_FP.i18n.confirm)) return;
      var id = $(this).data("delete-event");
      rsdAjax("delete_calendar_event", { event_id: id }, function (res) {
        if (res.success) {
          rsdToast(res.data.message || "حذف شد.", "success");
          setTimeout(function () {
            location.reload();
          }, 700);
        } else {
          rsdToast(res.data.message || "خطا.", "error");
        }
      });
    });

    // --- Upload area drag & drop ---
    $(".rsd-upload-area").each(function () {
      var $area = $(this);
      var $input = $area.find('input[type="file"]');

      $area.on("click", function (e) {
        if ($(e.target).closest("input, label, button").length === 0) {
          $input.trigger("click");
        }
      });

      $input.on("change", function () {
        if (this.files.length) {
          $area.addClass("dragover");
          var $label = $area.find(".upload-text");
          if ($label.length) $label.text(this.files.length + " فایل انتخاب شد");
        }
      });

      $area.on("dragover dragenter", function (e) {
        e.preventDefault();
        $area.addClass("dragover");
      });

      $area.on("dragleave dragend drop", function (e) {
        e.preventDefault();
        $area.removeClass("dragover");
      });

      $area.on("drop", function (e) {
        e.preventDefault();
        var files = e.originalEvent.dataTransfer.files;
        if (files.length) {
          $input.prop("files", files);
          $input.trigger("change");
        }
      });
    });

    // --- Batch upload documents (on documents page) ---
    $(document).on("submit", "#rsd-batch-upload-form", function (e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var orig = $btn.html();
      $btn.prop("disabled", true).html('<span class="rsd-spinner"></span> در حال آپلود...');

      var formData = new FormData(this);
      formData.append("action", "rsd_fp_batch_upload_documents");
      formData.append("nonce", RSD_FP.nonce);

      $.ajax({
        url: RSD_FP.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
      })
        .done(function (res) {
          $btn.prop("disabled", false).html(orig);
          if (res.success) {
            rsdToast(res.data.message, "success");
            setTimeout(function () {
              location.reload();
            }, 900);
          } else {
            rsdToast(res.data.message || "خطا در آپلود.", "error");
          }
        })
        .fail(function () {
          $btn.prop("disabled", false).html(orig);
          rsdToast("خطا در ارتباط با سرور.", "error");
        });
    });

    // --- Dismiss notification ---
    $(document).on("click", "[data-dismiss-notif]", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = $(this).data("dismiss-notif");
      var $el = $(this).closest(".rsd-notif-item");
      decrementNotifBadge(1);
      $el.slideUp(180, function () {
        $(this).remove();
        maybeShowEmptyNotifs();
      });
      rsdAjax("dismiss_notification", { notif_id: id });
    });

    // Mark all notifications as read
    $(document).on("click", "[data-mark-all-read]", function (e) {
      e.preventDefault();
      var $btn = $(this);
      var before = $(".rsd-notif-list .rsd-notif-item.unread").length;
      $btn.prop("disabled", true);
      rsdAjax("read_notifications", {}, function (res) {
        if (res.success) {
          $(".rsd-notif-list .rsd-notif-item").removeClass("unread");
          $(".rsd-notif-list .rsd-notif-item").slideUp(200, function () {
            $(this).remove();
            maybeShowEmptyNotifs();
          });
          decrementNotifBadge(before, true);
        }
      }).always(function () {
        $btn.prop("disabled", false);
      });
    });

    // --- Notification bell toggle + click outside ---
    var $bell = $("#rsd-notif-bell");
    var $dropdown = $("#rsd-notif-dropdown");
    if ($bell.length && $dropdown.length) {
      $bell.on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var isOpen = $dropdown.css("display") !== "none";
        if (isOpen) closeNotifDropdown();
        else openNotifDropdown();
      });
      $(document).on("click", function (e) {
        if (!$dropdown.is(":visible")) return;
        if ($(e.target).closest($dropdown).length) return;
        if ($(e.target).closest($bell).length) return;
        closeNotifDropdown();
      });
      $(document).on("keydown", function (e) {
        if (e.key === "Escape" && $dropdown.is(":visible")) closeNotifDropdown();
      });
    }

    function openNotifDropdown() {
      $dropdown.slideDown(180);
      $bell.attr("aria-expanded", "true").addClass("open");
    }
    function closeNotifDropdown() {
      $dropdown.slideUp(160);
      $bell.attr("aria-expanded", "false").removeClass("open");
    }
    function decrementNotifBadge(n, resetAll) {
      var $badge = $("#rsd-notif-badge");
      if (!$badge.length) return;
      if (resetAll) {
        $badge.text("0").hide();
        $(".rsd-notif-dropdown-head strong").text("بدون اعلان خوانده‌نشده");
        return;
      }
      var cur = parseInt($badge.text(), 10) || 0;
      cur = Math.max(0, cur - (parseInt(n, 10) || 1));
      if (cur <= 0) $badge.text("0").hide();
      else $badge.text(cur > 99 ? "99+" : String(cur)).show();
      var txt = cur === 0 ? "بدون اعلان خوانده‌نشده" : cur + " اعلان خوانده‌نشده";
      $(".rsd-notif-dropdown-head strong").text(txt);
    }
    function maybeShowEmptyNotifs() {
      var $list = $(".rsd-notif-list");
      if (!$list.length) return;
      if ($list.find(".rsd-notif-item").length === 0) {
        $list.html('<div class="rsd-notif-empty"><div class="rsd-notif-empty-icon">🎉</div>' + "<h4>همه اعلان‌ها خوانده شده‌اند</h4><p>اعلان‌های جدید اینجا نمایش داده می‌شوند.</p></div>");
      }
    }

    // --- Click-through a notification row: mark as read (AJAX), decrement count,
    //     close dropdown, then navigate to reference page.
    $(document).on("click", ".rsd-notif-clickable", function (e) {
      // If user clicked the dismiss (✕) button, let its own handler fire instead.
      if ($(e.target).closest("[data-dismiss-notif]").length) return;

      e.preventDefault();
      var $row = $(this);
      var notifId = parseInt($row.data("notif-id"), 10) || 0;
      var href = $row.data("navigate-to") || ($row.is("a") ? $row.attr("href") : "");

      decrementNotifBadge(1);
      $row.removeClass("unread");
      $row.fadeOut(160, function () {
        $(this).remove();
        maybeShowEmptyNotifs();
      });
      closeNotifDropdown();

      var onDone = function () {
        if (href) {
          window.location.href = href;
        }
      };

      if (notifId) {
        rsdAjax("dismiss_notification", { notif_id: notifId }, function () {
          onDone();
        });
        // fail-safe: navigate even if the ajax is slow/failed
        setTimeout(onDone, 1200);
      } else {
        onDone();
      }
    });

    // --- Calendar month navigation is now server-side via GET params
    // (mini_y/mini_m for dashboard mini, cal_y/cal_m for full calendar page).
    // The previous data-cal-nav toast placeholder is removed.

    // --- Keyboard shortcut: ESC closes any open modal ---
    $(document).on("keydown", function (e) {
      if (e.key === "Escape") ($(".rsd-modal-backdrop.show").removeClass("show"), $("body").css("overflow", ""));
    });
  }); // doc ready
})(jQuery);
