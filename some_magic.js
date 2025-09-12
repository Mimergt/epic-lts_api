jQuery(function($) {
  var camPhone = $('#call').attr('href');
  var url = new URL(window.location.href);
  var referer = window.location.origin + window.location.pathname;

  $('.elementor-field-type-hidden')
    .find('#form-field-camPhone').val(camPhone).end()
    .find('#form-field-ref').val(window.location.href).end();

  // Usar mapeos desde la base de datos (configurados en el admin)
  var phoneMap = my_ajax_object.phone_mappings || {};
  var defaultCamPhone = my_ajax_object.default_camphone || '5592509960';

  // Set the value of camPhone input field to the corresponding value in the phoneMap
  $('input[name="form_fields[camPhone]"]').val(function() {
    var mappedValue = phoneMap[camPhone] || defaultCamPhone; // Usar mapeo o default
    console.log('Original Phone:', camPhone);
    console.log('Mapped Phone:', mappedValue);
    console.log('Ref:', referer);
    return mappedValue;
  });
});







jQuery(document).ready(function() {
    // Función para limitar el campo de teléfono a 10 caracteres
    function limitarTelefono() {
        jQuery("input[name='form_fields[tel]'], form [id='form-field-tel']").attr("maxlength", "10").removeAttr('pattern');
    }

    // Función para interceptar el evento keypress para el campo de nombre y permitir solo letras
    function permitirLetras(event) {
        var inputValue = event.which;
        // Permitimos letras, espacios y algunas teclas especiales como retroceso y flechas de dirección
        if (!(inputValue >= 65 && inputValue <= 122) && // letras
            (inputValue != 32 && // espacio
            inputValue != 0 && // flechas de dirección
            inputValue != 8 && // retroceso
            inputValue != 9 && // tabulador
            inputValue != 37 && // flecha izquierda
            inputValue != 39 && // flecha derecha
            inputValue != 46)) { // suprimir
                event.preventDefault();
        }
    }

    // Función para interceptar el evento keypress para el campo de teléfono y permitir solo números
    function permitirNumeros(event) {
        var inputValue = event.which;
        // Permitimos solo números y ciertas teclas especiales
        if (!(inputValue >= 48 && inputValue <= 57) && // números
            (inputValue != 0 && // flechas de dirección
            inputValue != 8 && // retroceso
            inputValue != 9 && // tabulador
            inputValue != 37 && // flecha izquierda
            inputValue != 39 && // flecha derecha
            inputValue != 46)) { // suprimir
                event.preventDefault();
        }
    }

    // Función para validar el campo de teléfono
    function validarTelefono() {
        jQuery("input[name='form_fields[tel]'], form [id='form-field-tel']").each(function() {
            jQuery(this).on("input", function() {
                if (this.value.length !== 10) {
                    this.setCustomValidity("Debe ingresar exactamente 10 dígitos.");
                } else {
                    this.setCustomValidity("");
                }
            });
        });
    }

    // Función para aplicar eventos a los formularios
    function aplicarEventos() {
        // Limitar el campo de teléfono a 10 caracteres
        limitarTelefono();

        // Interceptar el evento keypress para el campo de teléfono
        jQuery("input[name='form_fields[tel]'], form [id='form-field-tel']").on('keypress', permitirNumeros);

        // Validar el campo de teléfono
        validarTelefono();
    }

    // Aplicar eventos al cargar la página
    aplicarEventos();

    // Aplicar eventos cuando se abra el modal
    jQuery(document).on('elementor/popup/show', function(event, id, instance) {
        aplicarEventos();
    });
});




















jQuery(document).ready(function($) {
    // Obtener la URL actual del navegador
    var url = window.location.href;

    // Extraer el número de teléfono del parámetro tel en la URL
    var urlParams = new URLSearchParams(new URL(url).search);
    var telefono = urlParams.get('tel');

    // Extraer el valor del parámetro page_ref de la URL
    var pageRef = urlParams.get('page_ref');

    // Crear la URL final
    var urlFinal = pageRef ? pageRef : url;

    // Si se extrajo el número de teléfono, añadirlo a la URL final
    if (telefono) {
        // Crear una instancia de URL con la URL final
        var urlObj = new URL(urlFinal);

        // Establecer el valor del parámetro tel en la URL final
        urlObj.searchParams.set('tel', telefono);

        // Obtener la URL completa con el número de teléfono añadido
        urlFinal = urlObj.toString();
    }

    // Establecer la URL final como el atributo href del elemento <a> dentro del div con el id "regresar"
    $('#regresar a').attr('href', urlFinal);
});


jQuery(document).ready(function($) {
    // Función para agregar parámetros a los enlaces
    function addParamsToLinks() {
        // Obtenemos los parámetros de la URL actual
        var urlParams = new URLSearchParams(window.location.search);

        // Recorremos todos los enlaces en la página
        $('.btn-mas a').each(function() {
            // Obtenemos la URL del enlace
            var href = $(this).attr('href');
            // Verificamos si ya hay parámetros en la URL del enlace
            var hasParams = href.indexOf('?') !== -1;

            // Si no hay parámetros, añadimos los de la URL actual
            if (!hasParams && urlParams.toString() !== '') {
                $(this).attr('href', href + '?' + urlParams.toString());
            }
        });
    }

    // Ejecutar la función cuando el documento esté listo
    addParamsToLinks();

    // Ejecutar la función cuando se inserten nuevos nodos en el DOM
    $(document).on('DOMNodeInserted', addParamsToLinks);
});





jQuery(document).ready(function() {
    // Función para obtener la dirección IP
    function getIPAddress() {
        return fetch('https://api64.ipify.org?format=json')
            .then(response => response.json())
            .then(data => data.ip)
            .catch(error => console.error('Error al obtener la dirección IP:', error));
    }

    // Función para asignar la dirección IP a los campos ocultos
    function assignIPAddress() {
        getIPAddress().then(ip => {
            jQuery("input[name='form_fields[ip_address]']").val(ip);
        });
    }
	
    // Asignar la dirección IP al cargar la página
    assignIPAddress();

    // Asignar la dirección IP cuando se abre cualquier modal
    jQuery(document).on('elementor/popup/show', function(event, id, instance) {
        assignIPAddress();
    });
});