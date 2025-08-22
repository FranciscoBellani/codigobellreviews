<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_review_after_comment_text', 'cwr_display_review_meta', 10, 1);
function cwr_display_review_meta($comment) {
    $comment_id = $comment->comment_ID;
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    echo '<div class="cwr-review-meta" style="margin: 10px 0;">';
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $rating_value = get_comment_meta($comment_id, 'cwr_rating_' . $rating_key, true);
        if ($rating_value && is_numeric($rating_value)) {
            $percentage = ($rating_value / 5) * 100;
            echo '<p><strong>' . esc_html($rating) . ':</strong> ';
            echo '<span class="star-rating" title="' . esc_attr($rating_value) . ' out of 5" style="font-size: 1.5em; width: 6em; height: 1.5em;">';
            echo '<span style="width:' . esc_attr($percentage) . '%;"></span>';
            echo '</span></p>';
        }
    }
    $feature_suggestion = get_comment_meta($comment_id, 'cwr_feature_suggestion', true);
    if ($feature_suggestion) {
        echo '<p><strong>' . __('Sugerencia de característica', 'codigobell-woo-reviews') . ':</strong> ' . esc_html($feature_suggestion) . '</p>';
    }
    echo '</div>';

    echo '
        <style>
            .cwr-review-meta .star-rating {
                display: inline-block;
                font-size: 1.5em; /* Aumenta el tamaño */
                height: 1.5em;
                line-height: 1.5;
                overflow: hidden;
                position: relative;
                width: 6em; /* Ajusta el ancho */
                margin: 0 0.2em;
                vertical-align: middle;
            }
            .cwr-review-meta .star-rating::before {
                content: "\f155\f155\f155\f155\f155";
                font-family: dashicons;
                letter-spacing: 0.13em;
                color: #d3ced2;
            }
            .cwr-review-meta .star-rating span {
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                color: #e6a600;
                overflow: hidden;
                white-space: nowrap;
            }
            .cwr-review-meta .star-rating span::before {
                content: "\f155\f155\f155\f155\f155";
                font-family: dashicons;
                letter-spacing: 0.13em;
            }
        </style>';
}