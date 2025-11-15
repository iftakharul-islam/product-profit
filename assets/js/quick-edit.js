/**
 * Product Profit Reporter - Quick Edit JavaScript
 * Handles populating the buy price field in quick edit
 */
(function($) {
    'use strict';

    // We create a copy of the WP inline edit post function
    var $wp_inline_edit = inlineEditPost.edit;

    // Override the inline edit function
    inlineEditPost.edit = function(id) {
        // Call the original WP edit function
        $wp_inline_edit.apply(this, arguments);

        // Get the post ID
        var post_id = 0;
        if (typeof(id) === 'object') {
            post_id = parseInt(this.getId(id));
        }

        if (post_id > 0) {
            // Get the row
            var $post_row = $('#post-' + post_id);

            // Get the buy price value from the hidden data attribute
            var $buy_price_data = $post_row.find('.hidden[data-buy_price]');
            var buy_price = '';

            if ($buy_price_data.length > 0) {
                buy_price = $buy_price_data.attr('data-buy_price') || '';
            }

            // Set the buy price in the quick edit form
            $('.buy_price', '.inline-edit-row').val(buy_price);
        }
    };

})(jQuery);
