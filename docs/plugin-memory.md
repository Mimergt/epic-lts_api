# Plugin Memory - Leads to LTS API (UIN)

Este documento es la memoria viva del plugin. Aqui iremos registrando como funciona, sus dependencias, cambios y decisiones tecnicas para mantener trazabilidad.

## 1. Estado actual

- Rama de trabajo: `uin`
- Version actual del plugin: `4.3.1`
- Archivo principal: `principal-plugin.php`
- Admin/configuracion: `backend.php` + `admin.js`
- Frontend/magia de formularios: `some_magic.js`

## 2. Objetivo del plugin

El plugin integra formularios Elementor Pro con la API de LTS para crear leads y redirigir al usuario a una pagina de gracias.

Tambien:
- Mapea telefonos origen a un `camPhone` configurable.
- Guarda logs de llamadas API (opcional).
- Guarda logs separados de errores de formulario Elementor.

## 3. Arquitectura y flujo general

### 3.1 Carga principal

1. WordPress carga `principal-plugin.php`.
2. Si estamos en admin, se incluye `backend.php`.
3. En frontend se encola `some_magic.js` y se inyectan variables via `wp_localize_script`.

### 3.2 Flujo frontend de formulario

1. `some_magic.js` toma el telefono del selector configurable (`leads_lts_phone_selector`, default `#call`).
2. Busca mapeo en `leads_lts_phone_mappings`.
3. Si no hay mapeo, usa `leads_lts_default_camphone`.
4. Asigna valores ocultos del formulario Elementor:
   - `form_fields[camPhone]`
   - `form-field-ref` (URL actual)
5. Aplica validacion de telefono (max 10 y solo numeros).

### 3.3 Flujo backend de envio a LTS

Hook principal:
- `elementor_pro/forms/validation` -> `applts_mx_produccion`

Proceso:
1. Filtra por `form_id === formdesk11`.
2. Lee campos (`tel`, `campid`, `keyword`, `ref`, `camPhone`, `nombre`, `email`, `formid`).
3. Valida que existan campos minimos requeridos.
4. Construye payload y hace POST JSON a:
   - `https://lts.exponentedigital.mx/api/v1/leads/create`
5. Maneja errores de cURL y HTTP >= 400.
6. Si todo sale bien, responde con redirect:
   - `/gracias/?page_ref=<ref>`

## 4. Dependencias

## 4.1 Dependencias WordPress

- Hooks/actions de WordPress.
- Options API (`get_option`, `update_option`).
- Shortcodes (`add_shortcode`).
- AJAX admin (`wp_ajax_*`).
- Capacidades admin (`manage_options`).

### 4.2 Dependencias de plugin/entorno

- Elementor Pro (Form widget + hooks de validacion).
- Extension cURL en PHP.
- jQuery en frontend y admin.

### 4.3 Dependencias externas

- API LTS: `lts.exponentedigital.mx`
- IP publica en frontend: `api64.ipify.org`

## 5. Opciones/configuracion guardadas (wp_options)

- `leads_lts_phone_mappings` (array telefono -> camPhone)
- `leads_lts_default_camphone` (string)
- `leads_lts_phone_selector` (string CSS selector)
- `leads_lts_enable_debug` (`0`/`1`)
- `leads_lts_enable_api_log` (`0`/`1`)
- `leads_lts_api_token` (string; si vacio usa fallback)

## 6. Logs y almacenamiento

### 6.1 Log API

- Archivo: `log.txt`
- Uso: solo cuando `leads_lts_enable_api_log = 1`
- Contiene respuesta API y metadata del lead.

### 6.2 Log errores formulario Elementor

- Archivo: `elementor_form_errors_log.txt`
- Uso: siempre para errores de validacion/formulario/envio
- Ejemplos:
  - Telefono invalido
  - Formulario incompleto
  - Error cURL
  - Error HTTP de API
  - Uso de token fallback

### 6.3 Limpieza de logs

Desde admin (boton limpiar log) se eliminan:
- `log.txt`
- `leads_lts_log.txt` (legacy debug)
- `elementor_form_errors_log.txt`

## 7. Pantallas de admin

Menu principal: `Leads LTS`

Subpaginas:
- Configuracion general + mapeos
- Ver Log (API)
- Errores Formularios (nuevo)

## 8. Seguridad y consideraciones

- Nonce y capability checks en acciones AJAX.
- Sanitizacion de campos al leer datos.
- Timeout de cURL en 20s para evitar cuelgues.
- Token configurable en admin.

Riesgo conocido:
- Existe un token fallback en codigo para compatibilidad. Recomendado definir siempre `leads_lts_api_token` en produccion y rotarlo periodicamente.

## 9. Historial de cambios relevantes

### v4.3.0 (UIN)

- Cambio de descripcion del plugin a enfoque UIN.
- Refactor del envio a LTS para evitar duplicidad de hooks.
- Mejor manejo de errores cURL/HTTP y mensajes a formulario.
- Nuevo log separado de errores de formulario Elementor.
- Nueva pantalla admin: `Errores Formularios`.
- Nuevo campo admin para token API.

### v4.3.1 (UIN)

- Se crea este archivo de memoria tecnica del plugin para documentacion continua.
- Se actualiza version para control de despliegue y cache de script frontend.

## 10. Checklist para pruebas en sitio

1. Activar plugin y verificar version 4.3.1.
2. Revisar selector de telefono configurado.
3. Configurar token API en admin.
4. Enviar formulario valido y confirmar:
   - lead en LTS
   - redirect a `/gracias/`
5. Forzar error (ej. telefono invalido) y verificar entrada en `elementor_form_errors_log.txt`.
6. Verificar que el visor de `Errores Formularios` muestre el evento.

## 11. Convencion de documentacion futura

En cada ajuste nuevo registrar:
- Version
- Fecha
- Objetivo del cambio
- Archivos tocados
- Riesgos
- Resultado de prueba

---

Documento vivo. Ultima actualizacion: 2026-06-24.
