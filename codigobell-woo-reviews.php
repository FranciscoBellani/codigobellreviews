<?php
/**
 * Plugin Name: Codigobell Woo Reviews
 * Plugin URI: https://codigobell.com
 * Description: Plugin para reseñas personalizadas en WooCommerce con valoraciones detalladas y gamificación extensible.
 * Version: 1.0
 * Author: Tu Nombre
 * Author URI: https://codigobell.com
 * License: GPL-2.0+
 * Text Domain: codigobell-woo-reviews
 */

if (!defined('ABSPATH')) {
    exit; // Evita acceso directo
}

// Verifica si WooCommerce está activo
function cwr_check_woocommerce() {
    if (class_exists('WooCommerce') || function_exists('WC')) {
        error_log('Codigobell Woo Reviews: WooCommerce detectado');
        return true;
    }
    error_log('Codigobell Woo Reviews: WooCommerce NO detectado');
    add_action('admin_notices', function() {
        echo '<div class="error"><p>Codigobell Woo Reviews requiere WooCommerce para funcionar.</p></div>';
    });
    return false;
}

// Carga el plugin en plugins_loaded
add_action('plugins_loaded', function() {
    if (cwr_check_woocommerce()) {
        require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
        require_once plugin_dir_path(__FILE__) . 'includes/review-form.php';
    }
});