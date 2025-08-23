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

    // Mostrar el template original de las reseñas (comentarios)
    comments_template();
}

function cwr_custom_reviews_visual_block() {
    if (!is_product()) return;

    global $product;
    $product_id = $product->get_id();

    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo\nProcesado\nVariedad\nRegión");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    $icons = [
        'acidez'      => '<span style="font-size:2em;">💧</span>',
        'dulzura'     => '<span style="font-size:2em;">🍬</span>',
        'cuerpo'      => '<span style="font-size:2em;">☕</span>',
        'intensidad'   => '<span style="font-size:2em;">🌱</span>',
        'variedad'    => '<span style="font-size:2em;">🌿</span>',
        'region'      => '<span style="font-size:2em;">📍</span>',
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
        <div class="attributes-icons" style="display: flex; justify-content: space-between; margin-bottom: 22px;">
            <?php foreach ($ratings_array as $rating): ?>
                <div style="text-align: center;">
                    <?php echo ($icons[sanitize_key($rating)] ?? '') ?>
                    <div style="font-size: 1em; margin-top: 4px;"><?php echo esc_html($rating); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="font-size: 2.5em; font-weight: bold; text-align: center; margin-bottom: 17px;">
            <?php echo esc_html($promedio_general); ?> / 5
        </div>

        <?php foreach ($ratings_array as $rating):
            $rating_key = sanitize_key($rating);
            $average = (!empty($count_ratings[$rating_key]) && $count_ratings[$rating_key] > 0) ? round($total_ratings[$rating_key] / $count_ratings[$rating_key], 1) : 0;
            $percentage = ($average / 5) * 100;
            ?>
            <div style="margin-bottom: 10px;">
                <div style="width: 100%; background: #eee; border-radius: 6px; overflow: hidden; height: 14px; margin-bottom: 4px;">
                    <div style="background: #6B4F31; height: 100%; width: <?php echo esc_attr($percentage); ?>%;"></div>
                </div>
                <div style="width: 55px; text-align: right; font-size: 1em;">
                    <?php echo intval($percentage); ?>%
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
