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

// Guardado de valoraciones extendidas desde frontend
add_action('comment_post', 'cwr_save_review_meta', 10, 3);
function cwr_save_review_meta($comment_id, $comment_approved, $commentdata) {
    if (!isset($_POST['cwr_nonce']) || !wp_verify_nonce($_POST['cwr_nonce'], 'cwr_save_review_meta')) {
        return;
    }
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

// Forzar que las reseñas vayan a pendientes
add_filter('wp_insert_comment', 'cwr_force_pending_reviews', 10, 2);
function cwr_force_pending_reviews($comment_id, $commentdata) {
    if (isset($commentdata->comment_type) && $commentdata->comment_type === 'review') {
        $commentdata->comment_approved = 0;
        wp_update_comment((array)$commentdata);
    }
    return $comment_id;
}

// Disparar eventos GamiPress al aprobar reseña, CORREGIDO: solo 1 argumento
add_action('comment_unapproved_to_approved', 'cwr_gamipress_award_on_review_approve', 10, 1);
function cwr_gamipress_award_on_review_approve($comment) {
    // $comment puede ser WP_Comment o comment_ID
    if (is_numeric($comment)) {
        $comment = get_comment($comment);
    }
    if (!$comment || $comment->comment_type !== 'review') {
        return;
    }
    $user_id = $comment->user_id;
    if ($user_id > 0) {
        error_log("[GamiPress Debug] Disparando trigger cwr_publish_review para user_id " . $user_id);
        gamipress_trigger_event(array(
            'event' => 'cwr_publish_review',
            'user_id' => $user_id,
            'post_id' => $comment->comment_post_ID,
            'comment_id' => $comment->comment_ID,
        ));
        $general_rating = get_comment_meta($comment->comment_ID, 'rating', true);
        if ($general_rating >= 4) {
            error_log("[GamiPress Debug] Disparando trigger cwr_high_rating_review para user_id " . $user_id);
            gamipress_trigger_event(array(
                'event' => 'cwr_high_rating_review',
                'user_id' => $user_id,
            ));
        }
        $like_dislike = get_comment_meta($comment->comment_ID, 'cwr_like_dislike', true);
        if ($like_dislike === 'like') {
            error_log("[GamiPress Debug] Disparando trigger cwr_like_review para user_id " . $user_id);
            gamipress_trigger_event(array(
                'event' => 'cwr_like_review',
                'user_id' => $user_id,
            ));
        }
        $purchase_intent = get_comment_meta($comment->comment_ID, 'cwr_purchase_intent', true);
        if ($purchase_intent === 'locompre') {
            error_log("[GamiPress Debug] Disparando trigger cwr_purchased_review para user_id " . $user_id);
            gamipress_trigger_event(array(
                'event' => 'cwr_purchased_review',
                'user_id' => $user_id,
            ));
        }
        if (get_comment_meta($comment->comment_ID, 'cwr_feature_suggestion', true)) {
            error_log("[GamiPress Debug] Disparando trigger cwr_suggestion_review para user_id " . $user_id);
            gamipress_trigger_event(array(
                'event' => 'cwr_suggestion_review',
                'user_id' => $user_id,
            ));
        }
    }
}

// Metabox para edición en backend
add_action('add_meta_boxes_comment', 'cwr_add_comment_meta_box');
function cwr_add_comment_meta_box() {
    add_meta_box(
        'cwr_review_extended_fields',
        __('Valoraciones Extendidas', 'codigobell-woo-reviews'),
        'cwr_render_comment_meta_box',
        'comment',
        'normal',
        'high'
    );
}

function cwr_render_comment_meta_box($comment) {
    if ($comment->comment_type !== 'review') {
        echo '<p>Esto no es una reseña de producto.</p>';
        return;
    }
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo\nProcesado\nVariedad\nRegión");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $value = get_comment_meta($comment->comment_ID, 'cwr_rating_' . $rating_key, true);
        echo '<p>
            <label for="cwr_rating_' . esc_attr($rating_key) . '">' . esc_html($rating) . ':</label>
            <input type="number" name="cwr_rating_' . esc_attr($rating_key) . '" value="' . esc_attr($value) . '" min="1" max="5"></p>';
    }
    $feature = get_comment_meta($comment->comment_ID, 'cwr_feature_suggestion', true);
    echo '<p>
        <label for="cwr_feature_suggestion">Sugerencia:</label><br>
        <textarea name="cwr_feature_suggestion">' . esc_textarea($feature) . '</textarea>
    </p>';
    $like_dislike = get_comment_meta($comment->comment_ID, 'cwr_like_dislike', true);
    echo '<p>
        <label for="cwr_like_dislike">Me gusta/No me gusta:</label>
        <select name="cwr_like_dislike">
            <option value="">--</option>
            <option value="like"' . selected($like_dislike, 'like', false) . '>Me gusta</option>
            <option value="dislike"' . selected($like_dislike, 'dislike', false) . '>No me gusta</option>
        </select>
    </p>';
    $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
    $purchase_array = array_filter(array_map('trim', explode("\n", $purchase_options)));
    $purchase_val = get_comment_meta($comment->comment_ID, 'cwr_purchase_intent', true);
    echo '<p>
        <label for="cwr_purchase_intent">Intención de compra:</label>
        <select name="cwr_purchase_intent">
            <option value="">--</option>';
    foreach ($purchase_array as $option) {
        $key = sanitize_key($option);
        echo '<option value="' . esc_attr($key) . '"' . selected($purchase_val, $key, false) . '>' . esc_html($option) . '</option>';
    }
    echo '</select></p>';
    wp_nonce_field('cwr_save_review_admin_fields', 'cwr_review_admin_nonce');
}

// Guardar cambios backend
add_action('edit_comment', 'cwr_save_comment_meta_box');
function cwr_save_comment_meta_box($comment_id) {
    if (!isset($_POST['cwr_review_admin_nonce']) || !wp_verify_nonce($_POST['cwr_review_admin_nonce'], 'cwr_save_review_admin_fields')) {
        return;
    }
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo\nProcesado\nVariedad\nRegión");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        if (isset($_POST['cwr_rating_' . $rating_key])) {
            $rating_value = intval($_POST['cwr_rating_' . $rating_key]);
            if ($rating_value >= 1 && $rating_value <= 5) {
                update_comment_meta($comment_id, 'cwr_rating_' . $rating_key, $rating_value);
            } elseif ($rating_value === 0) {
                delete_comment_meta($comment_id, 'cwr_rating_' . $rating_key);
            }
        }
    }
    if (isset($_POST['cwr_feature_suggestion'])) {
        $feature = sanitize_textarea_field($_POST['cwr_feature_suggestion']);
        update_comment_meta($comment_id, 'cwr_feature_suggestion', $feature);
    }
    if (isset($_POST['cwr_like_dislike'])) {
        $ld = sanitize_text_field($_POST['cwr_like_dislike']);
        update_comment_meta($comment_id, 'cwr_like_dislike', $ld);
    }
    $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
    $purchase_array = array_map('sanitize_key', array_filter(array_map('trim', explode("\n", $purchase_options))));
    if (isset($_POST['cwr_purchase_intent']) && in_array($_POST['cwr_purchase_intent'], $purchase_array)) {
        update_comment_meta($comment_id, 'cwr_purchase_intent', sanitize_text_field($_POST['cwr_purchase_intent']));
    }
}
