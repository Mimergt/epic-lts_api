<?php
/*
Plugin Name: Leads to LTS API
Description: Este plugin envía datos a LTS para Grupo Alega. Compatible con Contact Form 7.
Version: 2.2.4
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
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . '/some_magic.js', array('jquery'), '4.2.4', true);
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


// ==========================================
// COMPATIBILIDAD CON CONTACT FORM 7
// ==========================================

// Validación del campo de teléfono en Contact Form 7 (DESACTIVADA - BACKUP)
// add_filter('wpcf7_validate_tel', 'custom_cf7_phone_validation', 10, 2);
// add_filter('wpcf7_validate_tel*', 'custom_cf7_phone_validation', 10, 2);

// function custom_cf7_phone_validation($result, $tag)
// {
//     $name = $tag->name;
//     $value = isset($_POST[$name]) ? trim($_POST[$name]) : '';
//
//     if ($value) {
//         $tel_value = preg_replace('/\D/', '', $value); // Eliminar caracteres no numéricos
//
//         if (strlen($tel_value) !== 10) {
//             $result->invalidate($tag, 'Por favor ingrese un número con exactamente 10 dígitos');
//         }
//     }
//
//     return $result;
// }

// Hook para procesar el formulario después del envío
add_action('wpcf7_before_send_mail', 'send_cf7_data_to_lts_api');

function send_cf7_data_to_lts_api($contact_form)
{
    // Obtener el ID del formulario
    $form_id = $contact_form->id();

    // Obtener los datos enviados
    $submission = WPCF7_Submission::get_instance();

    if (!$submission) {
        return;
    }

    $posted_data = $submission->get_posted_data();

    // Buscar el campo de teléfono (puede tener diferentes nombres como tel-341, tel, phone, etc.)
    $phone = '';
    foreach ($posted_data as $key => $value) {
        if (strpos($key, 'tel') !== false || strpos($key, 'phone') !== false) {
            $phone = preg_replace('/\D/', '', $value); // Limpiar el teléfono
            break;
        }
    }

    // Si no hay teléfono, no enviar
    if (empty($phone)) {
        return;
    }

    // Buscar otros campos comunes
    $name = '';
    foreach ($posted_data as $key => $value) {
        if (strpos($key, 'nombre') !== false || strpos($key, 'name') !== false) {
            $name = sanitize_text_field($value);
            break;
        }
    }

    $email = '';
    foreach ($posted_data as $key => $value) {
        if (strpos($key, 'email') !== false || strpos($key, 'correo') !== false) {
            $email = sanitize_email($value);
            break;
        }
    }

    // Obtener datos de la URL
    $ref = isset($_SERVER['HTTP_REFERER']) ? esc_url($_SERVER['HTTP_REFERER']) : '';
    $userIP = $_SERVER['REMOTE_ADDR'];

    // Obtener parámetros de la URL
    $urlParams = array();
    parse_str(parse_url($ref, PHP_URL_QUERY), $urlParams);

    $phone_mappings = get_option('leads_lts_phone_mappings', array());
    $default_camphone = get_option('leads_lts_default_camphone', '5592509960');
    $phone_selector = get_option('leads_lts_phone_selector', '#call');

    // PRIORIDAD 1: Intentar obtener camPhone del formulario enviado
    $cPhone = '';
    foreach ($posted_data as $key => $value) {
        if (strpos($key, 'camPhone') !== false) {
            $cPhone = sanitize_text_field($value);
            break;
        }
    }

    // PRIORIDAD 2: Si no hay camPhone en el formulario, intentar con parámetro tel de URL
    if (empty($cPhone) && isset($urlParams['tel'])) {
        $tel_from_url = sanitize_text_field($urlParams['tel']);
        // Verificar si está en los mapeos
        if (isset($phone_mappings[$tel_from_url])) {
            $cPhone = $phone_mappings[$tel_from_url];
        }
    }

    // PRIORIDAD 3: Si aún no hay valor, usar default
    if (empty($cPhone)) {
        $cPhone = $default_camphone;
    }

    // Obtener campaña y otros datos de URL si existen
    $campaignid = isset($urlParams['campaignid']) ? $urlParams['campaignid'] : '';
    $keyword = isset($urlParams['keyword']) ? $urlParams['keyword'] : '';
    $adId = isset($urlParams['adid']) ? $urlParams['adid'] : '';

    // Preparar datos para enviar a la API
    $postData = array(
        'phone' => $phone,
        'phone_campaign' => $cPhone,
        'ip' => $userIP,
        'name_client' => $name,
        'lead_state' => '',
        'ivr_state' => '',
        'sale_date' => '',
        'channel' => 'Contact Form 7',
        'campaign' => $campaignid,
        'origin' => $ref,
        'form_name' => 'CF7-' . $form_id,
        'origin_ad_fb' => '',
        'origin_keyword_google' => $keyword,
        'talktime' => '',
        'client' => '',
    );

    // Enviar a la API
    $curl = curl_init();
    $token = '1|5xIXarWJw6IBROh10ofp9rQx6pRtNAIAG3qNU6vo762c1ae7';
    curl_setopt_array($curl, array(
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
        $log_entry = "=== CONTACT FORM 7 SUBMISSION ===\n";
        $log_entry .= "Form ID: " . $form_id . "\n";
        $log_entry .= "Response: " . $response . "\n";
        $log_entry .= "Phone: " . $postData['phone'] . "\n";
        $log_entry .= "Phone Campaign: " . $postData['phone_campaign'] . "\n";
        $log_entry .= "Name: " . $postData['name_client'] . "\n";
        $log_entry .= "Email: " . $email . "\n";
        $log_entry .= "User IP: " . $postData['ip'] . "\n";
        $log_entry .= "Origin: " . $postData['origin'] . "\n";
        $log_entry .= "Campaign ID: " . $postData['campaign'] . "\n";
        $log_entry .= "Keyword: " . $postData['origin_keyword_google'] . "\n";
        $log_entry .= "API URL: " . $url . "\n";
        $log_entry .= "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

        file_put_contents(plugin_dir_path(__FILE__) . 'log.txt', $log_entry, FILE_APPEND);
    }

    curl_close($curl);
}

// Comentar la redirección PHP, ahora se maneja con JavaScript
// add_filter('wpcf7_feedback_response', 'cf7_redirect_to_gracias', 10, 2);

// function cf7_redirect_to_gracias($response, $result)
// {
//     // Solo redirigir si el envío fue exitoso
//     if ($result['status'] === 'mail_sent') {
//         // Obtener la URL actual de la página desde donde se envió el formulario
//         $current_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url();

//         // Construir URL de redirección
//         $redirect_url = 'https://ivory-vulture-959197.hostingersite.com/gracias/?page_ref=' . urlencode($current_url);

//         // Agregar script de redirección a la respuesta
//         $response['redirect'] = $redirect_url;
//     }

//     return $response;
// }

// Agregar JavaScript para manejar la redirección después del envío de CF7
add_action('wp_footer', 'cf7_redirect_script');

function cf7_redirect_script()
{
    // Obtener las páginas habilitadas desde la configuración
    $enabled_pages = get_option('leads_lts_enabled_pages', array());

    // Si no hay páginas configuradas, no cargar el script
    if (empty($enabled_pages) || !is_array($enabled_pages)) {
        return;
    }

    // Verificar si estamos en una página habilitada
    $current_page_id = get_the_ID();
    if (!in_array($current_page_id, $enabled_pages)) {
        return;
    }

    ?>
    <script type="text/javascript">
        console.log('CF7 Redirect Script Loaded');

        // Función de redirección
        function redirectToGracias() {
            console.log('=== INICIANDO REDIRECCIÓN ===');

            // Obtener la URL actual sin parámetros para page_ref
            var currentUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;

            // Construir URL de redirección RELATIVA
            var redirectUrl = '/gracias/?page_ref=' + encodeURIComponent(currentUrl);

            console.log('CF7: Redirigiendo a:', redirectUrl);

            // Esperar 1 segundo antes de redirigir
            setTimeout(function () {
                console.log('CF7: Ejecutando redirección ahora...');
                location = redirectUrl;
            }, 1000);
        }

        // Listener para wpcf7mailsent (versiones más recientes)
        document.addEventListener('wpcf7mailsent', function (event) {
            console.log('CF7: Evento wpcf7mailsent detectado');
            redirectToGracias();
        }, false);

        // Listener para wpcf7submit (versiones antiguas)
        document.addEventListener('wpcf7submit', function (event) {
            console.log('CF7: Evento wpcf7submit detectado');
            console.log('CF7: event.detail =', event.detail);

            if (event.detail) {
                console.log('CF7: event.detail.status =', event.detail.status);
            }

            // Redirigir siempre, independientemente del status del mail
            // El envío a la API de LTS ocurre antes y es lo importante
            console.log('CF7: Ejecutando redirección (el dato ya se envió a la API)');
            redirectToGracias();
        }, false);

        // Listener adicional usando jQuery si está disponible
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('mailsent.wpcf7', function (event) {
                console.log('CF7: Evento mailsent.wpcf7 (jQuery) detectado');
                redirectToGracias();
            });

            jQuery(document).on('wpcf7mailsent', function (event) {
                console.log('CF7: Evento wpcf7mailsent (jQuery) detectado');
                redirectToGracias();
            });
        }

        console.log('CF7: Todos los event listeners configurados');
    </script>
    <?php
}
