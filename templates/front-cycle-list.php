<h2>My Gold Plans</h2>
<?php if (empty($cycles)): ?>
    <p>You have no active Gold Money Plan subscriptions.</p>
<?php else: ?>
    <table class="shop_table shop_table_responsive">
        <thead>
            <tr>
                <th>Product</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>EMI</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cycles as $cycle): ?>
                <tr>
                    <td><?= esc_html($cycle->product_name) ?></td>
                    <td><?= esc_html($cycle->start_date) ?></td>
                    <td><?= esc_html($cycle->end_date) ?></td>
                    <td><?= wc_price($cycle->emi_amount) ?></td>
                    <td><?= esc_html(ucfirst($cycle->status)) ?></td>
                    <td><a href="<?= esc_url(add_query_arg(['gmp_view' => 'cycle_detail', 'cycle_id' => $cycle->id])) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
