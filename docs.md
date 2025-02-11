# WooCommerce Benefit Report Plugin

## Overview
The **WooCommerce Benefit Report Plugin** is a comprehensive tool for WooCommerce store owners to analyze sales performance, profit margins, and identify trends. The plugin provides advanced features like per-product reporting, category-based filtering, color-coded insights, real-time alerts, and email summaries.

---

## Features

1. **Benefit Reports:**
   - View profit, sales, and buy price reports for selected date ranges.
   - Per-product reporting with details like quantity sold, sales, buy price, and profit.

2. **Category-Based Filtering:**
   - Filter reports by product category to focus on specific product groups.

3. **Color-Coded Insights:**
   - Green for profitable products.
   - Red for unprofitable products.
   - Yellow for neutral products.

4. **Real-Time Alerts:**
   - Notify store owners when:
     - Products become unprofitable.
     - Total sales cross a predefined threshold.

5. **Email Summaries:**
   - Send periodic email summaries (daily, weekly, or monthly) with sales and profit data.
   - HTML-formatted emails for better readability.

6. **CSV Export:**
   - Export summary and detailed reports in CSV format.

---

## Installation

1. Download the plugin ZIP file.
2. Go to **Plugins > Add New** in your WordPress admin dashboard.
3. Click **Upload Plugin**, choose the ZIP file, and click **Install Now**.
4. Activate the plugin.
5. Navigate to **WooCommerce > Benefit Reports** to start using the plugin.

---

## Usage Instructions

### 1. Accessing the Reports Page
1. Go to **WooCommerce > Benefit Reports**.
2. Use the date range selector to define the report period.
3. (Optional) Select a category to filter the report.
4. Click **Generate Report** to view the results.

### 2. Viewing the Report
- The report includes:
  - **Summary:** Total sales, buy price, and profit.
  - **Per-Product Details:** Quantity sold, sales, buy price, and profit.
  - **Color-Coded Rows:** Highlight product performance visually.

### 3. Exporting Reports
- Use the **Export Summary CSV** and **Export Detailed CSV** buttons to download the data.

### 4. Email Summaries
- The plugin automatically schedules email summaries based on your settings.

### 5. Real-Time Alerts
- Alerts are triggered automatically and sent to the admin email when:
  - A product becomes unprofitable.
  - Total sales in an order exceed the predefined threshold.

---

## Advanced Features

### 1. Sorting
- Sort per-product reports by columns like name, sales, buy price, or profit.
- Supports ascending and descending order.

### 2. Real-Time Alerts Configuration
- Alerts are triggered by:
  - Profit below $0 for any product.
  - Order total exceeding the threshold (default: $10,000).
- Modify thresholds by editing the plugin’s settings or code.

### 3. Email Customization
- Emails are sent in HTML format.
- Includes total sales, buy price, and profit for the selected period.

---

## Development Notes

### Key Hooks and Functions

1. **Saving Buy Price to Orders:**
   ```php
   add_action('woocommerce_checkout_create_order_line_item', 'save_buy_price_to_order_item', 10, 4);
   ```

2. **Generating Reports:**
   - `calculate_benefit_report($start_date, $end_date, $category_id = 'all')`
   - `calculate_product_benefit_report($start_date, $end_date, $category_id = 'all')`

3. **Exporting CSV:**
   ```php
   if (!empty($_GET['profit_report_export_csv'])) { ... }
   ```

4. **Real-Time Alerts:**
   ```php
   add_action('woocommerce_thankyou', 'check_real_time_alerts');
   ```

5. **Email Summaries:**
   ```php
   add_action('send_profit_summary_email', 'send_profit_summary_email');
   ```

### Custom Database Table (Optional)
To log email status and alerts, you can create a custom table:
```php
CREATE TABLE wp_email_log (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    email_subject VARCHAR(255) NOT NULL,
    email_recipient VARCHAR(255) NOT NULL,
    email_status VARCHAR(50) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

---

## FAQs

### Q1: Can I customize the email frequency?
Yes, you can adjust the frequency (daily, weekly, monthly) by modifying the cron schedule:
```php
wp_schedule_event(time(), 'daily', 'send_profit_summary_email');
```

### Q2: How do I change the profit threshold for alerts?
Update the threshold in the `check_real_time_alerts` function:
```php
$alert_threshold = 10000; // Change to your desired value
```

### Q3: What happens if a product has no buy price?
- The profit for such products is considered as 0.
- These products are highlighted in the report for attention.

---

## Support
For support or feature requests, contact us at **support@example.com** or visit our [GitHub Repository](https://github.com/example-repo/woocommerce-benefit-report).

---

## Changelog

### Version 1.0.0
- Initial release with basic reporting, sorting, alerts, and email summaries.

### Version 1.1.0
- Added category filtering and color-coded insights.
- Enhanced email formatting with HTML support.

### Version 1.2.0
- Introduced CSV export for summary and detailed reports.
- Added sorting functionality for per-product reports.

---

## License
This plugin is licensed under the [GPLv3 License](https://www.gnu.org/licenses/gpl-3.0.html).

