jQuery(function($) {
  // Usar selector dinámico desde la configuración
  var phoneSelector = my_ajax_object.phone_selector || '#call';
  var url = new URL(window.location.href);
  var referer = window.location.origin + window.location.pathname;
  var enableDebug = my_ajax_object.enable_debug == '1';

  // Usar mapeos desde la base de datos (configurados en el admin)
  var phoneMap = my_ajax_object.phone_mappings || {};
  var defaultCamPhone = my_ajax_object.default_camphone || '5592509960';

  // PRIMERO: Verificar si hay parámetro 'tel' en la URL
  var urlParams = new URLSearchParams(window.location.search);
  var telFromUrl = urlParams.get('tel');
  
  // SEGUNDO: Obtener el teléfono del selector (puede ser el original o ya actualizado)
  var camPhone = $(phoneSelector).attr('href');
  
  $('.elementor-field-type-hidden')
    .find('#form-field-camPhone').val(camPhone).end()
    .find('#form-field-ref').val(window.location.href).end();

  // Debug completo solo si está activado
  if (enableDebug) {
    console.log('=== LEADS LTS DEBUG ===');
    console.log('🔍 PASO 1: Verificando URL');
    console.log('Current URL:', window.location.href);
    console.log('URL Search params:', window.location.search);
    console.log('Tel parameter from URL:', telFromUrl);
    console.log('');
    console.log('🔍 PASO 2: Verificando selector');
    console.log('Phone Selector:', phoneSelector);
    console.log('Original Phone from selector:', camPhone);
    console.log('');
    console.log('🔍 PASO 3: Configuración de mapeos');
    console.log('Phone Mappings from DB:', phoneMap);
    console.log('Default CamPhone:', defaultCamPhone);
    
    // Verificar si existe el elemento
    if (!$(phoneSelector).length) {
      console.log('⚠️ Elemento con selector', phoneSelector, 'no encontrado');
    }
    console.log('');
  }

  // Calcular el valor una sola vez
  var finalMappedValue;
  
  // PRIORIDAD 1: Si hay parámetro 'tel' en la URL y está en los mapeos, usarlo
  if (telFromUrl && phoneMap[telFromUrl]) {
    finalMappedValue = phoneMap[telFromUrl];
    if (enableDebug) {
      console.log('🎯 DECISIÓN: Usando tel de URL');
      console.log('✅ Tel de URL encontrado en mapeos:', telFromUrl, '->', finalMappedValue);
    }
  } 
  // PRIORIDAD 2: Buscar el teléfono del selector en los mapeos
  else {
    if (enableDebug) {
      console.log('🎯 DECISIÓN: Tel de URL no disponible o no está en mapeos');
      if (telFromUrl) {
        console.log('⚠️ Tel de URL existe pero NO está en mapeos:', telFromUrl);
        console.log('💡 Verifica que el número esté agregado en el admin de WordPress');
      } else {
        console.log('ℹ️ No hay parámetro tel en la URL');
      }
    }
    
    var phoneToCheck = camPhone;
    var phoneWithoutTel = camPhone ? camPhone.replace('tel:', '') : '';
    
    // Intentar buscar el mapeo con diferentes formatos
    if (camPhone && phoneMap[camPhone]) {
      // Búsqueda exacta (ej: tel:8008800810)
      finalMappedValue = phoneMap[camPhone];
      if (enableDebug) {
        console.log('✅ Mapeo específico encontrado (formato completo):', camPhone, '->', finalMappedValue);
      }
    } else if (phoneWithoutTel && phoneMap[phoneWithoutTel]) {
      // Búsqueda sin prefijo tel: (ej: 8008800810)
      finalMappedValue = phoneMap[phoneWithoutTel];
      if (enableDebug) {
        console.log('✅ Mapeo específico encontrado (sin tel:):', phoneWithoutTel, '->', finalMappedValue);
      }
    } else {
      // Usar default
      finalMappedValue = defaultCamPhone;
      if (enableDebug) {
        console.log('📱 Usando CamPhone default:', finalMappedValue);
        if (camPhone) {
          console.log('❌ No se encontró mapeo para:', camPhone, 'ni para:', phoneWithoutTel);
          console.log('💡 Revisa que el mapeo en el admin coincida exactamente');
        }
      }
    }
  }
  
  if (enableDebug) {
    console.log('');
    console.log('🏁 RESULTADO FINAL');
    console.log('Final mapped value:', finalMappedValue);
    console.log('=== END DEBUG ===');
  }

  // PASO 1: Buscar todos los formularios CF7 y crear campo camPhone si no existe
  var cf7Forms = $('form.wpcf7-form');
  
  if (enableDebug) {
    console.log('');
    console.log('🔍 BUSCANDO FORMULARIOS CF7');
    console.log('Formularios CF7 encontrados:', cf7Forms.length);
  }
  
  // Crear campo oculto en cada formulario si no existe
  cf7Forms.each(function(index) {
    var $form = $(this);
    var existingField = $form.find('input[name="camPhone"]');
    
    if (existingField.length === 0) {
      // No existe, crear el campo
      var hiddenField = $('<input>', {
        type: 'hidden',
        name: 'camPhone',
        value: finalMappedValue,
        class: 'wpcf7-form-control'
      });
      $form.append(hiddenField);
      
      if (enableDebug) {
        console.log('✅ Campo camPhone creado en formulario', index);
      }
    } else {
      if (enableDebug) {
        console.log('ℹ️ Campo camPhone ya existe en formulario', index);
      }
    }
  });

  // PASO 2: Asignar el valor calculado a TODOS los posibles campos camPhone
  // Intentar múltiples selectores porque CF7 y Elementor usan formatos diferentes
  var camPhoneFields = $(
    'input[name="form_fields[camPhone]"], ' +
    'input[name="camPhone"], ' +
    'input[id="form-field-camPhone"], ' +
    '#form-field-camPhone, ' +
    '.elementor-field-type-hidden input[name*="camPhone"]'
  );
  
  if (enableDebug) {
    console.log('');
    console.log('📝 ASIGNANDO VALOR A CAMPOS');
    console.log('Campos camPhone encontrados:', camPhoneFields.length);
    camPhoneFields.each(function(index) {
      console.log('Campo ' + index + ':', {
        name: $(this).attr('name'),
        id: $(this).attr('id'),
        currentValue: $(this).val()
      });
    });
  }
  
  // Asignar el valor
  camPhoneFields.val(finalMappedValue);
  
  if (enableDebug) {
    console.log('✅ Valor asignado:', finalMappedValue);
    console.log('Verificando asignación...');
    camPhoneFields.each(function(index) {
      console.log('Campo ' + index + ' después:', {
        name: $(this).attr('name'),
        newValue: $(this).val()
      });
    });
  }
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



// Cambiar el número de teléfono en el header basado en el parámetro 'tel' de la URL
jQuery(document).ready(function($) {
    // Función para actualizar el teléfono en el header
    function updatePhoneFromURL() {
        // Obtener el parámetro 'tel' de la URL
        var urlParams = new URLSearchParams(window.location.search);
        var telParam = urlParams.get('tel');
        
        console.log('=== UPDATE PHONE FROM URL ===');
        console.log('Tel param from URL:', telParam);
        console.log('.phone_head a elements found:', $('.phone_head a').length);
        
        // Si existe el parámetro 'tel', actualizar el elemento
        if (telParam) {
            var $phoneElement = $('.phone_head a');
            
            if ($phoneElement.length > 0) {
                console.log('Original href:', $phoneElement.attr('href'));
                console.log('Original text:', $phoneElement.text());
                
                // Actualizar el href del enlace
                $phoneElement.attr('href', 'tel:' + telParam);
                
                // Actualizar el texto visible del teléfono
                $phoneElement.text(telParam);
                
                console.log('New href:', $phoneElement.attr('href'));
                console.log('New text:', $phoneElement.text());
                console.log('✅ Phone updated successfully');
            } else {
                console.log('⚠️ Element .phone_head a not found');
            }
        } else {
            console.log('ℹ️ No tel parameter in URL');
        }
        console.log('=== END UPDATE PHONE ===');
    }
    
    // Ejecutar al cargar la página
    updatePhoneFromURL();
    
    // Intentar nuevamente después de 500ms por si el elemento se carga tarde
    setTimeout(updatePhoneFromURL, 500);
    
    // Y una vez más después de 1 segundo
    setTimeout(updatePhoneFromURL, 1000);
});