jQuery(document).ready(function($) {
    $('.gmp-action-btn').on('click', function(e) {
        e.preventDefault();

        var cycleId = $(this).data('cycle-id');
        var actionType = $(this).data('action');
        var messages = {
            close: 'Marking the cycle as closed means no further EMIs are expected.',
            cancel: 'Cancelling will mark the entire cycle as cancelled. This cannot be undone.',
            stop: 'Stopping future EMIs will prevent upcoming EMIs from being created. Already due EMIs remain.'
        };

        if (!confirm(messages[actionType] + '\n\nAre you sure?')) return;

        $.post(ajaxurl, {
            action: 'gmp_admin_cycle_action',
            cycle_id: cycleId,
            cycle_action: actionType,
            _wpnonce: GMP_Admin_Actions.nonce
        }, function(response) {
            if (response.success) {
                alert('Action completed: ' + response.data.message);
                location.reload();
            } else {
                alert('Failed: ' + response.data.message);
            }
        });
    });
});