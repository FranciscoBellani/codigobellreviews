<?php
if (!defined('ABSPATH')) exit;

add_action('comment_post', 'cwr_save_review_meta', 10, 3);
function cwr_save_review_meta($comment_id, $comment_approved, $commentdata) {
    if (isset($commentdata['comment_type']) && $commentdata['comment_type'] === 'review') {
        $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo");
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

        // Guardar Like/Dislike
        if (isset($_POST['cwr_like_dislike']) && in_array($_POST['cwr_like_dislike'], ['like', 'dislike'])) {
            update_comment_meta($comment_id, 'cwr_like_dislike', sanitize_text_field($_POST['cwr_like_dislike']));
        }

        // Guardar Purchase Intent
        $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
        $purchase_array = array_map('sanitize_key', array_filter(array_map('trim', explode("\n", $purchase_options))));
        if (isset($_POST['cwr_purchase_intent']) && in_array($_POST['cwr_purchase_intent'], $purchase_array)) {
            update_comment_meta($comment_id, 'cwr_purchase_intent', sanitize_text_field($_POST['cwr_purchase_intent']));
        }
    }
}

// Forzar que las reseñas se guarden como pendientes
add_filter('wp_insert_comment', 'cwr_force_pending_reviews', 10, 2);
function cwr_force_pending_reviews($comment_id, $commentdata) {
    if (isset($commentdata->comment_type) && $commentdata->comment_type === 'review') {
        $commentdata->comment_approved = 0; // 0 = Pendiente
        wp_update_comment((array) $commentdata);
    }
    return $comment_id;
}

// Integración con GamiPress
if (function_exists('gamipress_award_points_to_user')) {
    add_action('comment_unapproved_to_approved', 'cwr_gamipress_award_on_review_approve', 10, 2);
    function cwr_gamipress_award_on_review_approve($comment, $commentdata) {
        if ($commentdata['comment_type'] === 'review') {
            $user_id = $comment->user_id;
            if ($user_id > 0) { // Solo usuarios logueados
                $points = 0;

                // Puntos por valoración general de WooCommerce
                $general_rating = get_comment_meta($comment->comment_ID, 'rating', true);
                if ($general_rating && is_numeric($general_rating)) {
                    $points += 5; // 5 puntos base por valoración general
                    if ($general_rating >= 4) {
                        $points += 5; // Bono de 5 puntos por 4-5 estrellas (total 10)
                    }
                }

                // Bono por comentario significativo
                if (strlen($commentdata['comment_content']) > 20) {
                    $points += 5;
                }
                if (strlen($commentdata['comment_content']) > 100) {
                    $points += 5; // Extra por comentario largo
                }

                // Bono por características del café
                $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo");
                $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));
                $completed_ratings = 0;
                foreach ($ratings_array as $rating) {
                    $rating_key = sanitize_key($rating);
                    $rating_value = get_comment_meta($comment->comment_ID, 'cwr_rating_' . $rating_key, true);
                    if ($rating_value) {
                        $completed_ratings++;
                        $points += 3; // 3 puntos por cada característica calificada
                        if ($rating_value >= 4) {
                            $points += 2; // Bono por rating alto por característica
                        }
                    }
                }
                if ($completed_ratings === count($ratings_array)) {
                    $points += 10; // Bono de 10 puntos por completar todas las características
                }

                // Bono por Me Gusta/No Me Gusta
                $like_dislike = get_comment_meta($comment->comment_ID, 'cwr_like_dislike', true);
                if ($like_dislike === 'like') {
                    $points += 3; // 3 puntos por "Me Gusta"
                } elseif ($like_dislike === 'dislike') {
                    $points += 1; // 1 punto por "No Me Gusta" (ajustado para consistencia)
                }

                // Bono por intención de compra
                $purchase_intent = get_comment_meta($comment->comment_ID, 'cwr_purchase_intent', true);
                if ($purchase_intent === 'locompre') {
                    $points += 10; // 10 puntos por "Lo compré"
                } elseif ($purchase_intent === 'lovoyacomprar') {
                    $points += 5; // 5 puntos por "Lo voy a comprar"
                }

                // Bono por sugerencia
                if (get_comment_meta($comment->comment_ID, 'cwr_feature_suggestion', true)) {
                    $points += 10; // 10 puntos por sugerencia
                }

                // Otorgar puntos
                if ($points > 0) {
                    gamipress_award_points_to_user($user_id, $points, 'cwr-review-points');
                }

                // Disparar eventos para badges
                if (function_exists('gamipress_trigger_event')) {
                    gamipress_trigger_event(array(
                        'event' => 'cwr_publish_review',
                        'user_id' => $user_id,
                        'post_id' => $commentdata['comment_post_ID'],
                        'comment_id' => $comment->comment_ID
                    ));

                    if ($general_rating >= 4) {
                        gamipress_trigger_event(array(
                            'event' => 'cwr_high_rating_review',
                            'user_id' => $user_id
                        ));
                    }
                    if ($like_dislike === 'like') {
                        gamipress_trigger_event(array(
                            'event' => 'cwr_like_review',
                            'user_id' => $user_id
                        ));
                    }
                    if ($purchase_intent === 'locompre') {
                        gamipress_trigger_event(array(
                            'event' => 'cwr_purchased_review',
                            'user_id' => $user_id
                        ));
                    }
                    if (get_comment_meta($comment->comment_ID, 'cwr_feature_suggestion', true)) {
                        gamipress_trigger_event(array(
                            'event' => 'cwr_suggestion_review',
                            'user_id' => $user_id
                        ));
                    }
                }
            }
        }
    }
}

// Registrar eventos personalizados en GamiPress
if (function_exists('gamipress_register_event')) {
    gamipress_register_event(array(
        'id' => 'cwr_publish_review',
        'label' => __('Publish a Review', 'codigobell-woo-reviews'),
        'description' => __('Awarded when a user publishes a product review.', 'codigobell-woo-reviews'),
        'trigger_type' => 'specific'
    ));
    gamipress_register_event(array(
        'id' => 'cwr_high_rating_review',
        'label' => __('High Rating Review', 'codigobell-woo-reviews'),
        'description' => __('Awarded for a review with a general rating of 4 or 5 stars.', 'codigobell-woo-reviews'),
        'trigger_type' => 'specific'
    ));
    gamipress_register_event(array(
        'id' => 'cwr_like_review',
        'label' => __('Like a Review', 'codigobell-woo-reviews'),
        'description' => __('Awarded when a user selects "Like" in a review.', 'codigobell-woo-reviews'),
        'trigger_type' => 'specific'
    ));
    gamipress_register_event(array(
        'id' => 'cwr_purchased_review',
        'label' => __('Purchased Review', 'codigobell-woo-reviews'),
        'description' => __('Awarded when a user selects "I bought it" in a review.', 'codigobell-woo-reviews'),
        'trigger_type' => 'specific'
    ));
    gamipress_register_event(array(
        'id' => 'cwr_suggestion_review',
        'label' => __('Suggestion Review', 'codigobell-woo-reviews'),
        'description' => __('Awarded when a user submits a feature suggestion.', 'codigobell-woo-reviews'),
        'trigger_type' => 'specific'
    ));
}