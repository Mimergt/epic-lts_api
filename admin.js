jQuery(document).ready(function($) {
    
    // Test de conexión AJAX
    $('#test-ajax').on('click', function() {
        $.ajax({
            url: leads_lts_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'leads_lts_test_connection',
                nonce: leads_lts_ajax.nonce
            },
            success: function(response) {
                alert('✅ Test exitoso: ' + response.data);
            },
            error: function(xhr, status, error) {
                console.log('Test AJAX Error:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                alert('❌ Test falló. Revisa la consola para detalles.');
            }
        });
    });
    
    // Añadir nuevo mapeo
    $('#add-mapping').on('click', function() {
        var originalPhone = $('#original_phone').val().trim().replace(/\s+/g, ''); // Quitar todos los espacios
        var mappedCamphone = $('#mapped_camphone').val().trim().replace(/\s+/g, ''); // Quitar todos los espacios
        
        if (!originalPhone || !mappedCamphone) {
            alert('Por favor, completa ambos campos.');
            return;
        }
        
        // Verificar si ya existe el mapeo
        if ($('tr[data-original="' + originalPhone + '"]').length > 0) {
            alert('Ya existe un mapeo para este número. Elimina el existente primero.');
            return;
        }
        
        $.ajax({
            url: leads_lts_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'leads_lts_save_phone_mapping',
                nonce: leads_lts_ajax.nonce,
                original_phone: originalPhone,
                mapped_camphone: mappedCamphone
            },
            beforeSend: function() {
                $('#add-mapping').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                $('#add-mapping').prop('disabled', false).text('Añadir Mapeo');
                
                if (response.success) {
                    // Agregar fila a la tabla
                    addMappingRow(originalPhone, mappedCamphone);
                    
                    // Limpiar campos
                    $('#original_phone').val('');
                    $('#mapped_camphone').val('');
                    
                    alert('Mapeo añadido exitosamente');
                } else {
                    alert('Error: ' + (response.data || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                $('#add-mapping').prop('disabled', false).text('Añadir Mapeo');
                console.log('AJAX Error:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                alert('Error al conectar con el servidor. Revisa la consola para más detalles.');
            }
        });
    });
    
    // Eliminar mapeo
    $(document).on('click', '.delete-mapping', function() {
        var originalPhone = $(this).data('original');
        var row = $(this).closest('tr');
        
        if (!confirm('¿Estás seguro de que deseas eliminar este mapeo?')) {
            return;
        }
        
        $.ajax({
            url: leads_lts_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'leads_lts_delete_phone_mapping',
                nonce: leads_lts_ajax.nonce,
                original_phone: originalPhone
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        checkEmptyTable();
                    });
                } else {
                    alert('Error: ' + (response.data || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                alert('Error al conectar con el servidor. Revisa la consola para más detalles.');
            }
        });
    });
    
    // Función para añadir fila a la tabla
    function addMappingRow(original, camphone) {
        var tableBody = $('#mappings-table-body');
        
        // Si la tabla está vacía, crear la estructura
        if (tableBody.length === 0) {
            $('.mappings-list').html(`
                <h3>Mapeos Existentes</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Teléfono Original</th>
                            <th>CamPhone Asignado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="mappings-table-body">
                    </tbody>
                </table>
            `);
            tableBody = $('#mappings-table-body');
        }
        
        var newRow = `
            <tr data-original="${original}">
                <td><code>${original}</code></td>
                <td><code>${camphone}</code></td>
                <td>
                    <button type="button" class="button delete-mapping" data-original="${original}">
                        Eliminar
                    </button>
                </td>
            </tr>
        `;
        
        tableBody.append(newRow);
    }
    
    // Verificar si la tabla está vacía después de eliminar
    function checkEmptyTable() {
        if ($('#mappings-table-body tr').length === 0) {
            $('.mappings-list').html('<h3>Mapeos Existentes</h3><p>No hay mapeos configurados aún.</p>');
        }
    }
    
    // Permitir Enter para añadir mapeo
    $('#original_phone, #mapped_camphone').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $('#add-mapping').click();
        }
    });
    
    // Limpiar espacios automáticamente mientras se escribe
    $('#original_phone, #mapped_camphone').on('input', function() {
        var cursorPos = this.selectionStart;
        var valueWithoutSpaces = this.value.replace(/\s+/g, '');
        if (this.value !== valueWithoutSpaces) {
            this.value = valueWithoutSpaces;
            // Mantener la posición del cursor
            this.setSelectionRange(cursorPos - 1, cursorPos - 1);
        }
    });
});
