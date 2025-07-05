jQuery(document).ready(function ($) {
  $(".gmp-action-btn").on("click", function (e) {
    e.preventDefault();
    let $btn = $(this);
    let action = $btn.data("action");
    let cycleId = $btn.data("cycle-id");

    let messages = {
      close:
        "Are you sure you want to mark this cycle as CLOSED?\n\nYES: Cycle will be closed.\nNO: Nothing will happen.",
      cancel:
        "Are you sure you want to CANCEL this cycle?\n\nYES: All future EMIs will be removed and cycle marked as cancelled.\nNO: Nothing will happen.",
      stop: "Are you sure you want to STOP future EMIs?\n\nYES: Upcoming EMIs will be removed, past payments retained.\nNO: Nothing will happen.",
      hold: "Are you sure you want to HOLD future payments?\n\nYES: Cycle status will be marked as 'hold'.\nNO: Nothing will happen.",
      resume:
        "Resume payments?\n\nYES: Cycle will be marked ACTIVE again.\nNO: Nothing will happen.",
    };

    if (!confirm(messages[action])) return;

    $.post(
      ajaxurl,
      {
        action: "gmp_admin_cycle_action",
        cycle_id: cycleId,
        type: action,
        _wpnonce: GMP_Admin_Actions.nonce,
      },
      function (resp) {
        if (resp.success) {
          alert(resp.data.message);
          location.reload();
        } else {
          alert("Error: " + resp.data.message);
        }
      }
    );
  });
  $(".gmp-buy-now").on("click", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const termsId = $btn.data("terms");
    if (!$(termsId).is(":checked")) {
      $.fancybox.open({
        src: "/digital-gold-terms/",
        type: "iframe",
        opts: {
          iframe: { preload: false },
          afterClose: function () {
            alert("Please accept Terms & Conditions to proceed.");
          },
        },
      });
    } else {
      window.location.href = $btn.data("url");
    }
  });
});
