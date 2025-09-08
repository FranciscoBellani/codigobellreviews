<?php
if (!defined('ABSPATH')) exit;


// Engancha el bloque dentro de la pestaña Reviews, antes del listado de comentarios
add_filter('woocommerce_product_tabs', 'cwr_add_extended_ratings_to_reviews_tab');


function cwr_add_extended_ratings_to_reviews_tab($tabs) {
    // Modificar callback original de la pestaña reviews para extender contenido
    $tabs['reviews']['callback'] = 'cwr_custom_reviews_tab_content';
    return $tabs;
}


function cwr_custom_reviews_tab_content() {
    // Mostrar el bloque de atributos extendidos
    cwr_custom_reviews_visual_block();

    // Nuevo: Bloque para atributos adicionales
    cwr_additional_attributes_visual_block();

    // Mostrar puntaje total del usuario actual
    cwr_show_user_total_score();

    // Mostrar el template original de las reseñas (comentarios)
    comments_template();
}

// Función que muestra el puntaje total actual del usuario con botón a Mi Cuenta
function cwr_show_user_total_score() {
    $user_id = get_current_user_id();
    if (!$user_id) return;

    // Usar el shortcode que suma puntos totales registrado en tu plugin
    $total_points = do_shortcode('[gamipress_site_points]');
    
    $account_url = wc_get_page_permalink('myaccount');
    
    echo '<div style="margin:20px 0; padding:12px; background:#e0f7fa; border-radius:8px; text-align:center;">';
    echo '<p style="font-weight:bold; font-size:1.2em;">' . sprintf(__('Puntaje total: %s', 'codigobell-woo-reviews'), $total_points) . '</p>';
    echo '<a href="' . esc_url($account_url) . '" style="background:#6B4F31; color:#fff; padding:10px 20px; border-radius:5px; text-decoration:none;">' . __('Ir a Mi Cuenta', 'codigobell-woo-reviews') . '</a>';
    echo '</div>';
}


function cwr_custom_reviews_visual_block() {
    if (!is_product()) return;

    global $product;
    $product_id = $product->get_id();

    // Configuración existente...
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo\nProcesado\nVariedad\nRegión");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    $icons = [
        'acidez'      => '💧',
        'dulzura'     => '🍬',
        'cuerpo'      => '☕',
        'intensidad'  => '🌱',
        'variedad'    => '🌿',
        'region'      => '📍',
    ];

    $total_ratings = [];
    $count_ratings = [];
    $sum_general = 0;
    $count_general = 0;

    $args = [
        'post_id' => $product_id,
        'status' => 'approve',
        'type' => 'review',
    ];
    $comments = get_comments($args);

    foreach ($comments as $comment) {
        $general_rating = get_comment_meta($comment->comment_ID, 'rating', true);
        if ($general_rating && is_numeric($general_rating)) {
            $sum_general += $general_rating;
            $count_general++;
        }
        foreach ($ratings_array as $rating) {
            $rating_key = sanitize_key($rating);
            $rating_value = get_comment_meta($comment->comment_ID, 'cwr_rating_' . $rating_key, true);
            if ($rating_value && is_numeric($rating_value)) {
                $total_ratings[$rating_key] = isset($total_ratings[$rating_key]) ? $total_ratings[$rating_key] + $rating_value : $rating_value;
                $count_ratings[$rating_key] = isset($count_ratings[$rating_key]) ? $count_ratings[$rating_key] + 1 : 1;
            }
        }
    }

    $promedio_general = $count_general > 0 ? round($sum_general / $count_general, 1) : 0;
    ?>
    <div class="custom-review-stats" style="padding: 24px 12px; background: #f8f3ef; border-radius: 16px; max-width: 520px; margin: 30px auto;">
        <div class="attributes-icons" style="display: flex; justify-content: space-between; margin-bottom: 18px;">
            <?php foreach ($ratings_array as $rating):
                $rating_key = sanitize_key($rating);
                $average = (!empty($count_ratings[$rating_key]) && $count_ratings[$rating_key] > 0) ? round($total_ratings[$rating_key] / $count_ratings[$rating_key], 1) : 0;
                $percentage = ($average / 5) * 100;
                ?>
                <div style="flex: 1; text-align: center; margin: 0 6px;">
                    <div style="font-size: 2em;"><?php echo esc_html($icons[$rating_key] ?? ''); ?></div>
                    <div style="font-size: 1em; margin-top: 6px;"><?php echo esc_html($rating); ?></div>
                    <div style="background: #eee; border-radius: 6px; overflow: hidden; height: 12px; margin: 6px auto 0; max-width: 90%;">
                        <div style="background: #6B4F31; height: 100%; width: <?php echo esc_attr($percentage); ?>%;"></div>
                    </div>
                    <div style="margin-top: 4px; font-size: 0.95em; font-weight: 600;">
                        <?php echo esc_html($average); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; font-size: 1.2em; font-weight: bold;">
            <?php echo esc_html($promedio_general); ?> / 5
            <span style="font-size: 0.8em; color: #666;">(Promedio General)</span>
        </div>
    </div>
    <?php
}

function cwr_additional_attributes_visual_block() {
    if (!is_product()) return;

    global $product;
    $product_id = $product->get_id();

    $like_count = 0;
    $dislike_count = 0;
    $comments = get_comments(array(
        'post_id' => $product_id,
        'status' => 'approve',
        'type' => 'review',
    ));
    foreach ($comments as $comment) {
        $value = get_comment_meta($comment->comment_ID, 'cwr_like_dislike', true);
        if ($value === 'like') $like_count++;
        if ($value === 'dislike') $dislike_count++;
    }
    $total_ld = $like_count + $dislike_count;
    $like_percentage = $total_ld > 0 ? round(($like_count / $total_ld) * 100) : 0;

    // Para Purchase Intent
    $purchase_counts = [];
    $purchase_options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
    $purchase_array = array_filter(array_map('trim', explode("\n", $purchase_options)));
    foreach ($purchase_array as $option) {
        $key = sanitize_key($option);
        $purchase_counts[$key] = 0;
    }
    $total_purchase = 0;
    foreach ($comments as $comment) {
        $value = get_comment_meta($comment->comment_ID, 'cwr_purchase_intent', true);
        if (isset($purchase_counts[$value])) {
            $purchase_counts[$value]++;
            $total_purchase++;
        }
    }

    $like_dislike_label = get_option('cwr_like_dislike_label', "Me Gusta / No Me Gusta");

    ?>
    <div class="additional-review-stats" style="padding: 24px 12px; background: #f8f3ef; border-radius: 16px; max-width: 520px; margin: 30px auto;">
        <h3 style="text-align: center; margin-bottom: 18px;"><?php echo esc_html($like_dislike_label); ?></h3>
        <div style="display: flex; justify-content: space-around;">
            <div style="text-align: center;">
                <div style="font-size: 2em;">👍</div>
                <div>Completamente: <?php echo $like_percentage; ?>%</div>
                <div style="background: #eee; border-radius: 6px; overflow: hidden; height: 12px; margin: 6px auto 0; max-width: 200px;">
                    <div style="background: #6B4F31; height: 100%; width: <?php echo esc_attr($like_percentage); ?>%;"></div>
                </div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2em;">👎</div>
                <div>Para Nada: <?php echo 100 - $like_percentage; ?>%</div>
                <div style="background: #eee; border-radius: 6px; overflow: hidden; height: 12px; margin: 6px auto 0; max-width: 200px;">
                    <div style="background: #6B4F31; height: 100%; width: <?php echo esc_attr(100 - $like_percentage); ?>%;"></div>
                </div>
            </div>
        </div>

        <h3 style="text-align: center; margin: 24px 0 18px;">Intención de Compra</h3>
        <div class="purchase-icons" style="display: flex; justify-content: space-between;">
            <?php foreach ($purchase_array as $option):
                $key = sanitize_key($option);
                $count = $purchase_counts[$key] ?? 0;
                $percentage = $total_purchase > 0 ? round(($count / $total_purchase) * 100) : 0;
                $icon = ($key === 'locompre') ? '🛒' : '📅'; // Iconos de ejemplo: carrito para "compré", calendario para "voy a comprar"
                ?>
                <div style="flex: 1; text-align: center; margin: 0 6px;">
                    <div style="font-size: 2em;"><?php echo esc_html($icon); ?></div>
                    <div style="font-size: 1em; margin-top: 6px;"><?php echo esc_html($option); ?></div>
                    <div style="background: #eee; border-radius: 6px; overflow: hidden; height: 12px; margin: 6px auto 0; max-width: 90%;">
                        <div style="background: #6B4F31; height: 100%; width: <?php echo esc_attr($percentage); ?>%;"></div>
                    </div>
                    <div style="margin-top: 4px; font-size: 0.95em; font-weight: 600;">
                        <?php echo intval($percentage); ?>%
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php

    // Mostrar valoraciones del usuario actual
    $current_user = wp_get_current_user();
    if ($current_user && ($current_user->ID == get_current_user_id() || current_user_can('manage_options'))) {
        foreach ($comments as $comment) {
            if ($comment->user_id == $current_user->ID) {
                echo '<div class="user-review-details" style="margin: 20px 0; padding: 15px; background: #f0f0f0; border-radius: 8px;">';
                echo '<h4 style="margin-bottom: 10px;">Tus Valoraciones para esta Reseña:</h4>';

                // Valoración general
                $general_rating = get_comment_meta($comment->comment_ID, 'rating', true);
                if ($general_rating) {
                    echo '<p>Valoración General: ' . esc_html($general_rating) . ' / 5</p>';
                }

                // Características específicas
                foreach ($ratings_array as $rating) {
                    $rating_key = sanitize_key($rating);
                    $rating_value = get_comment_meta($comment->comment_ID, 'cwr_rating_' . $rating_key, true);
                    if ($rating_value) {
                        echo '<p>' . esc_html($rating) . ': ' . esc_html($rating_value) . ' / 5</p>';
                    }
                }

                // Like/Dislike
                $like_dislike = get_comment_meta($comment->comment_ID, 'cwr_like_dislike', true);
                if ($like_dislike) {
                    echo '<p>' . esc_html($like_dislike_label) . ': ' . ($like_dislike === 'like' ? '👍' : '👎') . '</p>';
                }

                // Intención de Compra
                $purchase_intent = get_comment_meta($comment->comment_ID, 'cwr_purchase_intent', true);
                if ($purchase_intent) {
                    $option_text = array_search($purchase_intent, array_map('sanitize_key', $purchase_array)) ?: $purchase_intent;
                    echo '<p>Intención de Compra: ' . esc_html($option_text) . '</p>';
                }

                // Sugerencia
                $feature_suggestion = get_comment_meta($comment->comment_ID, 'cwr_feature_suggestion', true);
                if ($feature_suggestion) {
                    echo '<p>Sugerencia: ' . esc_html($feature_suggestion) . '</p>';
                }

                echo '</div>';
            }
        }
    }
}
