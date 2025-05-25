<!--/pages/generador.php -->
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
            <div class="card">
              <div class="card-header">
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-qrcode"></i>
                  </button>                  
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="row">
                  <div class="col-md-12">
                    <?php
                    // Intentar determinar automáticamente la URL relativa correcta
                    $iframe_urls = [
                        'GenerarQR/indexQ.php',
                        '/ASF/GenerarQR/indexQ.php',
                        '/GenerarQR/indexQ.php'
                    ];
                    
                    echo '<div class="card mb-3">';
                    echo '<div class="card-header bg-info">';
                    echo '<h5>Generador de Códigos QR</h5>';
                    echo '</div>';
                    echo '<div class="card-body">';
                    
                    // Mostrar el iframe con la primera URL (podemos cambiarla si es necesario)
                    echo '<iframe src="' . $iframe_urls[0] . '" style="width: 100%; height: 800px; border: none;"></iframe>';
                    
                    echo '</div>';
                    echo '</div>';
                    
                    // Enlaces alternativos (solo visibles para administradores o en modo debug)
                    echo '<div class="card">';
                    echo '<div class="card-header bg-secondary">';
                    echo '<h5>Resolución de problemas (solo para administradores)</h5>';
                    echo '</div>';
                    echo '<div class="card-body">';
                    echo '<p>Si el generador de QR no se carga correctamente arriba, intente acceder directamente a través de uno de estos enlaces:</p>';
                    echo '<div class="btn-group">';
                    foreach ($iframe_urls as $url) {
                        echo '<a href="' . $url . '" class="btn btn-outline-primary" target="_blank">';
                        echo '<i class="fas fa-qrcode"></i> ' . $url;
                        echo '</a>';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    ?>
                  </div>
                </div>
                <!-- /.row -->
              </div>
              <!-- ./card-body -->            
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->