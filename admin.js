jQuery(document).ready(function($) {
    
    // Añadir nuevo mapeo
    $('#add-mapping').on('click', function() {
        var originalPhone = $('#original_phone').val().trim();
        var mappedCamphone = $('#mapped_camphone').val().trim();
        
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
            data: {
                action: 'save_phone_mapping',
                nonce: leads_lts_ajax.nonce,
                original_phone: originalPhone,
                mapped_camphone: mappedCamphone
            },
            success: function(response) {
                if (response.success) {
                    // Agregar fila a la tabla
                    addMappingRow(originalPhone, mappedCamphone);
                    
                    // Limpiar campos
                    $('#original_phone').val('');
                    $('#mapped_camphone').val('');
                    
                    alert('Mapeo añadido exitosamente');
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error al conectar con el servidor');
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
            data: {
                action: 'delete_phone_mapping',
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
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error al conectar con el servidor');
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
});
