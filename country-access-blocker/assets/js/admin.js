(function () {
  "use strict";

  function q(sel, root) {
    return (root || document).querySelector(sel);
  }
  function qa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  // ---- Dev Card: dismiss + delayed reveal after enabling ----
  function initDevCard() {
    try {
      var card = document.getElementById("cab-dev-card");
      if (!card) return;

      var page = document.querySelector(".cab-page");
      var isEnabled = page && page.classList.contains("cab-enabled");

      // card exists only when enabled, but still guard
      if (!isEnabled) return;

      var dismissKey =
        card.getAttribute("data-dismiss-key") || "cab_dev_card_dismiss_until";
      var now = Date.now();
      var until = parseInt(localStorage.getItem(dismissKey) || "0", 10);

      // if dismissed -> keep hidden
      if (until && until > now) {
        card.style.display = "none";
        return;
      }

      // close button
      var btn = card.querySelector(".cab-close");
      if (btn) {
        btn.addEventListener("click", function () {
          var thirtyDaysMs = 30 * 24 * 60 * 60 * 1000;
          localStorage.setItem(dismissKey, String(Date.now() + thirtyDaysMs));
          card.style.display = "none";
        });
      }

      // delayed reveal: 30s after first enabling
      var delayMs = 30 * 1000;
      var sinceKey = "cab_ip_enabled_since_v1";
      var justEnabled = page && page.getAttribute("data-just-enabled") === "1";

      var since = parseInt(localStorage.getItem(sinceKey) || "0", 10);

      // If just enabled now OR first time we see enabled state -> set "since"
      if (justEnabled || !since) {
        since = Date.now();
        localStorage.setItem(sinceKey, String(since));
      }

      var elapsed = Date.now() - since;
      var wait = delayMs - elapsed;

      function showCardIfNotDismissed() {
        var now2 = Date.now();
        var until2 = parseInt(localStorage.getItem(dismissKey) || "0", 10);
        if (until2 && until2 > now2) return;
        card.style.display = "";
      }

      if (wait <= 0) {
        showCardIfNotDismissed();
      } else {
        setTimeout(showCardIfNotDismissed, wait);
      }
    } catch (e) {}
  }

  // ---- Bulk actions ----
  function setAllBlockCheckboxes(checked) {
    qa('input[type="checkbox"]').forEach(function (cb) {
      if (cb.name && cb.name.indexOf("block[") === 0) cb.checked = checked;
    });
  }

  function blockAllExceptMine(mine) {
    setAllBlockCheckboxes(true);
    var own = q('input[name="block[' + mine + ']"]');
    if (own) own.checked = false;
  }

  function unblockAll() {
    setAllBlockCheckboxes(false);
  }

  function initBulkButtons() {
    var page = document.querySelector(".cab-page");
    if (!page) return;

    var mine = page.getAttribute("data-mine") || "";

    var btnBlock = document.getElementById("cab-btn-block-except-mine");
    if (btnBlock) {
      btnBlock.addEventListener("click", function () {
        blockAllExceptMine(mine);
      });
    }

    var btnUnblock = document.getElementById("cab-btn-unblock-all");
    if (btnUnblock) {
      btnUnblock.addEventListener("click", function () {
        unblockAll();
      });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    initDevCard();
    initBulkButtons();
  });
})();
