<?php
if (!defined('ABSPATH')) exit;

// Registra la página de configuración como menú de nivel superior
add_action('admin_menu', 'cwr_add_admin_menu');
function cwr_add_admin_menu() {
    add_menu_page(
        'Codigobell Woo Reviews Settings',
        'CodBell Reviews',
        'manage_options',
        'codigobell-reviews-settings',
        'cwr_settings_page',
        'dashicons-star-filled',
        80
    );
}

// Renderiza la página de configuración
function cwr_settings_page() {
    ?>
    <div class="wrap">
        <h1>Codigobell Woo Reviews Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cwr_options_group');
            do_settings_sections('codigobell-reviews-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registra los ajustes
add_action('admin_init', 'cwr_register_settings');
function cwr_register_settings() {
    register_setting('cwr_options_group', 'cwr_specific_ratings', 'cwr_sanitize_ratings');
    register_setting('cwr_options_group', 'cwr_like_dislike_label', 'sanitize_text_field'); // Nuevo: Etiqueta para Like/Dislike
    register_setting('cwr_options_group', 'cwr_purchase_intent_options', 'cwr_sanitize_ratings'); // Nuevo: Opciones para Purchase Intent

    add_settings_section(
        'cwr_main_section',
        'Valoraciones Específicas (Estrellas)',
        null,
        'codigobell-reviews-settings'
    );

    add_settings_field(
        'cwr_ratings_field',
        'Lista de Valoraciones (una por línea)',
        'cwr_ratings_field_callback',
        'codigobell-reviews-settings',
        'cwr_main_section'
    );

    // Nueva sección para Me Gusta/No Me Gusta
    add_settings_section(
        'cwr_like_dislike_section',
        'Valoración Me Gusta / No Me Gusta',
        null,
        'codigobell-reviews-settings'
    );

    add_settings_field(
        'cwr_like_dislike_label_field',
        'Etiqueta para esta sección',
        'cwr_like_dislike_label_callback',
        'codigobell-reviews-settings',
        'cwr_like_dislike_section'
    );

    // Nueva sección para Compraste/Lo Voy a Comprar
    add_settings_section(
        'cwr_purchase_intent_section',
        'Valoración de Intención de Compra',
        null,
        'codigobell-reviews-settings'
    );

    add_settings_field(
        'cwr_purchase_intent_options_field',
        'Opciones (una por línea)',
        'cwr_purchase_intent_options_callback',
        'codigobell-reviews-settings',
        'cwr_purchase_intent_section'
    );
}

// Callback para el campo de texto original
function cwr_ratings_field_callback() {
    $ratings = get_option('cwr_specific_ratings', "Acidez\nDulzura\nCuerpo");
    echo '<textarea name="cwr_specific_ratings" rows="5" cols="50">' . esc_textarea($ratings) . '</textarea>';
    echo '<p>Ejemplo: Acidez<br>Dulzura<br>Cuerpo</p>';
}

// Nuevo callback para Like/Dislike label
function cwr_like_dislike_label_callback() {
    $label = get_option('cwr_like_dislike_label', "Me Gusta / No Me Gusta");
    echo '<input type="text" name="cwr_like_dislike_label" value="' . esc_attr($label) . '" size="50">';
    echo '<p>Ejemplo: Me Gusta o No Me Gusta</p>';
}

// Nuevo callback para Purchase Intent options
function cwr_purchase_intent_options_callback() {
    $options = get_option('cwr_purchase_intent_options', "Lo compré\nLo voy a comprar");
    echo '<textarea name="cwr_purchase_intent_options" rows="3" cols="50">' . esc_textarea($options) . '</textarea>';
    echo '<p>Ejemplo: Lo compré<br>Lo voy a comprar</p>';
}

// Sanitiza la entrada
function cwr_sanitize_ratings($input) {
    return sanitize_textarea_field($input);
}