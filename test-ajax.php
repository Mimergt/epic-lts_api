<?php
// Función simple para probar AJAX (función independiente)
function leads_lts_test_ajax_function() {
    wp_send_json_success('✅ Test AJAX funcionando correctamente - ' . date('Y-m-d H:i:s'));
}

// Registrar función de test con nombre único
add_action('wp_ajax_leads_lts_test_connection', 'leads_lts_test_ajax_function');
