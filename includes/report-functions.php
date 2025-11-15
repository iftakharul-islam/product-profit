<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Save Buy Price to Order Meta
 */
add_action('woocommerce_checkout_create_order_line_item', 'product_profit_reporter_save_buy_price_to_order_item', 10, 4);
function product_profit_reporter_save_buy_price_to_order_item($item, $cart_item_key, $values, $order) {
    $product_id = $values['product_id'];
    $buy_price = get_post_meta($product_id, '_product_profit_reporter_buy_price', true);

    // Save the buy price in order meta
    $item->add_meta_data('_product_profit_reporter_buy_price', $buy_price ?: 0, true);
}

/**
 * Render the Benefit Reports Page
 */

function product_profit_reporter_render_reports_page() {
    // Default values
    $default_end_date = wp_date('Y-m-d');
    $default_start_date = wp_date('Y-m-d', strtotime('-30 days'));
    $default_category_id = 'all';
    $default_sort_by = 'name';
    $default_order = 'asc';

    // Initialize with defaults
    $end_date = $default_end_date;
    $start_date = $default_start_date;
    $category_id = $default_category_id;
    $sort_by = $default_sort_by;
    $order = $default_order;

    // Check nonce if form was submitted
    if (!empty($_GET['product_profit_reporter_nonce_field']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['product_profit_reporter_nonce_field'])), 'product_profit_reporter_nonce')) {
        $end_date = !empty($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : $default_end_date;
        $start_date = !empty($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : $default_start_date;
        $category_id = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : $default_category_id;
        $sort_by = isset($_GET['sort_by']) ? sanitize_text_field(wp_unslash($_GET['sort_by'])) : $default_sort_by;
        $order = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : $default_order;
    }

    // Get the current page from the URL (default is page 1).
    $paged    = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $per_page = 10; // Number of products to display per page

    $benefit_data     = product_profit_reporter_calculate_report($start_date, $end_date, $category_id);
    $product_benefits = product_profit_reporter_calculate_product_report($start_date, $end_date, $category_id, $sort_by, $order);

?>
    <div class="wrap">
        <h1><?php esc_html_e('Profit Loss Report', 'product-profit-reporter'); ?></h1>
        <!-- Rate this plugin -->
        <div class="rate-plugin">
            <a href="https://wordpress.org/plugins/product-profit-reporter/" target="_blank"><?php esc_html_e('Rate this plugin', 'product-profit-reporter'); ?></a>
        </div>
        <!-- Filter Form -->
        <form method="get" action="" style="margin: 10px 0px;">
            <input type="hidden" name="page" value="product-profit-reporter-reports">
            <?php wp_nonce_field('product_profit_reporter_nonce', 'product_profit_reporter_nonce_field'); ?>
            <label for="start_date"><?php esc_html_e('Start Date', 'product-profit-reporter'); ?></label>
            <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" required>
            <label for="end_date"><?php esc_html_e('End Date', 'product-profit-reporter'); ?></label>
            <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>" required>
            <select name="category">
                <option value="all"><?php esc_html_e('All Categories', 'product-profit-reporter'); ?></option>
                <?php
                $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->term_id) . '" ' . selected($category_id, $category->term_id, false) . '>' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>
            <label for="sort_by"><?php esc_html_e('Sort By', 'product-profit-reporter'); ?></label>
            <select name="sort_by">
                <option <?php echo $sort_by == 'name' ? 'selected' : ''; ?> value="name"><?php esc_html_e('Product Name', 'product-profit-reporter'); ?></option>
                <option <?php echo $sort_by == 'sales' ? 'selected' : ''; ?> value="sales"><?php esc_html_e('Total Sales', 'product-profit-reporter'); ?></option>
                <option <?php echo $sort_by == 'buy_price' ? 'selected' : ''; ?> value="buy_price"><?php esc_html_e('Total Buy Price', 'product-profit-reporter'); ?></option>
                <option <?php echo $sort_by == 'name' ? 'selected' : ''; ?> value="benefit"><?php esc_html_e('Profit', 'product-profit-reporter'); ?></option>
                <option <?php echo $sort_by == 'quantity' ? 'selected' : ''; ?> value="quantity"><?php esc_html_e('Quantity Sold', 'product-profit-reporter'); ?></option>
            </select>
            <label for="order"><?php esc_html_e('Order', 'product-profit-reporter'); ?></label>
            <select name="order">
                <option value="asc"><?php esc_html_e('Ascending', 'product-profit-reporter'); ?></option>
                <option value="desc"><?php esc_html_e('Descending', 'product-profit-reporter'); ?></option>
            </select>
            <button type="submit" class="button button-primary"><?php esc_html_e('Generate Report', 'product-profit-reporter'); ?></button>
            <button type="submit" name="profit_report_export_csv" value="summary" class="button"><?php esc_html_e('Export Summary CSV', 'product-profit-reporter'); ?></button>
            <button type="submit" name="profit_report_export_csv" value="detailed" class="button"><?php esc_html_e('Export Detailed CSV', 'product-profit-reporter'); ?></button>
        </form>

        <?php


        // Display overall totals
        echo '<div class="postbox">
        
            <h2 class="hndle" style="margin: 18px 9px -5px 13px;">' .
            // translators: %s is the start date, %s is the end date
            sprintf(esc_html__('Report from %1$s to %2$s', 'product-profit-reporter'), esc_html($start_date), esc_html($end_date)) . '</h2>
            <div class="inside">
                <p><strong>' . esc_html__('Total Sales: ', 'product-profit-reporter') . '</strong>' . wp_kses_post(wc_price($benefit_data['total_sales'])) . ' | 
                <strong>' . esc_html__('Total Buy Price: ', 'product-profit-reporter') . '</strong>' . wp_kses_post(wc_price($benefit_data['total_buy_price'])) . ' | 
                <strong>' . esc_html__('Total Benefit: ', 'product-profit-reporter') . '</strong>' . wp_kses_post(wc_price($benefit_data['total_benefit'])) . '</p>
            </div>
        </div>';

        // Pagination calculation
        $total_items = count($product_benefits);
        $total_pages = ceil($total_items / $per_page);

        // Slice array for current page
        $offset = ($paged - 1) * $per_page;
        $product_benefits_page = array_slice($product_benefits, $offset, $per_page, true);

        echo '<h3>' . esc_html__('Per-Product Benefit Report', 'product-profit-reporter') . '</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>
            <tr>
                <th>' . esc_html__('Product Image', 'product-profit-reporter') . '</th>
                <th>' . esc_html__('Product Name', 'product-profit-reporter') . '</th>
                <th>' . esc_html__('Quantity Sold', 'product-profit-reporter') . '</th>
                <th>' . esc_html__('Total Sales', 'product-profit-reporter') . '</th>
                <th>' . esc_html__('Total Buy Price', 'product-profit-reporter') . '</th>
                <th>' . esc_html__('Profit', 'product-profit-reporter') . '</th>
                <th>' . esc_html__('Edit', 'product-profit-reporter') . '</th>
            </tr>
        </thead>';
        echo '<tbody>';

        // Loop through the sliced array for current page only
        foreach ($product_benefits_page as $product_id => $product_data) {
            echo '<tr>
                <td>' . (has_post_thumbnail($product_id) ? get_the_post_thumbnail($product_id, [48, 48]) : esc_html__('No Image', 'product-profit-reporter')) . '</td>
                <td><a href="' . esc_url(get_permalink($product_id)) . '">' . esc_html($product_data['name']) . '</a></td>
                <td>' . esc_html($product_data['quantity']) . '</td>
                <td>' . wp_kses_post(wc_price($product_data['sales'])) . '</td>
                <td>' . wp_kses_post(wc_price($product_data['buy_price'])) . '</td>
                <td>' . wp_kses_post(wc_price($product_data['benefit'])) . '</td>
                <td><a href="' . esc_url(get_edit_post_link($product_id)) . '">' . esc_html__('Edit', 'product-profit-reporter') . '</a></td>
            </tr>';
        }
        echo '</tbody>';
        echo '</table>';

        // Display pagination links if there is more than one page.
        if ($total_pages > 1) {
            // Build pagination base
            $page_link_base = esc_url_raw(add_query_arg([
                'page'       => 'product-profit-reporter-reports',
                'start_date' => $start_date,
                'end_date'   => $end_date,
                'paged'      => '%#%',
            ]));

            echo '<div class="tablenav">';
            echo wp_kses_post(paginate_links(array(
                'base'      => $page_link_base,
                'format'    => '',
                'current'   => $paged,
                'total'     => $total_pages,
                'prev_text' => __('« Prev', 'product-profit-reporter'),
                'next_text' => __('Next »', 'product-profit-reporter'),
            )));
            echo '</div>';
        }
        ?>
    </div>
<?php
}

/**
 * Calculate Overall Benefit Report
 */
function product_profit_reporter_calculate_report($start_date, $end_date, $category_id = 'all') {
    $orders = wc_get_orders([
        'date_created' => $start_date . '...' . $end_date,
        'status' => 'completed',
    ]);

    $total_sales = 0;
    $total_buy_price = 0;

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {

            // Filter by category if specified
            if ($category_id !== 'all') {
                $product_categories = wp_get_post_terms($item->get_product_id(), 'product_cat', ['fields' => 'ids']);
                if (!in_array($category_id, $product_categories)) {
                    continue; // Skip products not in the selected category
                }
            }

            $buy_price = $item->get_meta('_product_profit_reporter_buy_price', true);
            if (!$buy_price) {
                continue;
            }

            $quantity = $item->get_quantity();
            $item_total = $item->get_total();

            $total_sales += $item_total;
            $total_buy_price += $buy_price * $quantity;
        }
    }

    return [
        'total_sales' => $total_sales,
        'total_buy_price' => $total_buy_price,
        'total_benefit' => $total_sales - $total_buy_price,
    ];
}

/**
 * Calculate Product-Wise Benefit Report
 */
function product_profit_reporter_calculate_product_report($start_date, $end_date, $category_id = 'all', $sort_by = 'name', $order = 'asc') {
    $orders = wc_get_orders([
        'date_created' => $start_date . '...' . $end_date,
        'status'       => 'completed',
    ]);

    $product_benefits = [];

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product_name = $item->get_name();
            $buy_price = $item->get_meta('_product_profit_reporter_buy_price', true);
            $quantity = $item->get_quantity();
            $item_total = $item->get_total();

            // Filter by category if specified
            if ($category_id !== 'all') {
                $product_categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
                if (!in_array($category_id, $product_categories)) {
                    continue; // Skip products not in the selected category
                }
            }

            if (!isset($product_benefits[$product_id])) {
                $product_benefits[$product_id] = [
                    'name'      => $product_name,
                    'sales'     => 0,
                    'buy_price' => 0,
                    'benefit'   => 0,
                    'quantity'  => 0,
                ];
            }

            $product_benefits[$product_id]['sales'] += $item_total;
            $product_benefits[$product_id]['buy_price'] += ($buy_price ? $buy_price : 0) * $quantity;
            $product_benefits[$product_id]['benefit'] += ($buy_price ? $item_total - ($buy_price * $quantity) : 0);
            $product_benefits[$product_id]['quantity'] += $quantity;
        }
    }


    // Sort the results while preserving keys
    uasort($product_benefits, function ($a, $b) use ($sort_by, $order) {
        if ($a[$sort_by] == $b[$sort_by]) {
            return 0;
        }
        return ($order === 'asc' ? 1 : -1) * ($a[$sort_by] <=> $b[$sort_by]);
    });


    return $product_benefits;
}


/**
 * Export CSV Reports
 */
if (!empty($_GET['profit_report_export_csv']) && !empty($_GET['product_profit_reporter_nonce_field']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['product_profit_reporter_nonce_field'])), 'product_profit_reporter_nonce')) {
    $export_type = sanitize_text_field(wp_unslash($_GET['profit_report_export_csv']));
    $category_id = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : 'all';

    $start_date  = !empty($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : wp_date('Y-m-d', strtotime('-30 days'));
    $end_date    = !empty($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : wp_date('Y-m-d');

    // Set file name based on export type
    $file_name = $export_type === 'summary' ? 'benefit-summary-report.csv' : 'benefit-detailed-report.csv';

    // Build CSV content
    $csv_content = '';

    if ($export_type === 'summary') {
        // Export Summary Report
        $csv_content .= "Date Range,Total Sales,Total Buy Price,Total Benefit\n";

        $benefit_data = product_profit_reporter_calculate_report($start_date, $end_date, $category_id);
        $csv_content .= sprintf(
            '"%s to %s",%s,%s,%s',
            $start_date,
            $end_date,
            $benefit_data['total_sales'],
            $benefit_data['total_buy_price'],
            $benefit_data['total_benefit']
        );
    } elseif ($export_type === 'detailed') {
        // Export Detailed Report
        $csv_content .= "Product ID,Product Name,Quantity Sold,Total Sales,Total Buy Price,Profit\n";

        $product_benefits = product_profit_reporter_calculate_product_report($start_date, $end_date, $category_id);
        foreach ($product_benefits as $product_id => $product_data) {
            $csv_content .= sprintf(
                '"%s","%s",%s,%s,%s,%s' . "\n",
                $product_id,
                $product_data['name'],
                $product_data['quantity'],
                $product_data['sales'],
                $product_data['buy_price'],
                $product_data['benefit']
            );
        }
    }

    // Send headers and output
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"$file_name\"");
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $csv_content;
    exit;
}
