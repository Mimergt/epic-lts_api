<?php
// Archivo de debug para probar las funciones AJAX
// Borrar después de resolver el problema

// Función simple para probar AJAX
function leads_lts_test_ajax() {
    wp_send_json_success('Test AJAX funcionando correctamente');
}

// Registrar función de test
add_action('wp_ajax_leads_lts_test', 'leads_lts_test_ajax');

// Debug log function
function leads_lts_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LEADS LTS DEBUG: ' . $message);
    }
}

// Log cuando se cargan las acciones AJAX
add_action('wp_ajax_save_phone_mapping', function() {
    leads_lts_debug_log('save_phone_mapping action called');
});

add_action('wp_ajax_delete_phone_mapping', function() {
    leads_lts_debug_log('delete_phone_mapping action called');
});
