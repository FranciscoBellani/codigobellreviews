<?php
if (!defined('ABSPATH')) exit;

// Personaliza el formulario de reseñas de WooCommerce
add_filter('woocommerce_product_review_comment_form_args', 'cwr_custom_review_form', 10, 1);
function cwr_custom_review_form($comment_form) {
    // Solo usuarios registrados pueden dejar reseñas
    if (!is_user_logged_in()) {
        $comment_form['comment_field'] = '<p class="comment-form-comment"><strong>' . __('Debes estar registrado para dejar una reseña.', 'codigobell-woo-reviews') . '</strong></p>';
        $comment_form['fields'] = []; // Elimina otros campos
        $comment_form['submit_button'] = ''; // Oculta el botón de enviar
        return $comment_form;
    }

    // Obtiene las valoraciones específicas desde las opciones
    $ratings = get_option('cwr_specific_ratings', "Calidad\nDiseño\nValor");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    // Añade campos para las valoraciones específicas
    $comment_form['comment_field'] .= '<div class="cwr-specific-ratings">';
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $comment_form['comment_field'] .= '
            <p class="comment-form-' . esc_attr($rating_key) . '">
                <label for="cwr_rating_' . esc_attr($rating_key) . '">' . esc_html($rating) . ' (' . __('1-5 estrellas', 'codigobell-woo-reviews') . ')</label>
                <select name="cwr_rating_' . esc_attr($rating_key) . '" id="cwr_rating_' . esc_attr($rating_key) . '" required>
                    <option value="">' . __('Selecciona', 'codigobell-woo-reviews') . '</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </p>';
    }
    $comment_form['comment_field'] .= '</div>';

    // Añade campo para característica adicional
    $comment_form['comment_field'] .= '
        <p class="comment-form-feature">
            <label for="cwr_feature_suggestion">' . __('Sugerir una característica adicional', 'codigobell-woo-reviews') . '</label>
            <textarea id="cwr_feature_suggestion" name="cwr_feature_suggestion" cols="45" rows="4" maxlength="500"></textarea>
        </p>';

    return $comment_form;
}

// Añade traducciones para el dominio del plugin
add_action('init', 'cwr_load_textdomain');
function cwr_load_textdomain() {
    load_plugin_textdomain('codigobell-woo-reviews', false, dirname(plugin_basename(__FILE__)) . '/languages');
}