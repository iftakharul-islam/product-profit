<?php
/**
 * Plugin Name: Product Profit Reporter
 * Description: Calculates product benefits, monthly profits, and provides detailed financial reports for WooCommerce stores.
 * Version: 1.0.0
 * Author: ifatwp
 * Author URI:https://profiles.wordpress.org/ifatwp/
 * Text Domain: product-profit-reporter
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Product_Profit_Reporter_Calculator {

    public function __construct() {
        // Plugin initialization
        add_action('init', [$this, 'initialize_plugin']);
        add_action('admin_menu', [$this, 'register_reports_submenu']);
        add_action('woocommerce_product_options_pricing', [$this, 'add_buy_price_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_buy_price_field']);
        add_action('woocommerce_checkout_create_order_line_item', 'product_profit_reporter_save_buy_price_to_order_item', 10, 4);

        // Quick Edit functionality
        add_action('woocommerce_product_quick_edit_end', [$this, 'add_quick_edit_buy_price_field']);
        add_action('woocommerce_product_bulk_edit_end', [$this, 'add_bulk_edit_buy_price_field']);
        add_action('manage_product_posts_custom_column', [$this, 'add_buy_price_column_value'], 10, 2);
        add_action('woocommerce_product_quick_edit_save', [$this, 'save_quick_edit_buy_price']);
        add_action('woocommerce_product_bulk_edit_save', [$this, 'save_bulk_edit_buy_price']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_quick_edit_scripts']);
    }

    public function save_buy_price_to_order_item($item, $cart_item_key, $values, $order) {
        $product_id = $values['product_id'];
        $buy_price = get_post_meta($product_id, '_product_profit_reporter_buy_price', true);

        // Save the buy price to the order item meta
        $item->add_meta_data('_product_profit_reporter_buy_price', $buy_price ?: 0, true);
    }


    public function initialize_plugin() {
        // Initialize core functionality
        require_once plugin_dir_path(__FILE__) . 'includes/report-functions.php';
    }

    public function register_reports_submenu() {
        add_submenu_page(
            'woocommerce',
            __('Profit/Loss Report', 'product-profit-reporter'),
            __('Profit/Loss Report', 'product-profit-reporter'),
            'manage_woocommerce',
            'product-profit-reporter-reports',
            'product_profit_reporter_render_reports_page',
        );
    }

    public function add_buy_price_field() {
        woocommerce_wp_text_input([
            'id' => '_product_profit_reporter_buy_price',
            'label' => __('Buy Price', 'product-profit-reporter'),
            'desc_tip' => true,
            'description' => __('Enter the cost price of the product.', 'product-profit-reporter'),
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

        $buy_price = isset($_POST['_product_profit_reporter_buy_price']) ? sanitize_text_field(wp_unslash($_POST['_product_profit_reporter_buy_price'])) : '';
        update_post_meta($post_id, '_product_profit_reporter_buy_price', $buy_price);
    }

    /**
     * Add Buy Price field to Quick Edit
     */
    public function add_quick_edit_buy_price_field() {
        ?>
        <br class="clear" />
        <label class="alignleft">
            <span class="title"><?php esc_html_e('Buy Price', 'product-profit-reporter'); ?></span>
            <span class="input-text-wrap">
                <input type="text" name="_product_profit_reporter_buy_price" class="text buy_price" placeholder="<?php esc_attr_e('Buy price', 'product-profit-reporter'); ?>" value="">
            </span>
        </label>
        <?php
    }

    /**
     * Add Buy Price field to Bulk Edit
     */
    public function add_bulk_edit_buy_price_field() {
        ?>
        <label class="alignleft">
            <span class="title"><?php esc_html_e('Buy Price', 'product-profit-reporter'); ?></span>
            <span class="input-text-wrap">
                <input type="text" name="_product_profit_reporter_buy_price" class="text buy_price" placeholder="<?php esc_attr_e('Leave blank to keep current', 'product-profit-reporter'); ?>" value="">
            </span>
        </label>
        <?php
    }

    /**
     * Add hidden buy price value to product column for JavaScript to use
     */
    public function add_buy_price_column_value($column, $post_id) {
        if ($column === 'name') {
            $buy_price = get_post_meta($post_id, '_product_profit_reporter_buy_price', true);
            ?>
            <div class="hidden" data-buy_price="<?php echo esc_attr($buy_price); ?>"></div>
            <?php
        }
    }

    /**
     * Save Buy Price from Quick Edit
     */
    public function save_quick_edit_buy_price($product) {
        // Verify nonce for security
        if (!isset($_REQUEST['woocommerce_quick_edit_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['woocommerce_quick_edit_nonce'])), 'woocommerce_quick_edit_nonce')) {
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_products')) {
            return;
        }

        $product_id = $product->get_id();

        // Check if buy price is set in POST data
        if (isset($_POST['_product_profit_reporter_buy_price'])) {
            $buy_price = sanitize_text_field(wp_unslash($_POST['_product_profit_reporter_buy_price']));
            update_post_meta($product_id, '_product_profit_reporter_buy_price', $buy_price);
        }
    }

    /**
     * Save Buy Price from Bulk Edit
     */
    public function save_bulk_edit_buy_price($product) {
        // Verify nonce for security
        if (!isset($_REQUEST['woocommerce_bulk_edit_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['woocommerce_bulk_edit_nonce'])), 'woocommerce_bulk_edit_nonce')) {
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_products')) {
            return;
        }

        $product_id = $product->get_id();

        // Only update if value is provided (not empty)
        if (isset($_POST['_product_profit_reporter_buy_price']) && $_POST['_product_profit_reporter_buy_price'] !== '') {
            $buy_price = sanitize_text_field(wp_unslash($_POST['_product_profit_reporter_buy_price']));
            update_post_meta($product_id, '_product_profit_reporter_buy_price', $buy_price);
        }
    }

    /**
     * Enqueue JavaScript for Quick Edit functionality
     */
    public function enqueue_quick_edit_scripts($hook) {
        // Only load on product list page
        global $post_type;
        if ($hook !== 'edit.php' || $post_type !== 'product') {
            return;
        }

        wp_enqueue_script(
            'product-profit-reporter-quick-edit',
            plugin_dir_url(__FILE__) . 'assets/js/quick-edit.js',
            ['jquery', 'inline-edit-post'],
            '1.0.0',
            true
        );
    }
}

new Product_Profit_Reporter_Calculator();


add_action('wp', 'product_profit_reporter_schedule_summary_email');
function product_profit_reporter_schedule_summary_email() {
    if (!wp_next_scheduled('product_profit_reporter_send_summary_email')) {
        wp_schedule_event(time(), 'hourly', 'product_profit_reporter_send_summary_email'); // Change 'daily' to 'weekly' or 'monthly' as needed
    }
}



add_action('product_profit_reporter_send_summary_email', 'product_profit_reporter_send_summary_email');
function product_profit_reporter_send_summary_email() {
    $end_date = wp_date('Y-m-d');
    $start_date = wp_date('Y-m-d', strtotime('-7 days')); // Change the range for weekly/monthly

    $benefit_data = product_profit_reporter_calculate_report($start_date, $end_date);
    $email_body = sprintf(
        // translators: %s is the start date, %s is the end date, %s is total sales, %s is total buy price, %s is total profit
        __('Here is your profit summary for the period %1$s to %2$s:\n\nTotal Sales: %3$s\nTotal Buy Price: %4$s\nTotal Profit: %5$s', 'product-profit-reporter'),
        $start_date,
        $end_date,
        wc_price($benefit_data['total_sales']),
        wc_price($benefit_data['total_buy_price']),
        wc_price($benefit_data['total_benefit'])
    );
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail(
        get_option('admin_email'),
        __('Weekly Profit Summary', 'product-profit-reporter'),
        $email_body,
        $headers
    );
}
