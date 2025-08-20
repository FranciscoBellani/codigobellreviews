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

    add_settings_section(
        'cwr_main_section',
        'Valoraciones Específicas',
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
}

// Callback para el campo de texto
function cwr_ratings_field_callback() {
    $ratings = get_option('cwr_specific_ratings', "Calidad\nDiseño\nValor");
    echo '<textarea name="cwr_specific_ratings" rows="5" cols="50">' . esc_textarea($ratings) . '</textarea>';
    echo '<p>Ejemplo: Calidad<br>Diseño<br>Valor</p>';
}

// Sanitiza la entrada
function cwr_sanitize_ratings($input) {
    return sanitize_textarea_field($input);
}