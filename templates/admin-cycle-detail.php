<div class="wrap">
    <h1>Subscription Detail</h1>
    <p><strong>Customer:</strong> <?= esc_html($user->display_name) ?> (<?= esc_html($user->user_email) ?>)</p>
    <p><strong>Product:</strong> <?= esc_html($cycle->product_name) ?></p>
    <p><strong>Start:</strong> <?= esc_html($cycle->start_date) ?> | 
       <strong>End:</strong> <?= esc_html($cycle->end_date) ?></p>
    <p><strong>EMI:</strong> <?= wc_price($cycle->emi_amount) ?> | 
       <strong>Status:</strong> <?= esc_html(ucfirst($cycle->status)) ?></p>

    <h2>EMI Payment Schedule</h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>Instalment</th>
                <th>Date</th>
                <th>Order</th>
                <th>Interest %</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedule as $idx => $emi): ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td><?= esc_html($emi['date']) ?></td>
                    <td><?= $emi['order_id'] ? '<a href="' . esc_url(get_edit_post_link($emi['order_id'])) . '">#' . $emi['order_id'] . '</a>' : '-' ?></td>
                    <td><?= number_format_i18n($emi['interest'], 2) ?>%</td>
                    <td><?= wc_price($emi['total']) ?></td>
                    <td><?= $emi['paid'] ? 'Paid' : 'Pending' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
