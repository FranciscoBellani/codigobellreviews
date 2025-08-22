<?php
if (!defined('ABSPATH')) exit;

// Permitir que WooCommerce maneje la valoración general
add_filter('woocommerce_product_get_rating_html', 'cwr_modify_rating_html', 10, 2);
function cwr_modify_rating_html($html, $rating) {
    console_log("WooCommerce rating HTML: " . $html); // Depuración
    return $html;
}

// Mostrar meta de reseñas personalizadas como íconos con indicadores
add_action('woocommerce_review_after_comment_text', 'cwr_display_custom_review_meta', 30, 1); // Prioridad 30 para que vaya después
function cwr_display_custom_review_meta($comment) {
    $comment_id = $comment->comment_ID;
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    console_log("Rendering custom review meta for comment ID: " . $comment_id); // Depuración
    echo '<div class="cwr-custom-review-meta" style="margin: 10px 0; clear: both;">';
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $rating_value = get_comment_meta($comment_id, 'cwr_rating_' . $rating_key, true);
        if ($rating_value && is_numeric($rating_value)) {
            console_log("Rendering $rating with value: " . $rating_value); // Depuración
            echo '<p style="margin: 5px 0; display: flex; align-items: center;">';
            // Íconos personalizados con dashicons
            switch (strtolower($rating)) {
                case 'acidez':
                    echo '<span class="dashicons dashicons-image-filter" style="font-size: 1.5em; color: #0073aa; margin-right: 10px;"></span>'; // Gota
                    break;
                case 'dulzura':
                    echo '<span class="dashicons dashicons-lemon" style="font-size: 1.5em; color: #00a32a; margin-right: 10px;"></span>'; // Hoja
                    break;
                case 'cuerpo':
                    echo '<span class="dashicons dashicons-admin-users" style="font-size: 1.5em; color: #d54e21; margin-right: 10px;"></span>'; // Cuerpo
                    break;
            }
            echo '<strong>' . esc_html($rating) . ':</strong> <span style="margin-left: 5px;">' . esc_html($rating_value) . '/5</span>';
            echo '</p>';
        }
    }
    $feature_suggestion = get_comment_meta($comment_id, 'cwr_feature_suggestion', true);
    if ($feature_suggestion) {
        echo '<p style="margin: 5px 0;"><strong>' . __('Sugerencia de característica', 'codigobell-woo-reviews') . ':</strong> ' . esc_html($feature_suggestion) . '</p>';
    }
    echo '</div>';

    echo '
        <style>
            .cwr-custom-review-meta {
                margin-top: 15px;
                border-top: 1px solid #eee;
                padding-top: 10px;
                clear: both;
            }
            .cwr-custom-review-meta p {
                display: flex;
                align-items: center;
                font-size: 1.1em;
            }
            .cwr-custom-review-meta .dashicons {
                vertical-align: middle;
            }
            /* Asegurar separación con la valoración general */
            .comment_container .star-rating {
                margin-bottom: 10px;
                display: block;
            }
        </style>';
}

// Función auxiliar para console.log
if (!function_exists('console_log')) {
    function console_log($message) {
        echo '<script>console.log(' . json_encode($message) . ')</script>';
    }
}