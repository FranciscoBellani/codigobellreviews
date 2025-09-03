<?php
if (!defined('ABSPATH')) exit;

// Personaliza el formulario de reseñas de WooCommerce
add_filter('woocommerce_product_review_comment_form_args', 'cwr_custom_review_form', 10, 1);
function cwr_custom_review_form($comment_form) {
    if (!is_user_logged_in()) {
        // Obtener la URL de My Account con redirección a la página actual
        $redirect_url = get_permalink(); // URL del producto actual
        $my_account_url = get_permalink(wc_get_page_id('myaccount'));
        $nonce = wp_create_nonce('cwr_login_nonce'); // Nonce para seguridad
        $login_url = add_query_arg(
            array(
                'redirect_to' => urlencode($redirect_url),
                'cwr_nonce' => $nonce
            ),
            $my_account_url
        );
        
        // Reemplazar el mensaje con un botón seguro
        $comment_form['comment_field'] = '
            <p class="comment-form-comment"><strong>' . __('Debes estar registrado para dejar una reseña.', 'codigobell-woo-reviews') . '</strong></p>
            <p><a href="' . esc_url($login_url) . '" class="button" style="background: #6B4F31; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">' . __('Iniciar sesión para dejar una reseña', 'codigobell-woo-reviews') . '</a></p>';
        $comment_form['fields'] = [];
        $comment_form['submit_button'] = '';
        $comment_form['comment_notes_after'] = '';
        return $comment_form;
    }

    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    // Añadir descripción para la valoración general de WooCommerce
    $comment_form['comment_field'] .= '<p><small>(Gana 5 puntos por la valoración general)</small></p>';

    $comment_form['comment_field'] .= '<div class="cwr-specific-ratings" style="margin: 10px 0;">';
    $comment_form['comment_field'] .= '<p><strong>Valoraciones Específicas</strong> <small>(Gana 10 puntos por completar todas las características)</small></p>'; // Descripción para características
    foreach ($ratings_array as $rating) {
        $rating_key = sanitize_key($rating);
        $comment_form['comment_field'] .= '
            <p class="comment-form-' . esc_attr($rating_key) . '">
                <label for="cwr_rating_' . esc_attr($rating_key) . '">' . esc_html($rating) . ' <span class="required">*</span></label>
                <div class="cwr-star-rating" data-rating-key="' . esc_attr($rating_key) . '" role="img" aria-label="' . esc_attr($rating) . ' rating">
                    <span class="star-rating" style="width: 6em; height: 1.5em; position: relative; display: inline-block;">
                        <span style="position: absolute; left: 0; top: 0; width: 0%;"></span>
                    </span>
                    <input type="hidden" name="cwr_rating_' . esc_attr($rating_key) . '" id="cwr_rating_' . esc_attr($rating_key) . '" class="cwr-star-input" value="0" required>
                </div>
                <small>(Gana 3 puntos por esta valoración)</small>'; // Descripción por característica individual
    }
    $comment_form['comment_field'] .= '</div>';

    // Nuevo: Sección Me Gusta / No Me Gusta
    $like_dislike_label = get_option('cwr_like_dislike_label', "Me Gusta / No Me Gusta");
    $comment_form['comment_field'] .= '
        <p class="comment-form-like-dislike">
            <label>' . esc_html($like_dislike_label) . ' <span class="required">*</span></label>
            <div class="cwr-like-dislike" style="display: flex; gap: 10px;">
                <button type="button" class="cwr-thumb" data-value="like" style="font-size: 2em; cursor: pointer;">👍</button>
                <button type="button" class="cwr-thumb" data-value="dislike" style="font-size: 2em; cursor: pointer;">👎</button>
            </div>
            <input type="hidden" name="cwr_like_dislike" id="cwr_like_dislike" value="" required>
            <small>(Gana 3 puntos por seleccionar)</small>'; // Descripción para like/dislike
    $comment_form['comment_field'] .= '</p>';

    // Nuevo: Sección Compraste / Lo Voy a Comprar
    $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
    $purchase_array = array_filter(array_map('trim', explode("\n", $purchase_options)));
    $comment_form['comment_field'] .= '
        <p class="comment-form-purchase-intent">
            <label for="cwr_purchase_intent">Intención de Compra <span class="required">*</span></label>
            <select name="cwr_purchase_intent" id="cwr_purchase_intent" required>
                <option value="">Selecciona una opción</option>';
    foreach ($purchase_array as $option) {
        $comment_form['comment_field'] .= '<option value="' . esc_attr(sanitize_key($option)) . '">' . esc_html($option) . '</option>';
    }
    $comment_form['comment_field'] .= '</select>
            <small>(Gana 5 puntos por seleccionar)</small>'; // Descripción para intención de compra
    $comment_form['comment_field'] .= '</p>';

    $comment_form['comment_field'] .= '
        <p class="comment-form-feature">
            <label for="cwr_feature_suggestion">' . __('Sugerir una característica adicional', 'codigobell-woo-reviews') . '</label>
            <textarea id="cwr_feature_suggestion" name="cwr_feature_suggestion" cols="45" rows="4" maxlength="500"></textarea>
            <small>(Gana 10 puntos por sugerir)</small>'; // Descripción para sugerencia
    $comment_form['comment_field'] .= '</p>';

    $script_nonce = wp_create_nonce('cwr_star_rating_script');
    $comment_form['comment_field'] .= '
        <style>
            .cwr-specific-ratings .star-rating {
                display: inline-block;
                font-size: 1.5em; /* Aumenta el tamaño para coincidir con valoración general */
                height: 1.5em;
                line-height: 1.5;
                overflow: hidden;
                position: relative;
                width: 6em; /* Ajusta el ancho para 5 estrellas */
                margin: 0 0.2em;
                vertical-align: middle;
            }
            .cwr-specific-ratings .star-rating::before {
                content: "\f155\f155\f155\f155\f155";
                font-family: dashicons;
                letter-spacing: 0.13em; /* Ajuste para espaciado */
                color: #d3ced2;
            }
            .cwr-specific-ratings .star-rating span {
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                color: #e6a600;
                overflow: hidden;
                white-space: nowrap;
            }
            .cwr-specific-ratings .star-rating span::before {
                content: "\f155\f155\f155\f155\f155";
                font-family: dashicons;
                letter-spacing: 0.13em;
            }
            .cwr-like-dislike button.selected { background: #6B4F31; color: white; border-radius: 50%; }
            small { color: #666; font-size: 0.8em; display: block; margin-top: 5px; } /* Estilo para las descripciones de puntos */
        </style>
        <script id="cwr-star-rating-script-' . esc_attr($script_nonce) . '" nonce="' . esc_attr($script_nonce) . '">
            jQuery(document).ready(function($) {
                console.log("Codigobell Woo Reviews: Inicializando estrellas");
                $(".cwr-star-rating").each(function() {
                    var $container = $(this);
                    var $stars = $container.find(".star-rating");
                    var $input = $container.find(".cwr-star-input");
                    var $fillSpan = $stars.find("span:first");

                    if ($stars.length > 0) {
                        console.log("Inicializado:", $stars.length, "estrellas en", $container.attr("data-rating-key"));

                        $input.val(0);
                        $fillSpan.css("width", "0%");

                        $stars.on("click", function(e) {
                            var offset = e.offsetX;
                            var width = $stars.width();
                            var rating = Math.ceil((offset / width) * 5);
                            rating = Math.max(1, Math.min(5, rating));
                            $input.val(rating);
                            $fillSpan.css("width", (rating / 5 * 100) + "%");
                            console.log("Clic en estrella, rating:", rating);
                        });

                        $stars.on("mousemove", function(e) {
                            var offset = e.offsetX;
                            var width = $stars.width();
                            var hoverRating = Math.ceil((offset / width) * 5);
                            hoverRating = Math.max(1, Math.min(5, hoverRating));
                            $fillSpan.css("width", (hoverRating / 5 * 100) + "%");
                        });

                        $stars.on("mouseleave", function() {
                            var currentRating = parseInt($input.val()) || 0;
                            $fillSpan.css("width", (currentRating / 5 * 100) + "%");
                        });
                    } else {
                        console.log("No se encontraron estrellas en", $container.attr("data-rating-key"));
                    }
                });

                $(".cwr-thumb").on("click", function() {
                    $(".cwr-thumb").removeClass("selected");
                    $(this).addClass("selected");
                    $("#cwr_like_dislike").val($(this).data("value"));
                });
            });
        </script>';

    return $comment_form;
}

// Añade traducciones
add_action('init', 'cwr_load_textdomain');
function cwr_load_textdomain() {
    load_plugin_textdomain('codigobell-woo-reviews', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Carga jQuery y dashicons
add_action('wp_enqueue_scripts', 'cwr_enqueue_scripts');
function cwr_enqueue_scripts() {
    if (is_product()) {
        wp_enqueue_script('jquery', false, [], null, true);
        wp_enqueue_style('dashicons'); // Forzamos la carga de dashicons
    }
}