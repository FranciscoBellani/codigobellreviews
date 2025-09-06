<?php
if (!defined('ABSPATH')) exit;

// Añade un metabox en la edición de comentarios/reseñas para editar valoraciones extendidas
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
    // Solo mostrar campos si es tipo review
    if ($comment->comment_type !== 'review') {
        echo '<p style="color:#888;">Esto no es una reseña de producto.</p>';
        return;
    }

    // Valoraciones específicas
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo\nProcesado\nVariedad\nRegión");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $value = get_comment_meta($comment->comment_ID, 'cwr_rating_' . $rating_key, true);
        echo '<p>
            <label for="cwr_rating_' . esc_attr($rating_key) . '"><strong>' . esc_html($rating) . ':</strong></label>
            <input type="number" name="cwr_rating_' . esc_attr($rating_key) . '" value="' . esc_attr($value) . '" min="1" max="5" style="width:60px;"> /5</p>';
    }

    // Sugerencia característica
    $feature = get_comment_meta($comment->comment_ID, 'cwr_feature_suggestion', true);
    echo '<p>
        <label for="cwr_feature_suggestion"><strong>Sugerencia:</strong></label><br>
        <textarea name="cwr_feature_suggestion" style="width:100%;min-height:40px;">' . esc_textarea($feature) . '</textarea>
    </p>';

    // Like/Dislike
    $like_dislike = get_comment_meta($comment->comment_ID, 'cwr_like_dislike', true);
    echo '<p>
        <label for="cwr_like_dislike"><strong>Me gusta/No me gusta:</strong></label>
        <select name="cwr_like_dislike">
            <option value="">--</option>
            <option value="like" ' . selected($like_dislike, 'like', false) . '>👍 Me gusta</option>
            <option value="dislike" ' . selected($like_dislike, 'dislike', false) . '>👎 No me gusta</option>
        </select>
    </p>';

    // Intención de compra
    $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
    $purchase_array = array_filter(array_map('trim', explode("\n", $purchase_options)));
    $purchase_val = get_comment_meta($comment->comment_ID, 'cwr_purchase_intent', true);
    echo '<p>
        <label for="cwr_purchase_intent"><strong>Intención de compra:</strong></label>
        <select name="cwr_purchase_intent">
            <option value="">--</option>';
    foreach ($purchase_array as $option) {
        $key = sanitize_key($option);
        echo '<option value="' . esc_attr($key) . '" ' . selected($purchase_val, $key, false) . '>' . esc_html($option) . '</option>';
    }
    echo '</select>
    </p>';

    // Campo de seguridad
    wp_nonce_field('cwr_save_review_admin_fields', 'cwr_review_admin_nonce');
}

// Guarda los campos del metabox de edición de reseña
add_action('edit_comment', 'cwr_save_comment_meta_box');
function cwr_save_comment_meta_box($comment_id) {
    if (!isset($_POST['cwr_review_admin_nonce']) || !wp_verify_nonce($_POST['cwr_review_admin_nonce'], 'cwr_save_review_admin_fields')) {
        return;
    }

    // Ratings extendidos
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

    // Sugerencia
    if (isset($_POST['cwr_feature_suggestion'])) {
        $feature = sanitize_textarea_field($_POST['cwr_feature_suggestion']);
        update_comment_meta($comment_id, 'cwr_feature_suggestion', $feature);
    }

    // Like/Dislike
    if (isset($_POST['cwr_like_dislike'])) {
        $ld = sanitize_text_field($_POST['cwr_like_dislike']);
        update_comment_meta($comment_id, 'cwr_like_dislike', $ld);
    }

    // Intención de compra
    $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
    $purchase_array = array_map('sanitize_key', array_filter(array_map('trim', explode("\n", $purchase_options))));
    if (isset($_POST['cwr_purchase_intent']) && in_array($_POST['cwr_purchase_intent'], $purchase_array)) {
        update_comment_meta($comment_id, 'cwr_purchase_intent', sanitize_text_field($_POST['cwr_purchase_intent']));
    }
}
