<?php
/**
 * Show "Refunded" status on order row in order list
 * 70 is an arbitrarily chosen order status ID, so as not to interfere with any other status.
 */
if (!empty($smarty_data['list_orders'])) {
    foreach ($smarty_data['list_orders'] as  $key => $order) {
        if ($order['status_class'] == 'order_status_70') {
            $smarty_data['list_orders'][$key]['status'] = 'Refunded';
            $smarty_data['list_orders'][$key]['status_class'] .= ' " style="color:#acac15';
        }
    }
}

/** reassign orders to the list */
$GLOBALS['smarty']->assign('ORDER_LIST', $smarty_data['list_orders']);