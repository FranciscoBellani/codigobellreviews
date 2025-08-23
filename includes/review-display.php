<?php
if (!defined('ABSPATH')) exit;

// Hook para mostrar las estadísticas y reviews custom en la pestaña "Reviews"
add_action('woocommerce_review_before', 'cwr_custom_reviews_visual_block', 1);

function cwr_custom_reviews_visual_block() {
    if (!is_product()) return;

    global $product;
    $product_id = $product->get_id();

    // Configuración de los atributos
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo\nProcesado\nVariedad\nRegión");
    $ratings_array = array_filter(array_map('trim', explode("\n", $ratings)));

    // Iconos asociados (puedes remplazar SVGs por tu preferido)
    $icons = [
        'acidez'      => '<span style="font-size:2em;">💧</span>',        // Demo SVG/Iconos
        'dulzura'     => '<span style="font-size:2em;">🍬</span>',
        'cuerpo'      => '<span style="font-size:2em;">☕</span>',
        'procesado'   => '<span style="font-size:2em;">🌱</span>',
        'variedad'    => '<span style="font-size:2em;">🌿</span>',
        'region'      => '<span style="font-size:2em;">📍</span>',
    ];

    // Preparar sumas/medias
    $total_ratings = [];
    $count_ratings = [];
    $sum_general = 0;
    $count_general = 0;

    // Obtener todos los comentarios aprobados tipo review
    $args = array(
        'post_id' => $product_id,
        'status' => 'approve',
        'type' => 'review',
    );
    $comments = get_comments($args);

    foreach ($comments as $comment) {
        // General rating WooCommerce
        $general_rating = get_comment_meta($comment->comment_ID, 'rating', true);
        if ($general_rating && is_numeric($general_rating)) {
            $sum_general += $general_rating;
            $count_general++;
        }
        // Ratings por atributo
        foreach ($ratings_array as $rating) {
            $rating_key = sanitize_key($rating);
            $rating_value = get_comment_meta($comment->comment_ID, 'cwr_rating_' . $rating_key, true);
            if ($rating_value && is_numeric($rating_value)) {
                $total_ratings[$rating_key] = isset($total_ratings[$rating_key]) ? $total_ratings[$rating_key] + $rating_value : $rating_value;
                $count_ratings[$rating_key] = isset($count_ratings[$rating_key]) ? $count_ratings[$rating_key] + 1 : 1;
            }
        }
    }
    $promedio_general = $count_general > 0 ? round($sum_general / $count_general,1) : 0;

    // Renderizado visual
    ?>
    <div class="custom-review-stats" style="padding: 24px 12px; background: #f8f3ef; border-radius:16px ; max-width: 520px; margin:30px auto;">
        <!-- Iconos de atributos -->
        <div class="attributes-icons" style="display: flex; justify-content: space-between; margin-bottom: 22px;">
            <?php foreach ($ratings_array as $rating): ?>
                <div style="text-align: center;">
                    <?php echo ($icons[sanitize_key($rating)] ?? ''); ?>
                    <div style="font-size: 1em; margin-top: 4px;"><?php echo esc_html($rating); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Promedio general -->
        <div style="font-size: 2.5em; font-weight: bold; text-align: center; margin-bottom: 17px;">
            <?php echo esc_html($promedio_general); ?> / 5
        </div>

        <!-- Barras de porcentaje de cada atributo -->
        <?php foreach ($ratings_array as $rating):
            $rating_key = sanitize_key($rating);
            $average = ($count_ratings[$rating_key] ?? 0) > 0 ? round($total_ratings[$rating_key] / $count_ratings[$rating_key], 1) : 0;
            $percentage = ($average / 5) * 100;
            ?>
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="flex:1; background: #eee; border-radius:6px; overflow:hidden; height:14px; margin-right:10px;">
                    <div style="background:#6B4F31; height:100%; width:<?php echo esc_attr($percentage); ?>%;"></div>
                </div>
                <div style="width:50px; text-align:right; font-size:1em;"><?php echo intval($percentage); ?>%</div>
            </div>
        <?php endforeach; ?>

        <!-- Reseñas individuales -->
        <div style="margin-top: 25px;">
            <strong style="font-size:1.15em; margin-bottom:10px; display:block;">Reviews:</strong>
            <?php foreach ($comments as $comment): ?>
            <div style="margin-bottom:25px;">
                <div style="display: flex; align-items: center;">
                    <div style="margin-right:12px;"><?php echo get_avatar($comment->comment_author_email, 50); ?></div>
                    <div>
                        <div style="font-weight:bold;"><?php echo esc_html($comment->comment_author); ?></div>
                        <div style="color:#888;"><?php echo date('M d, Y', strtotime($comment->comment_date)); ?></div>
                    </div>
                </div>
                <div style="margin-top:7px;"><?php echo esc_html($comment->comment_content); ?></div>
                <div style="margin-top:3px; color:#e6a600;">
                    <?php
                    $general_rating = get_comment_meta($comment->comment_ID, 'rating', true);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= intval($general_rating) ? '★' : '☆';
                    }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
