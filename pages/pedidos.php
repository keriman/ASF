<!-- OPCIÓN 1: Agregar el modal en el archivo principal (pages/pedidos.php) -->
<!-- Reemplazar el contenido de pages/pedidos.php con esto: -->

<style>
/* Estilos para el botón de información en el card-tools */
.info-button {
    background-color: #17a2b8 !important;
    color: white !important;
    border: none;
    border-radius: 4px;
    padding: 6px 10px;
    font-size: 14px;
    cursor: pointer;
    margin-right: 5px;
    transition: all 0.3s ease;
}

.info-button:hover {
    background-color: #138496 !important;
    color: white !important;
    transform: scale(1.05);
}

.info-button:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(23, 162, 184, 0.25);
}

/* Estilos para el modal */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 1040;
}

.modal {
    z-index: 1050;
}

.modal-content {
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.modal-header {
    background-color: #17a2b8;
    color: white;
    border-radius: 8px 8px 0 0;
}

.modal-header .close {
    color: white !important;
    opacity: 0.8;
    font-size: 1.5rem;
}

.modal-header .close:hover {
    opacity: 1;
}

/* TEXTO OSCURO PARA TODO EL MODAL */
.modal-body {
    color: #212529 !important;
}

.modal-body p {
    color: #212529 !important;
}

.modal-body h6 {
    color: #212529 !important;
}

.criteria-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.criteria-title {
    font-weight: bold;
    color: #212529 !important; /* Texto oscuro */
    margin-bottom: 10px;
}

.status-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.status-item span {
    color: #212529 !important; /* Texto oscuro */
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    color: white !important; /* Texto blanco en badges */
    font-size: 12px;
    font-weight: bold;
    margin-right: 10px;
    min-width: 25px;
    text-align: center;
}

.status-10 { 
    background-color: #ffc107; 
    color: #212529 !important; /* Texto oscuro para amarillo */
}
.status-20 { background-color: #6f42c1; }
.status-30 { background-color: #28a745; }
.status-40 { background-color: #007bff; }
.status-50 { background-color: #dc3545; }

.example-box {
    background-color: #e7f3ff;
    border-left: 4px solid #007bff;
    padding: 15px;
    margin-top: 15px;
    color: #212529 !important; /* Texto oscuro */
}

.example-box p {
    color: #212529 !important;
}

.highlight {
    background-color: #fff3cd;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: bold;
    color: #212529 !important; /* Texto oscuro */
}

/* Texto oscuro para código */
code {
    color: #212529 !important;
}

/* Asegurar que todos los textos sean oscuros */
#infoModal * {
    color: #212529;
}

/* Excepciones para elementos que deben mantener color específico */
#infoModal .modal-header,
#infoModal .modal-header *,
#infoModal .status-badge,
#infoModal .btn {
    color: inherit;
}

#infoModal .alert-info {
    color: #0c5460 !important;
}
</style>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card" style="height: 100%;">
                        <div class="card-header">
                            <h5 class="card-title">Pedidos</h5>
                            <div class="card-tools">
                                <button type="button" class="btn info-button" data-toggle="modal" data-target="#infoModal" title="Información sobre criterios de filtrado">
                                    <i class="fas fa-info"></i> Info
                                </button>

                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body" style="height: calc(100vh - 250px); padding: 0;">
                            <iframe src="PedidosASF/index.php" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                        <!-- ./card-body -->            
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!--/. container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal de información -->
<div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="infoModalLabel">
                    <i class="fas fa-info-circle"></i> Criterios de Filtrado de Pedidos
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="criteria-section">
                    <div class="criteria-title">📋 CRITERIOS DE VISUALIZACIÓN:</div>
                    
                    <!-- Status que siempre se muestran -->
                    <div style="margin-bottom: 20px;">
                        <h6><strong>✅ Siempre visibles:</strong></h6>
                        <div class="status-item">
                            <span class="status-badge status-10">10</span>
                            <span><strong>Pendiente</strong> - Sin restricción de tiempo</span>
                        </div>
                        <div class="status-item">
                            <span class="status-badge status-20">20</span>
                            <span><strong>Cancelado</strong> - Sin restricción de tiempo</span>
                        </div>
                        <div class="status-item">
                            <span class="status-badge status-30">30</span>
                            <span><strong>En Almacén</strong> - Sin restricción de tiempo</span>
                        </div>
                    </div>

                    <!-- Status con restricción de tiempo -->
                    <div>
                        <h6><strong>⏰ Con límite de tiempo (últimos 5 días):</strong></h6>
                        <div class="status-item">
                            <span class="status-badge status-40">40</span>
                            <span><strong>Entregado Completo</strong> - Solo si fecha de salida ≥ últimos 5 días</span>
                        </div>
                        <div class="status-item">
                            <span class="status-badge status-50">50</span>
                            <span><strong>Entregado Incompleto</strong> - Solo si fecha de salida ≥ últimos 5 días</span>
                        </div>
                    </div>
                </div>

                <div class="criteria-section">
                    <div class="criteria-title">🔍 EXPLICACIÓN TÉCNICA:</div>
                    <p>
                        La condición <code class="highlight">date_add(fecha_salida, interval -5 day)</code> significa:
                    </p>
                    <p>
                        <strong>"fecha_salida - 5 días ≥ fecha_actual"</strong>
                    </p>
                    <p>
                        En otras palabras, solo muestra pedidos entregados cuya fecha de salida sea de los últimos 5 días hacia atrás.
                    </p>
                </div>

                <div class="example-box">
                    <div class="criteria-title">💡 EJEMPLO PRÁCTICO:</div>
                    <p><strong>Si hoy es:</strong> <span id="currentDate"></span></p>
                    <p><strong>Se mostrarán pedidos entregados (40, 50) con fecha de salida desde:</strong> <span id="limitDate"></span></p>
                    <p><strong>Los pedidos entregados anteriores a esa fecha:</strong> <span style="color: #dc3545; font-weight: bold;">❌ NO se mostrarán</span></p>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb"></i> 
                    <strong>Nota:</strong> Esta información se aplica al módulo de <strong>Gerencia</strong>. 
                    El módulo de Almacén tiene criterios diferentes.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-info" data-dismiss="modal">
                    <i class="fas fa-check"></i> Entendido
                </button>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Calcular fechas dinámicamente
    function updateDates() {
        const today = new Date();
        const limitDate = new Date();
        limitDate.setDate(today.getDate() - 5);

        // Formatear fechas en español
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };

        const todayStr = today.toLocaleDateString('es-ES', options);
        const limitStr = limitDate.toLocaleDateString('es-ES', options);

        // Actualizar el modal
        $('#currentDate').text(todayStr);
        $('#limitDate').text(limitStr + ' (hace 5 días)');
    }

    // Actualizar fechas cuando se abre el modal
    $('#infoModal').on('show.bs.modal', function () {
        updateDates();
    });

    // También actualizar al cargar la página
    updateDates();

    // Log para debug
    console.log('Modal de información de pedidos cargado correctamente');
});
</script>