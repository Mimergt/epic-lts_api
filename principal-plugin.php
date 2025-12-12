<?php
/*
Plugin Name: Leads to LTS API
Description: Este plugin envía datos a LTS.
Version: 1.0.1
Author: Mimer - EPIC.GT
*/

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir backend si estamos en admin
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'backend.php';
}

function add_custom_script()
{
    // Obtener la IP del cliente
    $userIP = $_SERVER['REMOTE_ADDR'];

    // Obtener mapeos de teléfonos desde la base de datos
    $phone_mappings = get_option('leads_lts_phone_mappings', array());
    $default_camphone = get_option('leads_lts_default_camphone', '5592509960');
    $phone_selector = get_option('leads_lts_phone_selector', '#call');
    $enable_debug = get_option('leads_lts_enable_debug', '0');

    // Encolar el script y pasar la IP al frontend
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . '/some_magic.js', array('jquery'), '4.2.2', true);
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



add_action('elementor_pro/forms/validation/tel', function ($field, $record, $ajax_handler) {
    // Custom validation
    if (empty($field['value'])) {
        return;
    }

    $tel_value = preg_replace('/\D/', '', $field['value']); // Eliminar caracteres no numéricos

    if (strlen($tel_value) !== 10) {
        $ajax_handler->add_error($field['id'], 'Por favor ingrese un número con exactamente 10 dígitos');
    } else {

        function applts_mx_produccion($record, $ajax_handler)
        {
            $form_settings = $record->get('form_settings');
            $form_id = $form_settings['form_id'];
            if ($form_id !== 'formdesk11') {
                return;
            }

            // get fields using method in Form_Record class
            $fields = $record->get('fields');
            // get keyword from the phone
            $phoneKey = $fields['tel']['value'];
            $campaignid = $fields['campid']['value'];
            $adgroupid = $fields['adgroupid']['value'];
            $keyword = $fields['keyword']['value'];
            $adId = $fields['adId']['value'];
            $ref = $fields['ref']['value'];
            $cPhone = $fields['camPhone']['value'];
            $theName = $fields['nombre']['value'];
            $theEmail = $fields['email']['value'];
            $userIP = $_SERVER['REMOTE_ADDR']; // Obtener la IP del cliente
            $CN = $fields['formid']['value'];

            // Send POST request
            $postData = array(
                'phone' => $phoneKey,
                'phone_campaign' => $cPhone,
                'ip' => $userIP,
                'name_client' => $theName,
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


            $curl = curl_init();
            $token = '1|5xIXarWJw6IBROh10ofp9rQx6pRtNAIAG3qNU6vo762c1ae7';
            curl_setopt_array($curl, array(
                #                CURLOPT_URL => 'https://qa-lts.exponentedigital.mx/api/v1/leads/create',
                CURLOPT_URL => 'https://lts.exponentedigital.mx/api/v1/leads/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

            // Log de API (si está activado)
            if (get_option('leads_lts_enable_api_log', '0') == '1') {
                file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', "Response: " . $response . "\nPhone Campaign: " . $postData['phone_campaign'] . "\nCampaign ID: " . $postData['campaignid'] . "\nOrigen URL: " . $postData['referer'] . "\nEnd URL: " . $url . "\nName: " . $postData['name'] . "\nEmail: " . $postData['email'] . "\nUser IP: " . $postData['ip'] . "\nFormulario: " . $postData['CN'] . "\nPhone: " . $postData['phone'] . "\n\n", FILE_APPEND);
            }
            curl_close($curl);

            // Set redirect URL	
            $redirect_url = site_url('/gracias/?page_ref=' . $ref);
            // o $redirect_url = home_url('/gracias/?page_ref=' . $ref);
            $ajax_handler->add_response_data('redirect_url', $redirect_url);


        }

        add_action('elementor_pro/forms/validation', 'applts_mx_produccion', 10, 2);

    }
}, 9, 3);



// Función para obtener y almacenar el valor de $ref desde el parámetro de la URL
function guardar_ref_desde_url_shortcode_function()
{
    // Obtiene el valor del parámetro page_ref de la URL si está presente
    $ref = isset($_GET['page_ref']) ? esc_url($_GET['page_ref']) : '';

    // Devuelve el valor de $ref
    return $ref;
}
add_shortcode('guardar_ref_desde_url', 'guardar_ref_desde_url_shortcode_function');

// Función para mostrar el valor de $ref almacenado
function mostrar_ref_shortcode_function()
{
    // Obtener el valor de $ref guardado
    $ref = do_shortcode('[guardar_ref_desde_url]');

    // Mostrar el valor de $ref
    return $ref;
}
add_shortcode('obtener_ref', 'mostrar_ref_shortcode_function');








// Función para determinar el tipo de dispositivo
function detect_device()
{
    $is_mobile = wp_is_mobile();
    if ($is_mobile) {
        if (stristr($_SERVER['HTTP_USER_AGENT'], 'android')) {
            return 'Mobile (Android)';
        } elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'iphone') || stristr($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
            return 'Mobile (iOS)';
        } else {
            return 'Mobile';
        }
    } else {
        return 'Computadora';
    }
}

// Shortcode para mostrar el tipo de dispositivo
function device_shortcode()
{
    return detect_device();
}
add_shortcode('device', 'device_shortcode');
