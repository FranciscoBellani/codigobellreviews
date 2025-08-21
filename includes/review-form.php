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

    // Añade campos para las valoraciones específicas con estrellas SVG
    $comment_form['comment_field'] .= '<div class="cwr-specific-ratings">';
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $comment_form['comment_field'] .= '
            <p class="comment-form-' . esc_attr($rating_key) . '">
                <label for="cwr_rating_' . esc_attr($rating_key) . '">' . esc_html($rating) . ' <span class="required">*</span></label>
                <div class="star-rating" role="img" aria-label="' . esc_attr($rating) . ' rating">
                    <input type="hidden" name="cwr_rating_' . esc_attr($rating_key) . '" id="cwr_rating_' . esc_attr($rating_key) . '" class="cwr-star-rating" value="0" required>
                    <span class="cwr-stars" data-rating="0">';
        for ($i = 1; $i <= 5; $i++) {
            $comment_form['comment_field'] .= '<svg width="20" height="20" viewBox="0 0 24 24" class="cwr-star" data-star="' . $i . '"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.416 3.967 1.48-8.279L.587 9.306l8.332-1.151z"/></svg>';
        }
        $comment_form['comment_field'] .= '</span></div></p>';
    }
    $comment_form['comment_field'] .= '</div>';

    // Añade campo para característica adicional
    $comment_form['comment_field'] .= '
        <p class="comment-form-feature">
            <label for="cwr_feature_suggestion">' . __('Sugerir una característica adicional', 'codigobell-woo-reviews') . '</label>
            <textarea id="cwr_feature_suggestion" name="cwr_feature_suggestion" cols="45" rows="4" maxlength="500"></textarea>
        </p>';

    // Añade CSS y JavaScript para las estrellas
    $comment_form['comment_field'] .= '
        <style>
            .cwr-specific-ratings .star-rating {
                display: inline-block;
                font-size: 0;
                line-height: 1;
                margin-top: 5px;
            }
            .cwr-specific-ratings .cwr-stars {
                display: inline-flex;
                cursor: pointer;
            }
            .cwr-specific-ratings .cwr-star {
                fill: none;
                stroke: #ccc;
                stroke-width: 2;
                margin-right: 5px;
                transition: fill 0.2s, stroke 0.2s;
            }
            .cwr-specific-ratings .cwr-star.filled {
                fill: #f5c518;
                stroke: #f5c518;
            }
            .cwr-specific-ratings .cwr-star:hover,
            .cwr-specific-ratings .cwr-star:hover ~ .cwr-star {
                fill: none;
                stroke: #ccc;
            }
            .cwr-specific-ratings .cwr-star.filled:hover,
            .cwr-specific-ratings .cwr-star.filled:hover ~ .cwr-star.filled {
                fill: #f5c518;
                stroke: #f5c518;
            }
        </style>
        <script>
            (function() {
                function initStarRatings() {
                    document.querySelectorAll(".cwr-specific-ratings .cwr-stars").forEach(function(starsContainer) {
                        var input = starsContainer.previousElementSibling;
                        var stars = starsContainer.querySelectorAll(".cwr-star");
                        stars.forEach(function(star, index) {
                            star.addEventListener("click", function() {
                                var rating = index + 1;
                                input.value = rating;
                                starsContainer.setAttribute("data-rating", rating);
                                stars.forEach(function(s, i) {
                                    s.classList.toggle("filled", i < rating);
                                });
                            });
                            star.addEventListener("mouseover", function() {
                                stars.forEach(function(s, i) {
                                    s.classList.toggle("filled", i < (index + 1));
                                });
                            });
                            star.addEventListener("mouseout", function() {
                                var currentRating = parseInt(starsContainer.getAttribute("data-rating")) || 0;
                                stars.forEach(function(s, i) {
                                    s.classList.toggle("filled", i < currentRating);
                                });
                            });
                        });
                    });
                }
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", initStarRatings);
                } else {
                    initStarRatings();
                }
            })();
        </script>';

    return $comment_form;
}

// Añade traducciones para el dominio del plugin
add_action('init', 'cwr_load_textdomain');
function cwr_load_textdomain() {
    load_plugin_textdomain('codigobell-woo-reviews', false, dirname(plugin_basename(__FILE__)) . '/languages');
}