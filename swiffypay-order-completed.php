<?php
/*
 * @wordpress-plugin
 * Plugin Name:       Swiffypay Order Completed
 * Plugin URI:        https://yourpropfirm.com/
 * Description:       Add-On for Swiffypay to change status order to completed 
 * Version:           1.0.1
 * Author:            Ardika JM Consulting
 * Author URI:        https://yourpropfirm.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/
function swiffypay_add_submenu() {
    add_submenu_page(
        'woocommerce',
        'Swiffpay Order Status',
        'Swiffpay Order Status',
        'manage_options',
        'swiffpay-order-status',
        'swiffpay_order_status_page'
    );
}
add_action('admin_menu', 'swiffpay_add_submenu');

function swiffpay_order_status_page() {
    ?>
    <div class="wrap">
        <h2>Swiffpay Order Completed Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('swiffpay_order_completed_settings_group');
            do_settings_sections('swiffpay-order-status');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function swiffpay_order_completed_settings_init() {
    register_setting('swiffpay_order_completed_settings_group', 'swiffpay_order_completed_settings');
    add_settings_section('swiffpay_order_completed_general_section', 'Swiffpay Order Completed Settings', null, 'swiffpay-order-status');
    add_settings_field('swiffpay_enable_plugin', 'Enable Plugin', 'swiffpay_enable_plugin_callback', 'swiffpay-order-status', 'swiffpay_order_completed_general_section');
    add_settings_field('swiffpay_new_status_completed', 'New Status Completed', 'swiffpay_new_status_completed_callback', 'swiffpay-order-status', 'swiffpay_order_completed_general_section');
}
add_action('admin_init', 'swiffpay_order_completed_settings_init');

function swiffpay_enable_plugin_callback() {
    $options = get_option('swiffpay_order_completed_settings');
    $value = isset($options['enable_plugin']) ? $options['enable_plugin'] : 'disable';
    echo '<select name="swiffpay_order_completed_settings[enable_plugin]">
            <option value="enable" ' . selected($value, 'enable', false) . '>Enable</option>
            <option value="disable" ' . selected($value, 'disable', false) . '>Disable</option>
          </select>';
}

// Fungsi callback untuk pengaturan "New Status Completed"
function swiffpay_new_status_completed_callback() {
    $options = get_option('swiffpay_order_completed_settings');
    $value = isset($options['new_status_completed']) ? $options['new_status_completed'] : 'completed';
    $order_statuses = wc_get_order_statuses(); // Mendapatkan status pesanan WooCommerce

    echo '<select name="swiffpay_order_completed_settings[new_status_completed]">';
    foreach ($order_statuses as $status_key => $status_label) {
        echo '<option value="' . esc_attr($status_key) . '" ' . selected($value, $status_key, false) . '>' . esc_html($status_label) . '</option>';
    }
    echo '</select>';
}


function swiffypay_check_woocommerce() {
    if (!class_exists('WooCommerce') || !function_exists('wc_get_order')) {
        add_action('admin_notices', 'swiffypay_woocommerce_missing_notice');
    }
}

add_action('admin_init', 'swiffypay_check_woocommerce');

// Pemberitahuan jika WooCommerce tidak diinstal atau tidak aktif
function swiffypay_woocommerce_missing_notice() {
    echo '<div class="error"><p>Plugin Swiffypay Order Completed memerlukan WooCommerce untuk berfungsi dengan baik. Silakan instal dan aktifkan WooCommerce untuk melanjutkan.</p></div>';
}

function swiffypay_check_swiffypay() {
    if (!class_exists('SwiffyPay')) {
        add_action('admin_notices', 'swiffypay_swiffypay_missing_notice');
    }
}

add_action('admin_init', 'swiffypay_check_swiffypay');

function swiffypay_swiffypay_missing_notice() {
    echo '<div class="error"><p>Swiffypay Order Completed plugin requires Swiffypay plugin to function properly. Please install and activate the Swiffypay plugin to proceed.</p></div>';
}


function swiffypay_auto_complete_by_payment_method($order_id)
{
    if (!$order_id) {
        return;
    }

    $options = get_option('swiffpay_order_completed_settings');
    $plugin_enabled = isset($options['enable_plugin']) ? $options['enable_plugin'] : 'disable';

    $new_status = get_option('swiffypay_plugin_enabled');

    if ($plugin_enabled) {
        $order = wc_get_order($order_id);
        $new_status_completed = isset($options['new_status_completed']) ? $options['new_status_completed'] : 'completed';
        if (in_array($order->get_status(), array('processing', 'on-hold'))) {
            $payment_method = $order->get_payment_method();
            if ($payment_method === 'swiffypay') {
                $order->update_status($new_status_completed);
            }
        }
    }
}

function swiffypay_enable_plugin()
{
    add_action('woocommerce_order_status_changed', 'swiffypay_auto_complete_by_payment_method', 99);
}
?>
