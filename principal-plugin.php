<?php
/*
Plugin Name: Leads to LTS API
Description: Plugin UIN para enviar leads a LTS, mapear camPhone y registrar errores de formularios Elementor.
Version: 4.3.4
Author: Mimer - EPIC.GT
*/

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('LEADS_LTS_API_LOG_FILE')) {
    define('LEADS_LTS_API_LOG_FILE', 'log.txt');
}

if (!defined('LEADS_LTS_FORM_ERROR_LOG_FILE')) {
    define('LEADS_LTS_FORM_ERROR_LOG_FILE', 'elementor_form_errors_log.txt');
}

if (!defined('LEADS_LTS_API_ENDPOINT')) {
    define('LEADS_LTS_API_ENDPOINT', 'https://lts.exponentedigital.mx/api/v1/leads/create');
}

if (!defined('LEADS_LTS_FALLBACK_TOKEN')) {
    define('LEADS_LTS_FALLBACK_TOKEN', '1|5xIXarWJw6IBROh10ofp9rQx6pRtNAIAG3qNU6vo762c1ae7');
}

// Incluir backend si estamos en admin
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'backend.php';
}

function add_custom_script() {
    // Obtener la IP del cliente
    $userIP = leads_lts_get_client_ip();
    
    // Obtener mapeos de teléfonos desde la base de datos
    $phone_mappings = get_option('leads_lts_phone_mappings', array());
    $default_camphone = get_option('leads_lts_default_camphone', '5592509960');
    $phone_selector = get_option('leads_lts_phone_selector', '#call');
    $enable_debug = get_option('leads_lts_enable_debug', '0');

    // Encolar el script y pasar la IP al frontend
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . '/some_magic.js', array('jquery'), '4.3.4', true);
    wp_localize_script('custom-script', 'my_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'user_ip' => $userIP, // Añadir la IP del usuario
        'phone_mappings' => $phone_mappings, // Mapeos de teléfonos
        'default_camphone' => $default_camphone, // CamPhone por defecto
        'phone_selector' => $phone_selector, // Selector del elemento teléfono
        'enable_debug' => $enable_debug // Activar/desactivar debug
    ));
}
add_action('wp_enqueue_scripts', 'add_custom_script');

function leads_lts_get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded_ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($forwarded_ips[0]);
    }

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }

    return '0.0.0.0';
}

function leads_lts_get_field_value($fields, $key) {
    if (!isset($fields[$key]['value'])) {
        return '';
    }

    return sanitize_text_field($fields[$key]['value']);
}

function leads_lts_write_line($filename, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $line = '[' . $timestamp . '] ' . $message . PHP_EOL;
    file_put_contents(plugin_dir_path(__FILE__) . $filename, $line, FILE_APPEND | LOCK_EX);
}

function leads_lts_log_api($message) {
    if (get_option('leads_lts_enable_api_log', '0') !== '1') {
        return;
    }

    leads_lts_write_line(LEADS_LTS_API_LOG_FILE, $message);
}

function leads_lts_log_form_error($message) {
    leads_lts_write_line(LEADS_LTS_FORM_ERROR_LOG_FILE, $message);
}

function leads_lts_add_form_error_message($ajax_handler, $message) {
    if (method_exists($ajax_handler, 'add_error_message')) {
        $ajax_handler->add_error_message($message);
    }
}



add_action('elementor_pro/forms/validation/tel', function($field, $record, $ajax_handler) {
    // Custom validation
    if (empty($field['value'])) {
        return;
    }
    
    $tel_value = preg_replace('/\D/', '', $field['value']); // Eliminar caracteres no numéricos
    
    if (strlen($tel_value) !== 10) {
        $form_settings = $record->get('form_settings');
        $form_id = isset($form_settings['form_id']) ? $form_settings['form_id'] : 'sin_form_id';
        leads_lts_log_form_error('Error validacion telefono | form_id=' . $form_id . ' | field_id=' . $field['id'] . ' | valor=' . sanitize_text_field($field['value']));
        $ajax_handler->add_error($field['id'], 'Por favor ingrese un número con exactamente 10 dígitos');
    }
}, 9, 3);

function applts_mx_produccion($record, $ajax_handler) {
    $form_settings = $record->get('form_settings');
    $form_id = isset($form_settings['form_id']) ? $form_settings['form_id'] : '';

    if ($form_id !== 'formdesk11') {
        return;
    }

    $fields = $record->get('fields');

    $phoneKey = leads_lts_get_field_value($fields, 'tel');
    $campaignid = leads_lts_get_field_value($fields, 'campid');
    $keyword = leads_lts_get_field_value($fields, 'keyword');
    $ref = leads_lts_get_field_value($fields, 'ref');
    $cPhone = leads_lts_get_field_value($fields, 'camPhone');
    $theName = leads_lts_get_field_value($fields, 'nombre');
    $theEmail = leads_lts_get_field_value($fields, 'email');
    $CN = leads_lts_get_field_value($fields, 'formid');
    $userIP = leads_lts_get_client_ip();

    // Si campaignid, keyword o adgroupid llegan vacíos desde el campo oculto,
    // intentar extraerlos de los parámetros de la URL que viaja en el campo ref.
    if (!empty($ref)) {
        $ref_parts = parse_url($ref);
        if (!empty($ref_parts['query'])) {
            parse_str($ref_parts['query'], $url_params);
            if (empty($campaignid) && !empty($url_params['campaignid'])) {
                $campaignid = sanitize_text_field($url_params['campaignid']);
                leads_lts_log_form_error('[INFO] campid no llegó en campo oculto del formulario; recuperado de URL ref correctamente: ' . $campaignid . ' | form_id=' . $form_id . ' | Accion: ninguna, lead se procesa normal.');
            }
            if (empty($keyword) && !empty($url_params['keyword'])) {
                $keyword = sanitize_text_field($url_params['keyword']);
            }
        }
    }

    // Solo phone y camPhone son estrictamente requeridos para procesar el lead.
    if (empty($phoneKey) || empty($cPhone)) {
        leads_lts_log_form_error(
            'Formulario incompleto - faltan campos criticos | form_id=' . $form_id .
            ' | phone=' . $phoneKey .
            ' | camPhone=' . $cPhone .
            ' | campaign=' . $campaignid .
            ' | ref=' . $ref
        );
        leads_lts_add_form_error_message($ajax_handler, 'No se pudo procesar el formulario. Faltan datos requeridos.');
        return;
    }

    $postData = array(
        'phone' => $phoneKey,
        'phone_campaign' => $cPhone,
        'ip' => $userIP,
        'name_client' => $theName,
        'email_client' => $theEmail,
        'lead_state' => '',
        'ivr_state' => '',
        'sale_date' => '',
        'channel' => '',
        'campaign' => $campaignid,
        'origin' => $ref,
        'form_name' => $CN,
        'origin_ad_fb' => '',
        'origin_keyword_google' => $keyword,
        'talktime' => '',
        'client' => '',
    );

    $token = get_option('leads_lts_api_token', '');
    if (empty($token)) {
        $token = LEADS_LTS_FALLBACK_TOKEN;
        leads_lts_log_form_error('Token API no configurado en admin. Usando token fallback para form_id=' . $form_id);
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => LEADS_LTS_API_ENDPOINT,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => wp_json_encode($postData),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ),
    ));

    $response = curl_exec($curl);
    $curl_error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
    curl_close($curl);

    if (!empty($curl_error)) {
        leads_lts_log_form_error('Error cURL envio LTS | form_id=' . $form_id . ' | error=' . $curl_error . ' | phone=' . $phoneKey);
        leads_lts_add_form_error_message($ajax_handler, 'No se pudo enviar la información en este momento. Intenta nuevamente.');
        return;
    }

    if ((int) $http_code >= 400) {
        leads_lts_log_form_error('Error API LTS | form_id=' . $form_id . ' | http_code=' . $http_code . ' | response=' . sanitize_text_field((string) $response));
        leads_lts_add_form_error_message($ajax_handler, 'Hubo un problema al procesar tu información.');
        return;
    }

    leads_lts_log_api(
        'Response: ' . sanitize_text_field((string) $response) .
        ' | Phone Campaign: ' . $postData['phone_campaign'] .
        ' | Campaign ID: ' . $postData['campaign'] .
        ' | Origen URL: ' . $postData['origin'] .
        ' | End URL: ' . $url .
        ' | Name: ' . $postData['name_client'] .
        ' | Email: ' . $postData['email_client'] .
        ' | User IP: ' . $postData['ip'] .
        ' | Formulario: ' . $postData['form_name'] .
        ' | Phone: ' . $postData['phone']
    );

    $redirect_url = site_url('/gracias/?page_ref=' . rawurlencode($ref));
    $ajax_handler->add_response_data('redirect_url', $redirect_url);
}

add_action('elementor_pro/forms/validation', 'applts_mx_produccion', 10, 2);



// Función para obtener y almacenar el valor de $ref desde el parámetro de la URL
function guardar_ref_desde_url_shortcode_function() {
    // Obtiene el valor del parámetro page_ref de la URL si está presente
    $ref = isset($_GET['page_ref']) ? esc_url($_GET['page_ref']) : '';

    // Devuelve el valor de $ref
    return $ref;
}
add_shortcode('guardar_ref_desde_url', 'guardar_ref_desde_url_shortcode_function');

// Función para mostrar el valor de $ref almacenado
function mostrar_ref_shortcode_function() {
    // Obtener el valor de $ref guardado
    $ref = do_shortcode('[guardar_ref_desde_url]');

    // Mostrar el valor de $ref
    return $ref;
}
add_shortcode('obtener_ref', 'mostrar_ref_shortcode_function');








// Función para determinar el tipo de dispositivo
function detect_device() {
    $is_mobile = wp_is_mobile();
    if ( $is_mobile ) {
        if ( stristr( $_SERVER['HTTP_USER_AGENT'], 'android' ) ) {
            return 'Mobile (Android)';
        } elseif ( stristr( $_SERVER['HTTP_USER_AGENT'], 'iphone' ) || stristr( $_SERVER['HTTP_USER_AGENT'], 'ipad' ) ) {
            return 'Mobile (iOS)';
        } else {
            return 'Mobile';
        }
    } else {
        return 'Computadora';
    }
}

// Shortcode para mostrar el tipo de dispositivo
function device_shortcode() {
    return detect_device();
}
add_shortcode( 'device', 'device_shortcode' );
