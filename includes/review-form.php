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

    // Añade campos para las valoraciones específicas con estrellas
    $comment_form['comment_field'] .= '<div class="cwr-specific-ratings">';
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $comment_form['comment_field'] .= '
            <p class="comment-form-' . esc_attr($rating_key) . '">
                <label for="cwr_rating_' . esc_attr($rating_key) . '">' . esc_html($rating) . '</label>
                <div class="star-rating" role="img" aria-label="' . esc_attr($rating) . '">
                    <select name="cwr_rating_' . esc_attr($rating_key) . '" id="cwr_rating_' . esc_attr($rating_key) . '" class="cwr-star-rating" required style="display:none;">
                        <option value="">' . __('Selecciona', 'codigobell-woo-reviews') . '</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <span class="cwr-stars" data-rating="0"></span>
                </div>
            </p>';
    }
    $comment_form['comment_field'] .= '</div>';

    // Añade campo para característica adicional
    $comment_form['comment_field'] .= '
        <p class="comment-form-feature">
            <label for="cwr_feature_suggestion">' . __('Sugerir una característica adicional', 'codigobell-woo-reviews') . '</label>
            <textarea id="cwr_feature_suggestion" name="cwr_feature_suggestion" cols="45" rows="4" maxlength="500"></textarea>
        </p>';

    // Añade JavaScript y CSS para las estrellas
    $comment_form['comment_field'] .= '
        <style>
            .cwr-specific-ratings .star-rating {
                display: inline-block;
                font-size: 0;
                line-height: 1;
                position: relative;
            }
            .cwr-specific-ratings .cwr-stars {
                display: inline-block;
                width: 100px;
                height: 20px;
                background: url(' . esc_url(plugins_url('images/star-golden.svg', __FILE__)) . ') 0 0 repeat-x;
                background-size: 20px 20px;
            }
            .cwr-specific-ratings .cwr-stars::before {
                content: "";
                display: block;
                width: 100px;
                height: 20px;
                background: url(' . esc_url(plugins_url('images/star-gray.svg', __FILE__)) . ') 0 0 repeat-x;
                background-size: 20px 20px;
            }
            .cwr-specific-ratings .cwr-stars[data-rating="1"]::before { width: 20px; }
            .cwr-specific-ratings .cwr-stars[data-rating="2"]::before { width: 40px; }
            .cwr-specific-ratings .cwr-stars[data-rating="3"]::before { width: 60px; }
            .cwr-specific-ratings .cwr-stars[data-rating="4"]::before { width: 80px; }
            .cwr-specific-ratings .cwr-stars[data-rating="5"]::before { width: 100px; }
        </style>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                document.querySelectorAll(".cwr-specific-ratings .cwr-star-rating").forEach(function(select) {
                    var stars = select.nextElementSibling;
                    select.addEventListener("change", function() {
                        stars.setAttribute("data-rating", this.value);
                    });
                    stars.addEventListener("click", function(e) {
                        var rect = stars.getBoundingClientRect();
                        var x = e.clientX - rect.left;
                        var rating = Math.ceil(x / (rect.width / 5));
                        select.value = rating;
                        stars.setAttribute("data-rating", rating);
                    });
                });
            });
        </script>';

    return $comment_form;
}

// Añade traducciones para el dominio del plugin
add_action('init', 'cwr_load_textdomain');
function cwr_load_textdomain() {
    load_plugin_textdomain('codigobell-woo-reviews', false, dirname(plugin_basename(__FILE__)) . '/languages');
}