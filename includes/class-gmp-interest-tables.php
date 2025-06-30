<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Procedural callbacks for your two hooks.
 */

/**
 * Admin: after the “Subscriptions” box on the order edit screen,
 * find the linked subscription and render its interest table.
 */
function gmp_admin_related_orders_interest_table( $order ) {
    // only proceed if this order contains a subscription
    if ( ! function_exists( 'wcs_order_contains_subscription' ) || ! wcs_order_contains_subscription( $order ) ) {
        return;
    }

    // grab all subscriptions attached to this order
    $subs = wcs_get_subscriptions_for_order( $order );
    if ( empty( $subs ) ) {
        return;
    }

    // use the first one (if you have multiple, you can loop here)
    $subscription = reset( $subs );
    gmp_render_interest_table( $subscription );
}

/**
 * Frontend: on the “View Subscription” page (woocommerce_subscription_details_table),
 * just render the interest table for the current subscription object.
 */
function gmp_frontend_related_orders_interest_table( $subscription ) {
    if ( ! $subscription instanceof WC_Subscription ) {
        return;
    }
    gmp_render_interest_table( $subscription );
}


/**
 * Shared renderer: outputs the EMI + Interest table.
 */
function gmp_render_interest_table( $subscription ) {
    // get your saved settings (product_id → ['base'=>X,'ext'=>[…]])
    $settings       = get_option( 'gmp_interest_settings', [] );
    // fetch only the “parent” related orders (each renewal)
    $related_orders = wcs_get_related_orders( $subscription, [ 'parent' ] );

    if ( empty( $related_orders ) ) {
        return;
    }

    echo '<h3>Gold Plan EMI + Interest Summary</h3>';
    echo '<table class="shop_table shop_table_responsive">';
    echo   '<thead><tr>
              <th>Order</th>
              <th>Date</th>
              <th>Product</th>
              <th>Base EMI</th>
              <th>Interest %</th>
              <th>Total (EMI + Interest)</th>
            </tr></thead>';
    echo   '<tbody>';

    foreach ( $related_orders as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            continue;
        }

        foreach ( $order->get_items() as $item ) {
            $prod_id     = $item->get_product_id();
            $qty         = max( 1, $item->get_quantity() );
            $base_price  = $item->get_total() / $qty;
            $int_percent = floatval( $settings[ $prod_id ]['base'] ?? 0 );
            $total_amt   = $base_price + ( $base_price * $int_percent / 100 );

            echo '<tr>';
            echo '<td><a href="' . esc_url( get_edit_post_link( $order_id ) ) . '" target="_blank">#' . esc_html( $order->get_order_number() ) . '</a></td>';
            echo '<td>' . esc_html( $order->get_date_created()->date( 'Y-m-d' ) ) . '</td>';
            echo '<td>' . esc_html( $item->get_name() ) . '</td>';
            echo '<td>₹' . number_format( $base_price, 2 ) . '</td>';
            echo '<td>' . number_format( $int_percent, 2 ) . '%</td>';
            echo '<td>₹' . number_format( $total_amt, 2 ) . '</td>';
            echo '</tr>';
        }
    }

    echo   '</tbody>';
    echo '</table>';
}
