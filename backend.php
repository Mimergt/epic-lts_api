<?php
// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Administrador del Plugin - Mapeo de Teléfonos
 */
class LeadsLTSAdmin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_leads_lts_save_phone_mapping', array($this, 'save_phone_mapping'));
        add_action('wp_ajax_leads_lts_delete_phone_mapping', array($this, 'delete_phone_mapping'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Agregar menú en el administrador
     */
    public function add_admin_menu() {
        add_menu_page(
            'Leads to LTS API',
            'Leads LTS',
            'manage_options',
            'leads-lts-admin',
            array($this, 'admin_page'),
            'dashicons-phone',
            30
        );
    }

    /**
     * Inicializar configuraciones del admin
     */
    public function admin_init() {
        register_setting('leads_lts_settings', 'leads_lts_phone_mappings');
        register_setting('leads_lts_settings', 'leads_lts_default_camphone');
    }

    /**
     * Encolar scripts del admin
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'toplevel_page_leads-lts-admin') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('leads-lts-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0', true);
        wp_localize_script('leads-lts-admin', 'leads_lts_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('leads_lts_nonce')
        ));
    }

    /**
     * Página del administrador
     */
    public function admin_page() {
        $phone_mappings = get_option('leads_lts_phone_mappings', array());
        $default_camphone = get_option('leads_lts_default_camphone', '');
        ?>
        <div class="wrap">
            <h1>Configuración Leads to LTS API</h1>
            
            <div class="card">
                <h2>CamPhone por Defecto</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('leads_lts_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">CamPhone Default</th>
                            <td>
                                <input type="text" name="leads_lts_default_camphone" value="<?php echo esc_attr($default_camphone); ?>" class="regular-text" placeholder="Ej: 5597186629" />
                                <p class="description">Este será el número asignado cuando no se encuentre mapeo específico.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Guardar CamPhone Default'); ?>
                </form>
            </div>

            <div class="card">
                <h2>Mapeo de Teléfonos</h2>
                <p>Aquí puedes mapear números de teléfono específicos a sus correspondientes CamPhone.</p>
                
                <!-- Formulario para añadir nuevo mapeo -->
                <div class="add-mapping-form">
                    <h3>Añadir Nuevo Mapeo</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Teléfono Original</th>
                            <td>
                                <input type="text" id="original_phone" class="regular-text" placeholder="Ej: tel:8008800810" />
                                <p class="description">El número tal como aparece en la página (incluye prefijos como 'tel:').</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">CamPhone Asignado</th>
                            <td>
                                <input type="text" id="mapped_camphone" class="regular-text" placeholder="Ej: 5597186629" />
                                <p class="description">El número que se asignará como CamPhone.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"></th>
                            <td>
                                <button type="button" id="add-mapping" class="button button-primary">Añadir Mapeo</button>
                                <button type="button" id="test-ajax" class="button button-secondary" style="margin-left: 10px;">Test Conexión</button>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Lista de mapeos existentes -->
                <div class="mappings-list">
                    <h3>Mapeos Existentes</h3>
                    <?php if (empty($phone_mappings)): ?>
                        <p>No hay mapeos configurados aún.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Teléfono Original</th>
                                    <th>CamPhone Asignado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="mappings-table-body">
                                <?php foreach ($phone_mappings as $original => $camphone): ?>
                                    <tr data-original="<?php echo esc_attr($original); ?>">
                                        <td><code><?php echo esc_html($original); ?></code></td>
                                        <td><code><?php echo esc_html($camphone); ?></code></td>
                                        <td>
                                            <button type="button" class="button delete-mapping" data-original="<?php echo esc_attr($original); ?>">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información sobre cómo usar -->
            <div class="card">
                <h2>Cómo usar</h2>
                <ol>
                    <li><strong>CamPhone Default:</strong> Se usa cuando no se encuentra un mapeo específico para un número.</li>
                    <li><strong>Mapeo de Teléfonos:</strong> Permite asignar números específicos a CamPhones particulares.</li>
                    <li><strong>Formato:</strong> El teléfono original debe incluir exactamente el formato que aparece en tu página (ej: 'tel:8008800810').</li>
                    <li><strong>Prioridad:</strong> Si existe un mapeo específico, se usa ese. Si no, se usa el CamPhone default.</li>
                </ol>
            </div>
        </div>

        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 20px 0;
            padding: 20px;
        }
        .add-mapping-form {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            background: #f9f9f9;
        }
        .mappings-list {
            margin-top: 20px;
        }
        #mappings-table-body tr:hover {
            background-color: #f5f5f5;
        }
        </style>
        <?php
    }

    /**
     * AJAX: Guardar mapeo de teléfono
     */
    public function save_phone_mapping() {
        // Headers para debugging
        header('Content-Type: application/json');
        
        try {
            // Verificar si es POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                wp_send_json_error('Método no permitido');
                exit;
            }
            
            // Verificar nonce
            $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
            if (empty($nonce) || !wp_verify_nonce($nonce, 'leads_lts_nonce')) {
                wp_send_json_error('Verificación de seguridad fallida');
                exit;
            }

            // Verificar permisos
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permisos insuficientes');
                exit;
            }

            // Obtener datos
            $original_phone = isset($_POST['original_phone']) ? sanitize_text_field($_POST['original_phone']) : '';
            $mapped_camphone = isset($_POST['mapped_camphone']) ? sanitize_text_field($_POST['mapped_camphone']) : '';

            // Quitar espacios
            $original_phone = preg_replace('/\s+/', '', $original_phone);
            $mapped_camphone = preg_replace('/\s+/', '', $mapped_camphone);

            if (empty($original_phone) || empty($mapped_camphone)) {
                wp_send_json_error('Ambos campos son obligatorios');
                exit;
            }

            // Guardar en base de datos
            $phone_mappings = get_option('leads_lts_phone_mappings', array());
            if (!is_array($phone_mappings)) {
                $phone_mappings = array();
            }
            
            $phone_mappings[$original_phone] = $mapped_camphone;
            
            $saved = update_option('leads_lts_phone_mappings', $phone_mappings);
            
            wp_send_json_success(array(
                'message' => 'Mapeo guardado exitosamente',
                'original' => $original_phone,
                'camphone' => $mapped_camphone,
                'saved' => $saved
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error interno: ' . $e->getMessage());
        } catch (Error $e) {
            wp_send_json_error('Error fatal: ' . $e->getMessage());
        }
        
        exit;
    }

    /**
     * AJAX: Eliminar mapeo de teléfono
     */
    public function delete_phone_mapping() {
        header('Content-Type: application/json');
        
        try {
            // Verificar si es POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                wp_send_json_error('Método no permitido');
                exit;
            }
            
            // Verificar nonce
            $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
            if (empty($nonce) || !wp_verify_nonce($nonce, 'leads_lts_nonce')) {
                wp_send_json_error('Verificación de seguridad fallida');
                exit;
            }

            // Verificar permisos
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permisos insuficientes');
                exit;
            }

            // Obtener datos
            $original_phone = isset($_POST['original_phone']) ? sanitize_text_field($_POST['original_phone']) : '';

            if (empty($original_phone)) {
                wp_send_json_error('Teléfono requerido');
                exit;
            }

            $phone_mappings = get_option('leads_lts_phone_mappings', array());
            if (!is_array($phone_mappings)) {
                $phone_mappings = array();
            }
            
            if (isset($phone_mappings[$original_phone])) {
                unset($phone_mappings[$original_phone]);
                update_option('leads_lts_phone_mappings', $phone_mappings);
                wp_send_json_success('Mapeo eliminado exitosamente');
            } else {
                wp_send_json_error('Mapeo no encontrado');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error interno: ' . $e->getMessage());
        } catch (Error $e) {
            wp_send_json_error('Error fatal: ' . $e->getMessage());
        }
        
        exit;
    }

    /**
     * Obtener CamPhone para un número específico
     */
    public static function get_camphone_for_number($original_phone) {
        $phone_mappings = get_option('leads_lts_phone_mappings', array());
        
        if (isset($phone_mappings[$original_phone])) {
            return $phone_mappings[$original_phone];
        }
        
        // Si no se encuentra mapeo específico, usar default
        return get_option('leads_lts_default_camphone', '');
    }
}

// Inicializar la clase admin solo si estamos en el admin
if (is_admin()) {
    function init_leads_lts_admin() {
        new LeadsLTSAdmin();
    }
    add_action('init', 'init_leads_lts_admin');
}
