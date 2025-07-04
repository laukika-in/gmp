jQuery(document).ready(function($) {
    $('.gmp-action-btn').click(function(e) {
        e.preventDefault();
        const btn = $(this);
        const cycleId = btn.data('cycle-id');
        const action = btn.data('action');
        let confirmMsg = '';

        switch (action) {
            case 'close':
                confirmMsg = "This will permanently mark the cycle as CLOSED. No further payments can be made. Are you sure?";
                break;
            case 'cancel':
                confirmMsg = "This will CANCEL the cycle. All future EMIs will be deleted. Paid ones will remain. Are you sure?";
                break;
            case 'stop':
                confirmMsg = "This will stop future EMIs. Existing EMIs will stay, but nothing new will be generated. Continue?";
                break;
        }

        if (!confirm(confirmMsg)) return;

        $.post(ajaxurl, {
            action: 'gmp_admin_cycle_action',
            cycle_id: cycleId,
            do_action: action,
            _ajax_nonce: gmp_admin.nonce
        }, function(response) {
            if (response.success) {
                alert('Action completed successfully.');
                location.reload();
            } else {
                alert('Failed: ' + response.data);
            }
        });
    });
});
