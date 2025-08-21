<?php
if (!defined('ABSPATH')) exit;

// Guarda las valoraciones específicas y la sugerencia como metadatos del comentario
add_action('comment_post', 'cwr_save_review_meta', 10, 3);
function cwr_save_review_meta($comment_id, $comment_approved, $commentdata) {
    // Verifica que sea un comentario de un producto de WooCommerce
    if (isset($commentdata['comment_type']) && $commentdata['comment_type'] === 'review') {
        // Obtiene las valoraciones específicas desde las opciones
        $ratings = get_option('cwr_specific_ratings', "Calidad\nDiseño\nValor");
        $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

        // Guarda cada valoración específica
        foreach ($ratings_array as $rating) {
            $rating_key = sanitize_key($rating);
            if (isset($_POST['cwr_rating_' . $rating_key]) && is_numeric($_POST['cwr_rating_' . $rating_key])) {
                $rating_value = intval($_POST['cwr_rating_' . $rating_key]);
                if ($rating_value >= 1 && $rating_value <= 5) {
                    update_comment_meta($comment_id, 'cwr_rating_' . $rating_key, $rating_value);
                }
            }
        }

        // Guarda la sugerencia de característica adicional
        if (isset($_POST['cwr_feature_suggestion']) && !empty($_POST['cwr_feature_suggestion'])) {
            $feature_suggestion = sanitize_textarea_field($_POST['cwr_feature_suggestion']);
            update_comment_meta($comment_id, 'cwr_feature_suggestion', $feature_suggestion);
        }
    }
}