<?php
if (!defined('ABSPATH')) exit;

// Mostrar estadísticas de valoraciones extras en la pestaña Reviews de WooCommerce
add_action('woocommerce_review_before', 'cwr_display_attribute_statistics_in_reviews_tab', 1);

function cwr_display_attribute_statistics_in_reviews_tab() {
    if (!is_product()) return;

    global $product;
    $product_id = $product->get_id();
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));
    $total_ratings = [];
    $count_ratings = [];

    // Obtener todos los comentarios aprobados del producto
    $args = array(
        'post_id' => $product_id,
        'status' => 'approve',
        'type' => 'review',
    );
    $comments = get_comments($args);

    // Calcular promedios
    foreach ($comments as $comment) {
        $comment_id = $comment->comment_ID;
        foreach ($ratings_array as $rating) {
            $rating_key = sanitize_key($rating);
            $rating_value = get_comment_meta($comment_id, 'cwr_rating_' . $rating_key, true);
            if ($rating_value && is_numeric($rating_value)) {
                $total_ratings[$rating_key] = isset($total_ratings[$rating_key]) ? $total_ratings[$rating_key] + $rating_value : $rating_value;
                $count_ratings[$rating_key] = isset($count_ratings[$rating_key]) ? $count_ratings[$rating_key] + 1 : 1;
            }
        }
    }

    // Solo mostrar una vez antes del loop de reseñas
    static $displayed = false;
    if ($displayed) return;
    $displayed = true;

    echo '<div class="cwr-attribute-stats" style="margin:15px 0; border-top:1px solid #eee; padding-top:10px;">';
    echo '<h4>Estadísticas de valoraciones específicas</h4>';

    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $average = ($count_ratings[$rating_key] ?? 0) > 0 ? round($total_ratings[$rating_key] / $count_ratings[$rating_key], 1) : 0;
        $percentage = ($average / 5) * 100;

        echo '<p style="display: flex; align-items: center; margin: 5px 0;">';
        // Iconos opcionales
        switch (strtolower($rating)) {
            case 'acidez':
                $icon = '<span class="dashicons dashicons-image-filter" style="color:#0073aa; margin-right:8px; font-size:1.3em;"></span>';
                break;
            case 'dulzura':
                $icon = '<span class="dashicons dashicons-lemon" style="color:#00a32a; margin-right:8px; font-size:1.3em;"></span>';
                break;
            case 'cuerpo':
                $icon = '<span class="dashicons dashicons-admin-users" style="color:#d54e21; margin-right:8px; font-size:1.3em;"></span>';
                break;
            default:
                $icon = '';
        }
        echo $icon;
        echo '<strong>' . esc_html($rating) . ':</strong>';
        // Solo estrellas doradas, sin capa gris
        echo '<span class="star-rating" title="' . esc_attr($average) . ' de 5" style="display:inline-block; position:relative; font-size: 1.2em; color: #e6a600; margin-left: 8px;">';
        echo '<span style="width:' . esc_attr($percentage) . '%; overflow: hidden; white-space: nowrap; position: static; color: #e6a600;">★★★★★</span>';
        echo '</span>';
        echo '</p>';
    }
    echo '</div>';
}
