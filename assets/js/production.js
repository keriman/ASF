const socket = new WebSocket('ws://127.0.0.1:8080');

document.addEventListener('DOMContentLoaded', () => {
    socket.addEventListener('message', (event) => {
        console.log('Received message:', event.data);
        try {
            const order = JSON.parse(event.data);
            console.log('ID de la orden recibida:', order._id);
            addOrderToHistory(order);
            Swal.fire({
                title: 'Nueva Orden Recibida',
                text: `Cantidad: ${order.cantidad}, Producto: ${order.producto}, Presentación: ${order.presentacion}`,
                icon: 'info',
                confirmButtonText: 'Aceptar',
                timer: 4000,
                timerProgressBar: true
            });
            playAlertSound();
            flashScreen(); // Llamamos a flashScreen aquí
            showNotification('Nueva Orden', `Cantidad: ${order.cantidad}, Producto: ${order.producto}`);
            fetchOrderHistory();
        } catch (error) {
            console.error('Error parsing JSON:', error);
        }
    });
    if ("Notification" in window && Notification.permission !== "granted") {
        Notification.requestPermission();
    }
});


function fetchOrderHistory() {
    return fetch('http://127.0.0.1:3000/api/orders')
        .then(response => response.json())
        .then(orders => {
            ordersHistory = [];
            if (historyDataTable) {
                historyDataTable.clear();
            }
            orders.forEach(order => addOrderToHistory(order));
            if (historyDataTable) {
                historyDataTable.order([6, 'desc']).draw();
            }
            return orders; // Retornar las órdenes para uso posterior si es necesario
        })
        .catch(error => {
            console.error('Error fetching order history:', error);
            throw error; // Propagar el error para manejarlo en el llamador si es necesario
        });
}

// Función para recargar la tabla
function reloadTable() {
    fetchOrderHistory().then(() => {
        console.log('Tabla actualizada');
        // Mostrar una notificación al usuario
        Swal.fire({
            title: 'Actualización',
            text: 'La tabla de pedidos ha sido actualizada',
            icon: 'info',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }).catch(error => {
        console.error('Error al actualizar la tabla:', error);
        // Mostrar una notificación de error al usuario
        Swal.fire({
            title: 'Error',
            text: 'No se pudo actualizar la tabla de pedidos',
            icon: 'error',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    });
}

setInterval(reloadTable, 1 * 60 * 1000);


let ordersHistory = [];
let historyDataTable = null;

function addOrderToHistory(order) {
    if (!historyDataTable) {
        let table = document.getElementById('ordersHistoryTable');
        if (!table) {
            table = document.createElement('table');
            table.id = 'ordersHistoryTable';
            table.className = 'table table-striped table-hover table-bordered';
            table.innerHTML = `
                <thead class="thead-dark">
                <tr>
                <th>Fecha</th>
                <th>Pedido</th>
                <th>Producto</th>
                <th>Presentación</th>
                <th>Cantidad</th>
                <th>Acción</th>
                <th>Creado</th>
                </tr>
                </thead>
                <tbody id="ordersHistoryBody">
                </tbody>
            `;
            document.getElementById('orderHistoryDisplay').appendChild(table);
        }

        historyDataTable = $('#ordersHistoryTable').DataTable({
            language: {
                url: 'http://127.0.0.1:8081/assets/json/Spanish.json'
            },
            dom: '<"top">rt<"clear">',
            pageLength: 25,
            order: [[6, 'desc']], // Ordena por la columna createdAt (índice 6) de forma descendente
            columns: [
                { title: 'Fecha' },
                { title: 'Pedido' },
                { title: 'Producto' },
                { title: 'Presentación' },
                { 
                    title: 'Cantidad',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return new Intl.NumberFormat('es-MX', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0,
                                useGrouping: true
                            }).format(data);
                        }
                        return data;
                    }
                },
                { title: 'Acción' },
                { title: 'Creado', visible: false } // Columna oculta para createdAt
            ]
        });
    }

    let buttonText, buttonClass;
    if (order.confirmado === 1) {
        buttonText = 'Desconfirmar Pedido';
        buttonClass = 'btn-success';
    } else {
        buttonText = 'Confirmar Pedido';
        buttonClass = 'btn-primary';
    }

    const formattedDate = moment.utc(order.fecha).format('YYYY-MM-DD');
    const formattedCreatedAt = moment.utc(order.createdAt).format('YYYY-MM-DD HH:mm:ss');
    const rowNode = historyDataTable.row.add([
        formattedDate,
        order.numeroPedido ? order.numeroPedido.toUpperCase() : '',
        order.producto ? order.producto.toUpperCase() : '',
        order.presentacion,
        order.cantidad,
        `<button class="btn ${buttonClass} btn-sm toggle-confirm-btn" data-order-id="${order._id}" data-confirmed="${order.confirmado}">${buttonText}</button>`,
        formattedCreatedAt // Añade createdAt como una columna oculta
    ]).draw(false).node();

    const $rowNode = $(rowNode);

    if (order.confirmado === 1) {
        $rowNode.addClass('highlight-green');
    }
}


function playAlertSound() {
    const audio = new Audio('http://127.0.0.1:8081/assets/sounds/alert.mp3');
    
    audio.play().then(() => {
        console.log('Alerta de sonido reproducida exitosamente');
    }).catch((error) => {
        console.warn('No se pudo reproducir el sonido de alerta:', error);
    });
}



function flashScreen() {
    const flash = document.createElement('div');
    flash.style.position = 'fixed';
    flash.style.top = '0';
    flash.style.left = '0';
    flash.style.width = '100%';
    flash.style.height = '100%';
    flash.style.backgroundColor = 'white';
    flash.style.opacity = '0';
    flash.style.zIndex = '9999';
    flash.style.pointerEvents = 'none';
    document.body.appendChild(flash);

    let opacity = 0;
    let increasing = true;
    let flashCount = 0;

    const interval = setInterval(() => {
        if (increasing) {
            opacity += 0.1;
            if (opacity >= 1) {
                increasing = false;
            }
        } else {
            opacity -= 0.1;
            if (opacity <= 0) {
                flashCount++;
                if (flashCount >= 2) {
                    clearInterval(interval);
                    document.body.removeChild(flash);
                    return;
                }
                increasing = true;
            }
        }
        flash.style.opacity = opacity.toString();
    }, 50);
}


function showNotification(title, body) {
    // Verifica si el navegador soporta notificaciones
    if (!("Notification" in window)) {
        alert("Este navegador no soporta notificaciones de escritorio");
    }
    // Verifica si ya se han concedido los permisos
    else if (Notification.permission === "granted") {
        new Notification(title, { body: body });
    }
    // De lo contrario, pide permiso
    else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(function (permission) {
            if (permission === "granted") {
                new Notification(title, { body: body });
            }
        });
    }
}
document.addEventListener('DOMContentLoaded', (event) => {
    if (Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }
});

function toggleOrderConfirmation(orderId, currentConfirmationStatus) {
    const newConfirmationStatus = currentConfirmationStatus === 1 ? 0 : 1;
    //console.log(`Intentando ${newConfirmationStatus === 1 ? 'confirmar' : 'desconfirmar'} orden:`, orderId);
    
    fetch(`http://localhost:3000/api/orders/${orderId}/toggleConfirmation`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ confirmado: newConfirmationStatus })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        //console.log('Respuesta del servidor:', data);
        if (data.success) {
            //console.log(`Orden ${newConfirmationStatus === 1 ? 'confirmada' : 'desconfirmada'} exitosamente`);
            // Actualizar la UI
            const row = $(`button[data-order-id="${orderId}"]`).closest('tr');
            if (newConfirmationStatus === 1) {
                row.removeClass('highlight-orange highlight-red').addClass('highlight-green');
                $(`button[data-order-id="${orderId}"]`)
                    .text('Desconfirmar Pedido')
                    .removeClass('btn-primary')
                    .addClass('btn-success')
                    .data('confirmed', 1);
            } else {
                row.removeClass('highlight-green highlight-orange highlight-red');
                $(`button[data-order-id="${orderId}"]`)
                    .text('Confirmar Pedido')
                    .removeClass('btn-success')
                    .addClass('btn-primary')
                    .data('confirmed', 0);
            }
        } else {
            console.error(`No se pudo ${newConfirmationStatus === 1 ? 'confirmar' : 'desconfirmar'} la orden:`, data.message);
        }
    })
    .catch(error => {
        console.error(`Error al ${newConfirmationStatus === 1 ? 'confirmar' : 'desconfirmar'} la orden:`, error);
    });
}

// Actualizar el evento del botón
$(document).on('click', '.toggle-confirm-btn', function() {
    const orderId = $(this).data('order-id');
    const currentConfirmationStatus = $(this).data('confirmed');
    toggleOrderConfirmation(orderId, currentConfirmationStatus);
});