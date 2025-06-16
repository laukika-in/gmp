<?php
if (!is_user_logged_in()) {
    echo '<p>Please login to view your Gold Money Plan dashboard.</p>';
    return;
}

$user_id = get_current_user_id();
$plan = get_user_meta($user_id, 'gmp_plan', true);

if (!$plan) {
    echo '<p>You have not subscribed to any GMP plan yet.</p>';
    return;
}

$paid_months = count($plan['paid_months']);
$locked = $plan['locked'] ? 'Yes' : 'No';
$redeemed = $plan['redeemed'] ? 'Yes' : 'No';
$balance = $plan['balance'] ?? 0;
?>

<div class="gmp-dashboard">
    <h2>Your GMP Plan</h2>
    <p><strong>EMI:</strong> ₹<?php echo esc_html($plan['emi']); ?></p>
    <p><strong>Duration:</strong> <?php echo esc_html($plan['duration']); ?> months</p>
    <p><strong>Paid Months:</strong> <?php echo $paid_months; ?></p>
    <p><strong>Locked:</strong> <?php echo $locked; ?></p>
    <p><strong>Redeemed:</strong> <?php echo $redeemed; ?></p>
    <p><strong>Available Balance:</strong> ₹<?php echo esc_html($balance); ?></p>

    <?php if (!$plan['redeemed'] && $paid_months >= $plan['duration']) : ?>
        <button id="gmp-redeem-btn">Redeem Now</button>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const redeemBtn = document.getElementById('gmp-redeem-btn');
    if (redeemBtn) {
        redeemBtn.addEventListener('click', function () {
            if (confirm("Are you sure you want to redeem your plan? This will lock future EMI payments.")) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=gmp_redeem', {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Redemption successful! Balance: ₹" + data.data.balance);
                        location.reload();
                    } else {
                        alert("Redemption failed: " + data.data);
                    }
                });
            }
        });
    }
});
</script>
