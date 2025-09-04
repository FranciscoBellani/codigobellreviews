<?php
if (!defined('ABSPATH')) exit;

// Registro de triggers personalizados para GamiPress
add_filter('gamipress_activity_triggers', 'cwr_register_custom_gamipress_triggers');
function cwr_register_custom_gamipress_triggers($triggers) {
    $triggers['Custom Woo Reviews'] = array(
        'cwr_publish_review' => __('Publish a Review', 'codigobell-woo-reviews'),
        'cwr_high_rating_review' => __('High Rating Review', 'codigobell-woo-reviews'),
        'cwr_like_review' => __('Like a Review', 'codigobell-woo-reviews'),
        'cwr_purchased_review' => __('Purchased Review', 'codigobell-woo-reviews'),
        'cwr_suggestion_review' => __('Suggestion Review', 'codigobell-woo-reviews'),
    );
    return $triggers;
}

// Guardado de ratings y otros metadatos
add_action('comment_post', 'cwr_save_review_meta', 10, 3);
function cwr_save_review_meta($comment_id, $comment_approved, $commentdata) {
    if (isset($commentdata['comment_type']) && $commentdata['comment_type'] === 'review') {
        $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo\nProcesado\nVariedad\nRegión");
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

        if (isset($_POST['cwr_like_dislike']) && in_array($_POST['cwr_like_dislike'], ['like', 'dislike'])) {
            update_comment_meta($comment_id, 'cwr_like_dislike', sanitize_text_field($_POST['cwr_like_dislike']));
        }

        $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
        $purchase_array = array_map('sanitize_key', array_filter(array_map('trim', explode("\n", $purchase_options))));
        if (isset($_POST['cwr_purchase_intent']) && in_array($_POST['cwr_purchase_intent'], $purchase_array)) {
            update_comment_meta($comment_id, 'cwr_purchase_intent', sanitize_text_field($_POST['cwr_purchase_intent']));
        }
    }
}

// Forzar pendientes
add_filter('wp_insert_comment', 'cwr_force_pending_reviews', 10, 2);
function cwr_force_pending_reviews($comment_id, $commentdata) {
    if (isset($commentdata->comment_type) && $commentdata->comment_type === 'review') {
        $commentdata->comment_approved = 0;
        wp_update_comment((array)$commentdata);
    }
    return $comment_id;
}

// Disparar eventos al aprobar
add_action('comment_unapproved_to_approved', 'cwr_gamipress_award_on_review_approve', 10, 2);
function cwr_gamipress_award_on_review_approve($comment, $commentdata) {
    if ($commentdata['comment_type'] === 'review') {
        $user_id = $comment->user_id;
        if ($user_id > 0) {
            gamipress_trigger_event(array(
                'event' => 'cwr_publish_review',
                'user_id' => $user_id,
                'post_id' => $commentdata['comment_post_ID'],
                'comment_id' => $comment->comment_ID,
            ));

            $general_rating = get_comment_meta($comment->comment_ID, 'rating', true);
            if ($general_rating >= 4) {
                gamipress_trigger_event(array(
                    'event' => 'cwr_high_rating_review',
                    'user_id' => $user_id,
                ));
            }

            $like_dislike = get_comment_meta($comment->comment_ID, 'cwr_like_dislike', true);
            if ($like_dislike === 'like') {
                gamipress_trigger_event(array(
                    'event' => 'cwr_like_review',
                    'user_id' => $user_id,
                ));
            }

            $purchase_intent = get_comment_meta($comment->comment_ID, 'cwr_purchase_intent', true);
            if ($purchase_intent === 'locompre') {
                gamipress_trigger_event(array(
                    'event' => 'cwr_purchased_review',
                    'user_id' => $user_id,
                ));
            }

            if (get_comment_meta($comment->comment_ID, 'cwr_feature_suggestion', true)) {
                gamipress_trigger_event(array(
                    'event' => 'cwr_suggestion_review',
                    'user_id' => $user_id,
                ));
            }
        }
    }
}