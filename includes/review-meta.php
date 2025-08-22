<?php
if (!defined('ABSPATH')) exit;

add_action('comment_post', 'cwr_save_review_meta', 10, 3);
function cwr_save_review_meta($comment_id, $comment_approved, $commentdata) {
    if (isset($commentdata['comment_type']) && $commentdata['comment_type'] === 'review') {
        $ratings = get_option('cwr_specific_ratings', "Calidad\nDiseño\nValor");
        $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));
        foreach ($ratings_array as $rating) {
            $rating_key = sanitize_key($rating);
            if (isset($_POST['cwr_rating_' . $rating_key]) && is_numeric($_POST['cwr_rating_' . $rating_key])) {
                $rating_value = intval($_POST['cwr_rating_' . $rating_key]);
                if ($rating_value >= 1 && $rating_value <= 5) {
                    update_comment_meta($comment_id, 'cwr_rating_' . $rating_key, $rating_value);
                }
            }
        }
        if (isset($_POST['cwr_feature_suggestion']) && !empty($_POST['cwr_feature_suggestion'])) {
            $feature_suggestion = sanitize_textarea_field($_POST['cwr_feature_suggestion']);
            update_comment_meta($comment_id, 'cwr_feature_suggestion', $feature_suggestion);
        }
    }
}