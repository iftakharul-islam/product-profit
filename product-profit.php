<?php
/**
 * Plugin Name: Product Profit Reporter
 * Description: Calculates product benefits, monthly profits, and provides detailed financial reports for WooCommerce stores.
 * Version: 1.0.0
 * Author: ifatwp
 * Author URI:https://profiles.wordpress.org/ifatwp/
 * Text Domain: product-profit
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WooCommerce_Benefit_Calculator {

    public function __construct() {
        // Plugin initialization
        add_action('init', [$this, 'initialize_plugin']);
        add_action('admin_menu', [$this, 'register_reports_submenu']);
        add_action('woocommerce_product_options_pricing', [$this, 'add_buy_price_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_buy_price_field']);
        add_action('woocommerce_checkout_create_order_line_item', 'save_buy_price_to_order_item', 10, 4);
    }
    function save_buy_price_to_order_item($item, $cart_item_key, $values, $order) {
        $product_id = $values['product_id'];
        $buy_price = get_post_meta($product_id, '_buy_price', true);
    
        // Save the buy price to the order item meta
        $item->add_meta_data('_buy_price', $buy_price ? $buy_price : 0, true);
    }


    public function initialize_plugin() {
        // Initialize core functionality
        require_once plugin_dir_path(__FILE__) . 'includes/report-functions.php';
    }

    public function register_reports_submenu() {
        add_submenu_page(
            'woocommerce',
            __('Profit/Loss Report', 'product-profit'),
            __('Profit/Loss Report', 'product-profit'),
            'manage_woocommerce',
            'benefit-reports',
            'render_benefit_reports_page',
        );
    }

    public function add_buy_price_field() {
        woocommerce_wp_text_input([
            'id' => '_buy_price',
            'label' => __('Buy Price', 'product-profit'),
            'desc_tip' => true,
            'description' => __('Enter the cost price of the product.', 'product-profit'),
        ]);
    }

    public function save_buy_price_field($post_id) {
        // Verify nonce for security
        if (!isset($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $buy_price = isset($_POST['_buy_price']) ? sanitize_text_field(wp_unslash($_POST['_buy_price'])) : '';
        update_post_meta($post_id, '_buy_price', $buy_price);
    }
}

new WooCommerce_Benefit_Calculator();


add_action('wp', 'schedule_profit_summary_email');
function schedule_profit_summary_email() {
    if (!wp_next_scheduled('send_profit_summary_email')) {
        wp_schedule_event(time(), 'hourly', 'send_profit_summary_email'); // Change 'daily' to 'weekly' or 'monthly' as needed
    }
}



add_action('send_profit_summary_email', 'send_profit_summary_email');
function send_profit_summary_email() {
    $end_date = wp_date('Y-m-d');
    $start_date = wp_date('Y-m-d', strtotime('-7 days')); // Change the range for weekly/monthly

    $benefit_data = calculate_benefit_report($start_date, $end_date);
    $email_body = sprintf(
        // translators: %s is the start date, %s is the end date, %s is total sales, %s is total buy price, %s is total profit
        __('Here is your profit summary for the period %1$s to %2$s:\n\nTotal Sales: %3$s\nTotal Buy Price: %4$s\nTotal Profit: %5$s', 'product-profit'),
        $start_date,
        $end_date,
        wc_price($benefit_data['total_sales']),
        wc_price($benefit_data['total_buy_price']),
        wc_price($benefit_data['total_benefit'])
    );
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail(
        get_option('admin_email'),
        __('Weekly Profit Summary', 'product-profit'),
        $email_body,
        $headers
    );
}
