jQuery(function($) {
  // Obtener el número de la URL (?tel=...)
  const url = new URL(window.location.href);
  let rawTel = url.searchParams.get('tel');

  // Si no viene en la URL, toma el del enlace tel:
  if (!rawTel) {
    rawTel = $('#telefono a').attr('href')?.replace('tel:', '') || '';
  }

  // Limpiar número (solo dígitos)
  rawTel = rawTel.replace(/\D/g, '');

  // Formato con código de país (para API)
  let con51 = rawTel;
  if (!con51.startsWith('51')) {
    con51 = '51' + con51;
  }

  // Formato sin código de país con cero (para mostrar si se desea)
  let sin51con0 = rawTel;
  if (sin51con0.startsWith('51')) {
    sin51con0 = sin51con0.slice(2);
  }
  if (!sin51con0.startsWith('0')) {
    sin51con0 = '0' + sin51con0;
  }

  // Rellenar campos ocultos
  $('.elementor-field-type-hidden')
    .find('#form-field-camPhone').val(con51).end()
    .find('#form-field-ref').val(window.location.href).end();

  // Map phone numbers to their corresponding values (con 51)
  const phoneMap = {

	'51017017632': '5117017632',
    

    
	'5117017642': '5117017642',
    '5117017643': '5117017643',
    '5117017650': '5117017650',
    '5117309232': '5117309232', // v4
    '5117309260': '5117309260',
    '5117309287': '5117309287',
    '5117301006': '5117301006',
    '5117301008': '5117301008',
    '51017301040': '51017301040',
    '5117301059': '5117301059',
    '5117301063': '5117301063', // v11
    '5117301082': '5117301082', // v12
    '5117301094': '5117301094', // v13
    '5117028451': '5117028451', // v14
	'5117091787': '5117091787', // v16
	'5117091788': '5117091788', // v17
	'5117091789': '5117091789', // v18		
		
		
    '51017301041': '51017301041'
  };

  // Aplicar el valor mapeado o usar el valor con51 como fallback
  const mappedValue = phoneMap[con51] || con51;

  $('input[name="form_fields[camPhone]"]').val(mappedValue);

  // Logs de depuración
  console.log('Número original:', rawTel);
  console.log('Formato con 51 (API):', con51);
  console.log('Formato sin 51 con 0 (visual):', sin51con0);
  console.log('Mapped Phone:', mappedValue);
});





jQuery(document).ready(function() {
    // Función para limitar el campo de teléfono a 11 caracteres
    function limitarTelefono() {
        jQuery("input[name='form_fields[tel]'], form [id='form-field-tel']").attr("maxlength", "11").removeAttr('pattern');
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
                if (this.value.length !== 11) {
                    this.setCustomValidity("Debe ingresar exactamente 11 dígitos anteponiendo 51.");
                } else {
                    this.setCustomValidity("");
                }
            });
        });
    }

    // Función para aplicar eventos a los formularios
    function aplicarEventos() {
        // Limitar el campo de teléfono a 11 caracteres
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





jQuery(document).ready(function($) {
    // Verificar si la IP está presente
 //   console.log('IP del usuario:', my_ajax_object.user_ip);

    // Función para establecer la IP en todos los campos de formularios
    function setIpAddress() {
        $('input#form-field-ip_address').each(function() {
            $(this).val(my_ajax_object.user_ip);
   //         console.log('IP establecida en el campo:', $(this).val());
        });
    }

    // Establecer la IP al cargar la página
    setIpAddress();

    // Escuchar el evento de inicialización del frontend de Elementor
    $(window).on('elementor/frontend/init', function() {
  //      console.log('Elementor frontend inicializado');
        setIpAddress();
    });

    // Detectar cuando se muestra un popup/modal de Elementor
    $(document).on('elementor/popup/show', function() {
    //    console.log('Popup/modal de Elementor mostrado');
        setIpAddress();
    });

    // Escuchar cualquier cambio en el DOM para detectar nuevos formularios cargados dinámicamente
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                setIpAddress();
            }
        });
    });

    // Configurar el observador para observar cambios en todo el documento
    observer.observe(document.body, { childList: true, subtree: true });
});