// JavaScript para barcode_scanner_salida.php
document.addEventListener('DOMContentLoaded', function() {
    // Datos de productos obtenidos del servidor
    var productsData = productDataFromServer; // Esta variable será definida en el HTML
    
    // Elementos del DOM
    var barcodeInput = document.getElementById('barcode-input');
    var scanArea = document.getElementById('scan-area');
    var manualAddBtn = document.getElementById('manual-add-btn');
    var clearInputBtn = document.getElementById('clear-input-btn');
    var batchItems = document.getElementById('batch-items');
    var emptyBatchMessage = document.getElementById('empty-batch-message');
    var totalProductsEl = document.getElementById('total-products');
    var totalUnitsEl = document.getElementById('total-units');
    var clearBatchBtn = document.getElementById('clear-batch-btn');
    var saveBatchBtn = document.getElementById('save-batch-btn');
    var batchForm = document.getElementById('batch-form');
    var batchDataInput = document.getElementById('batch-data-input');
    
    // Modales (asumiendo que se está usando Bootstrap)
    var productSelectModal = new bootstrap.Modal(document.getElementById('product-select-modal'));
    var assignBarcodeModal = new bootstrap.Modal(document.getElementById('assign-barcode-modal'));
    
    // Estado del lote actual
    var batchData = {};

    barcodeInput.focus();

    
    // Función para buscar un producto por código de barras
    function findProductByBarcode(barcode) {
        for (var id in productsData) {
            if (productsData[id].barcode && productsData[id].barcode.trim() === barcode) {
                return productsData[id];
            }
        }
        return null;
    }
    
    // Función para agregar un producto al lote
    function addProductToBatch(product, barcode, quantity) {
        // Valor por defecto para quantity si no se proporciona
        quantity = quantity || 1;
        
        // Verificar stock disponible
        if (product.stock < quantity) {
            Swal.fire({
                title: 'Stock Insuficiente',
                text: `El producto "${product.name}" solo tiene ${product.stock} unidades disponibles. No puede retirar ${quantity} unidades.`,
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return;
        }
        
        // Si ya existe, aumentar la cantidad (pero verificar stock total)
        if (batchData[barcode]) {
            var newQuantity = batchData[barcode].quantity + quantity;
            if (product.stock < newQuantity) {
                Swal.fire({
                    title: 'Stock Insuficiente',
                    text: `El producto "${product.name}" solo tiene ${product.stock} unidades disponibles. No puede retirar ${newQuantity} unidades en total.`,
                    icon: 'warning',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
            batchData[barcode].quantity = newQuantity;
            updateBatchItemRow(barcode);
        } else {
            // Si es nuevo, crear entrada
            batchData[barcode] = {
                product_id: product.id,
                product_name: product.name,
                product_description: product.description,
                barcode: barcode,
                quantity: quantity,
                available_stock: product.stock
            };
            
            // Crear fila en la tabla
            if (Object.keys(batchData).length === 1) {
                // Ocultar mensaje de lote vacío
                emptyBatchMessage.style.display = 'none';
            }
            
            var stockWarning = product.stock < 10 ? 'stock-warning' : '';
            var remainingStock = product.stock - quantity;
            var remainingClass = remainingStock < 10 ? 'stock-warning' : '';
            
            var newRow = document.createElement('tr');
            newRow.id = 'batch-item-' + barcode;
            newRow.classList.add('scan-animation');
            newRow.innerHTML = 
                '<td>' + barcode + '</td>' +
                '<td>' + product.name + '</td>' +
                '<td>' + product.description + '</td>' +
                '<td class="' + stockWarning + '">' + product.stock + ' unidades</td>' +
                '<td>' +
                    '<div class="quantity-control">' +
                        '<span class="quantity-btn decrease-btn" data-barcode="' + barcode + '">-</span>' +
                        '<input type="number" class="form-control quantity-input" value="' + quantity + '" min="1" max="' + product.stock + '" ' +
                        'data-barcode="' + barcode + '">' +
                        '<span class="quantity-btn increase-btn" data-barcode="' + barcode + '">+</span>' +
                    '</div>' +
                    '<div class="stock-info">Quedarán: <span class="remaining-stock ' + remainingClass + '">' + remainingStock + '</span></div>' +
                '</td>' +
                '<td>' +
                    '<button class="btn btn-danger btn-sm remove-item" data-barcode="' + barcode + '">' +
                        '<i class="bi bi-x"></i>' +
                    '</button>' +
                '</td>';
            
            batchItems.appendChild(newRow);
            
            // Eliminar la animación después de un tiempo
            setTimeout(function() {
                newRow.classList.remove('scan-animation');
            }, 500);
            
            // Agregar event listeners para botones de cantidad
            var decreaseBtn = newRow.querySelector('.decrease-btn');
            if (decreaseBtn) {
                decreaseBtn.addEventListener('click', function() {
                    var barcode = this.getAttribute('data-barcode');
                    updateItemQuantity(barcode, -1);
                });
            }
            
            var increaseBtn = newRow.querySelector('.increase-btn');
            if (increaseBtn) {
                increaseBtn.addEventListener('click', function() {
                    var barcode = this.getAttribute('data-barcode');
                    updateItemQuantity(barcode, 1);
                });
            }
            
            var quantityInput = newRow.querySelector('.quantity-input');
            if (quantityInput) {
                quantityInput.addEventListener('change', function() {
                    var barcode = this.getAttribute('data-barcode');
                    var newQuantity = parseInt(this.value);
                    var availableStock = batchData[barcode].available_stock;
                    
                    if (newQuantity > 0 && newQuantity <= availableStock) {
                        batchData[barcode].quantity = newQuantity;
                        updateBatchItemRow(barcode);
                        updateBatchSummary();
                    } else if (newQuantity > availableStock) {
                        this.value = availableStock;
                        batchData[barcode].quantity = availableStock;
                        Swal.fire({
                            title: 'Cantidad Ajustada',
                            text: `La cantidad se ajustó a ${availableStock} que es el máximo disponible.`,
                            icon: 'info',
                            confirmButtonText: 'Aceptar'
                        });
                        updateBatchItemRow(barcode);
                        updateBatchSummary();
                    } else {
                        this.value = 1;
                        batchData[barcode].quantity = 1;
                        updateBatchItemRow(barcode);
                        updateBatchSummary();
                    }
                });
            }
            
            var removeBtn = newRow.querySelector('.remove-item');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    var barcode = this.getAttribute('data-barcode');
                    removeItemFromBatch(barcode);
                });
            }
        }
        
        // Actualizar resumen del lote
        updateBatchSummary();
        
        // Habilitar botón de guardar
        saveBatchBtn.disabled = false;
    }
    
    // Función para actualizar la cantidad de un producto
    function updateItemQuantity(barcode, change) {
        if (batchData[barcode]) {
            var newQuantity = batchData[barcode].quantity + change;
            var availableStock = batchData[barcode].available_stock;
            
            if (newQuantity > 0 && newQuantity <= availableStock) {
                batchData[barcode].quantity = newQuantity;
                updateBatchItemRow(barcode);
                updateBatchSummary();
            } else if (newQuantity > availableStock) {
                Swal.fire({
                    title: 'Stock Insuficiente',
                    text: `Solo hay ${availableStock} unidades disponibles.`,
                    icon: 'warning',
                    confirmButtonText: 'Aceptar'
                });
            }
        }
    }
    
    // Función para actualizar la fila de un producto
    function updateBatchItemRow(barcode) {
        var row = document.getElementById('batch-item-' + barcode);
        if (row && batchData[barcode]) {
            var quantityInput = row.querySelector('.quantity-input');
            var remainingStockEl = row.querySelector('.remaining-stock');
            
            if (quantityInput) {
                quantityInput.value = batchData[barcode].quantity;
            }
            
            if (remainingStockEl) {
                var remaining = batchData[barcode].available_stock - batchData[barcode].quantity;
                remainingStockEl.textContent = remaining;
                
                // Actualizar clase de advertencia
                remainingStockEl.className = 'remaining-stock' + (remaining < 10 ? ' stock-warning' : '');
            }
        }
    }
    
    // Función para eliminar un producto del lote
    function removeItemFromBatch(barcode) {
        delete batchData[barcode];
        var row = document.getElementById('batch-item-' + barcode);
        if (row) {
            row.parentNode.removeChild(row);
        }
        
        // Si no hay productos, mostrar mensaje
        if (Object.keys(batchData).length === 0) {
            emptyBatchMessage.style.display = '';
            saveBatchBtn.disabled = true;
        }
        
        updateBatchSummary();
    }
    
    // Función para actualizar el resumen del lote
    function updateBatchSummary() {
        var productCount = Object.keys(batchData).length;
        var unitCount = 0;
        
        for (var barcode in batchData) {
            unitCount += batchData[barcode].quantity;
        }
        
        totalProductsEl.textContent = productCount;
        totalUnitsEl.textContent = unitCount;
    }
    
    // Función para procesar un código de barras escaneado
    function processBarcode(barcode) {
        if (!barcode) return;
        
        // Reproducir sonido de beep si está disponible
        var beepSound = document.getElementById('beep-sound');
        if (beepSound) {
            beepSound.play().catch(function(e) {
                console.log('Error reproduciendo sonido', e);
            });
        }
        
        // Buscar producto por código de barras
        var product = findProductByBarcode(barcode);
        
        if (product) {
            // Verificar si hay stock disponible
            if (product.stock <= 0) {
                Swal.fire({
                    title: 'Sin Stock',
                    text: `El producto "${product.name}" no tiene stock disponible.`,
                    icon: 'warning',
                    confirmButtonText: 'Aceptar'
                });
                barcodeInput.value = '';
                return;
            }
            
            // Agregar el producto al lote
            addProductToBatch(product, barcode);
            
            // Efecto de escaneo exitoso
            scanArea.classList.add('scan-animation');
            setTimeout(function() {
                scanArea.classList.remove('scan-animation');
            }, 500);
            
            // Limpiar input
            barcodeInput.value = '';
        } else {
            // Preguntar si quiere asignar el código a un producto
            showAssignBarcodeModal(barcode);
        }
    }
    
    // Función para mostrar modal de asignación de código
    function showAssignBarcodeModal(barcode) {
        document.getElementById('scanned-barcode-display').textContent = barcode;
        document.getElementById('current-scanned-barcode').value = barcode;
        
        // Llenar la lista de productos
        populateBarcodeProductList('');
        
        // Mostrar modal
        assignBarcodeModal.show();
    }
    
    // Función para poblar la lista de productos para asignar código
    function populateBarcodeProductList(searchTerm) {
        var productList = document.getElementById('barcode-product-list');
        productList.innerHTML = '';
        
        var barcode = document.getElementById('current-scanned-barcode').value;
        var filteredProducts = [];
        
        // Filtrar productos manualmente
        for (var id in productsData) {
            var product = productsData[id];
            if (!searchTerm || 
                product.name.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1 || 
                (product.description && product.description.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1)) {
                filteredProducts.push(product);
            }
        }
        
        filteredProducts.forEach(function(product) {
            var stockClass = product.stock < 10 ? 'stock-warning' : '';
            var row = document.createElement('tr');
            row.innerHTML = 
                '<td>' + product.id + '</td>' +
                '<td>' + product.name + '</td>' +
                '<td class="' + stockClass + '">' + product.stock + '</td>' +
                '<td>' +
                    '<button class="btn btn-primary btn-sm assign-barcode-btn" data-product-id="' + product.id + '" ' +
                    (product.stock <= 0 ? 'disabled' : '') + '>' +
                        'Asignar' +
                    '</button>' +
                '</td>';
            productList.appendChild(row);
            
            // Event listener para asignar código
            var assignBtn = row.querySelector('.assign-barcode-btn');
            if (assignBtn) {
                assignBtn.addEventListener('click', function() {
                    var productId = this.getAttribute('data-product-id');
                    assignBarcodeToProduct(barcode, productId);
                });
            }
        });
    }
    
    // Función para asignar código a un producto
    function assignBarcodeToProduct(barcode, productId) {
        // Llamada AJAX para guardar en la BD
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'assign_barcode.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Actualizar localmente
                            productsData[productId].barcode = barcode;
                            
                            // Cerrar modal
                            assignBarcodeModal.hide();
                            
                            // Agregar producto al lote
                            addProductToBatch(productsData[productId], barcode);
                            
                            // Notificar éxito
                            Swal.fire({
                                title: 'Código Asignado',
                                text: 'Código ' + barcode + ' asignado al producto ' + productsData[productId].name,
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
                            });
                            
                            // Limpiar input
                            barcodeInput.value = '';
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'Aceptar'
                            });
                        }
                    } catch (e) {
                        console.error('Error al analizar la respuesta:', e);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al procesar la respuesta del servidor',
                            icon: 'error',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error en la solicitud: ' + xhr.status,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            }
        };
        
        xhr.send(JSON.stringify({
            product_id: productId,
            barcode: barcode
        }));
    }
    
    // Función para mostrar el modal de selección manual
    function showProductSelectModal() {
        // Poblar la lista de productos
        populateProductList('');
        
        // Mostrar modal
        productSelectModal.show();
    }
    
    // Función para poblar la lista de productos 
    function populateProductList(searchTerm) {
        var productList = document.getElementById('product-list');
        productList.innerHTML = '';
        
        var filteredProducts = [];
        
        // Filtrar productos manualmente
        for (var id in productsData) {
            var product = productsData[id];
            if (!searchTerm || 
                product.name.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1 || 
                (product.description && product.description.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1)) {
                filteredProducts.push(product);
            }
        }
        
        filteredProducts.forEach(function(product) {
            var stockClass = product.stock < 10 ? 'stock-warning' : '';
            var row = document.createElement('tr');
            row.innerHTML = 
                '<td>' + product.id + '</td>' +
                '<td>' + product.name + '</td>' +
                '<td>' + product.description + '</td>' +
                '<td class="' + stockClass + '">' + product.stock + '</td>' +
                '<td>' +
                    '<button class="btn btn-primary btn-sm select-product-btn" data-product-id="' + product.id + '" ' +
                    (product.stock <= 0 ? 'disabled' : '') + '>' +
                        'Seleccionar' +
                    '</button>' +
                '</td>';
            productList.appendChild(row);
            
            // Event listener para seleccionar producto
            var selectBtn = row.querySelector('.select-product-btn');
            if (selectBtn) {
                selectBtn.addEventListener('click', function() {
                    var productId = this.getAttribute('data-product-id');
                    selectProduct(productId);
                });
            }
        });
    }
    
    // Función para seleccionar un producto manualmente
    function selectProduct(productId) {
        var product = productsData[productId];
        
        if (product.stock <= 0) {
            Swal.fire({
                title: 'Sin Stock',
                text: `El producto "${product.name}" no tiene stock disponible.`,
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return;
        }
        
        // Generar un código Code 128 si no tiene
        var barcode;
        if (product.barcode) {
            barcode = product.barcode;
        } else {
            // Formato Code 128: ASF-XXXXX
            barcode = 'ASF-' + productId.toString().padStart(5, '0');
        }
        
        // Agregar al lote
        addProductToBatch(product, barcode);
        
        // Cerrar modal
        productSelectModal.hide();
    }
    
    // Función para limpiar el lote
    function clearBatch() {
        if (Object.keys(batchData).length === 0) return;
        
        Swal.fire({
            title: '¿Limpiar lote?',
            text: '¿Está seguro de que desea limpiar todo el lote de salida?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                batchData = {};
                batchItems.innerHTML = '';
                batchItems.appendChild(emptyBatchMessage);
                emptyBatchMessage.style.display = '';
                updateBatchSummary();
                saveBatchBtn.disabled = true;
            }
        });
    }
    
    function saveBatch() {
        if (Object.keys(batchData).length === 0) return;
        
        // Verificar stock antes de confirmar
        var stockIssues = [];
        for (var barcode in batchData) {
            var item = batchData[barcode];
            if (item.quantity > item.available_stock) {
                stockIssues.push(item.product_name + ' (solicita: ' + item.quantity + ', disponible: ' + item.available_stock + ')');
            }
        }
        
        if (stockIssues.length > 0) {
            Swal.fire({
                title: 'Problemas de Stock',
                html: 'Los siguientes productos tienen problemas de stock:<br><br>' + stockIssues.join('<br>'),
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            return;
        }
        
        // Mostrar confirmación antes de proceder
        Swal.fire({
            title: '¿Confirmar salida de inventario?',
            html: `Se procesarán <strong>${Object.keys(batchData).length}</strong> productos para <strong>salida de inventario</strong>.<br><br>Esta operación <strong>reducirá</strong> el stock disponible.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar salida',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                // Preparar datos para enviar
                batchDataInput.value = JSON.stringify(batchData);
                
                // Mostrar SweetAlert2 para indicar que se está procesando
                Swal.fire({
                    title: 'Procesando salida de inventario',
                    text: 'Por favor espere mientras se procesa la salida del inventario...',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: () => {
                        Swal.showLoading();
                        
                        // Usar setTimeout para simular un breve retraso y permitir que se muestre la alerta
                        setTimeout(() => {
                            // Enviar formulario
                            batchForm.submit();
                        }, 800);
                    }
                });
            }
        });
    }
    
    
    // Escáner de código de barras (tecla Enter)
    barcodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            processBarcode(this.value.trim());
        }
    });
    
    // Botón de agregar manualmente
    manualAddBtn.addEventListener('click', function() {
        showProductSelectModal();
    });
    
    // Botón de limpiar input
    clearInputBtn.addEventListener('click', function() {
        barcodeInput.value = '';
        barcodeInput.focus();
    });
    
    // Botón de limpiar lote
    clearBatchBtn.addEventListener('click', clearBatch);
    
    // Botón de guardar lote
    saveBatchBtn.addEventListener('click', saveBatch);
    
    // Búsqueda en modal de productos
    var productSearchInput = document.getElementById('product-search');
    if (productSearchInput) {
        productSearchInput.addEventListener('input', function() {
            populateProductList(this.value);
        });
    }
    
    // Búsqueda en modal de asignación de código
    var barcodeProductSearchInput = document.getElementById('barcode-product-search');
    if (barcodeProductSearchInput) {
        barcodeProductSearchInput.addEventListener('input', function() {
            populateBarcodeProductList(this.value);
        });
    }
    
    // Mantener el foco en el input de código de barras
    barcodeInput.focus();
    document.addEventListener('click', function() {
        var modalShown = document.querySelector('.modal.show');
        var sweetAlertShown = document.querySelector('.swal2-container');
        
        // Solo dar foco al input si no hay modales o SweetAlert abiertos
        if (!modalShown && !sweetAlertShown) {
            barcodeInput.focus();
        }
    });

    // MutationObserver para mantener el foco después de cerrar SweetAlert
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            // Verificar si se eliminó un nodo
            if (mutation.removedNodes.length > 0) {
                // Verificar cada nodo eliminado
                for (var i = 0; i < mutation.removedNodes.length; i++) {
                    var node = mutation.removedNodes[i];
                    // Verificar si es el contenedor de SweetAlert
                    if (node.classList && node.classList.contains('swal2-container')) {
                        // Redirigir el foco al input
                        setTimeout(function() {
                            barcodeInput.focus();
                        }, 100);
                        break;
                    }
                }
            }
        });
    });

    // Configurar el observer para observar cambios en el body
    observer.observe(document.body, {
        childList: true,
        subtree: false
    });
});

// Función para asegurar que el input tenga el foco
function ensureFocus() {
    setTimeout(function() {
        var barcodeInput = document.getElementById('barcode-input');
        if (barcodeInput) {
            barcodeInput.focus();
        }
    }, 100);
}

window.ensureFocus = ensureFocus;