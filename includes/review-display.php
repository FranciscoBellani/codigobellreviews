<?php
if (!defined('ABSPATH')) exit;

// Personaliza la visualización de las reseñas
add_action('woocommerce_review_after_comment_text', 'cwr_display_review_meta', 10, 1);
function cwr_display_review_meta($comment) {
    $comment_id = $comment->comment_ID;

    // Obtiene las valoraciones específicas desde las opciones
    $ratings = get_option('cwr_specific_ratings', "Calidad\nDiseño\nValor");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    // Muestra las valoraciones específicas
    echo '<div class="cwr-review-meta">';
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $rating_value = get_comment_meta($comment_id, 'cwr_rating_' . $rating_key, true);
        if ($rating_value && is_numeric($rating_value)) {
            echo '<p><strong>' . esc_html($rating) . ':</strong> ' . intval($rating_value) . '/5</p>';
        }
    }

    // Muestra la sugerencia de característica adicional
    $feature_suggestion = get_comment_meta($comment_id, 'cwr_feature_suggestion', true);
    if ($feature_suggestion) {
        echo '<p><strong>' . __('Sugerencia de característica', 'codigobell-woo-reviews') . ':</strong> ' . esc_html($feature_suggestion) . '</p>';
    }
    echo '</div>';
}