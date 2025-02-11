<?php
/**
 * Plugin Name: WooCommerce Benefit Calculator
 * Description: Calculates product benefits, monthly profits, and provides detailed financial reports for WooCommerce stores.
 * Version: 1.0
 * Author: ifatwp
 * Author URI: https://ifatwp.com
 * Text Domain: profit-loss-report
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
            __('Profit/Loss Report', 'profit-loss-report'),
            __('Profit/Loss Report', 'profit-loss-report'),
            'manage_woocommerce',
            'benefit-reports',
            'render_benefit_reports_page',
        );
    }

    public function add_buy_price_field() {
        woocommerce_wp_text_input([
            'id' => '_buy_price',
            'label' => __('Buy Price', 'profit-loss-report'),
            'desc_tip' => true,
            'description' => __('Enter the cost price of the product.', 'profit-loss-report'),
        ]);
    }

    public function save_buy_price_field($post_id) {
        $buy_price = isset($_POST['_buy_price']) ? wc_clean($_POST['_buy_price']) : '';
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
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-7 days')); // Change the range for weekly/monthly

    $benefit_data = calculate_benefit_report($start_date, $end_date);
    $email_body = sprintf(
        __("Here is your profit summary for the period %s to %s:\n\nTotal Sales: %s\nTotal Buy Price: %s\nTotal Profit: %s", 'profit-loss-report'),
        $start_date,
        $end_date,
        wc_price($benefit_data['total_sales']),
        wc_price($benefit_data['total_buy_price']),
        wc_price($benefit_data['total_benefit'])
    );
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail(
        get_option('admin_email'),
        __('Weekly Profit Summary', 'profit-loss-report'),
        $email_body,
        $headers
    );
}
