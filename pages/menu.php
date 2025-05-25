  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
      <div class="login-logo" style="display: flex; justify-content: center; align-items: center;">
        <a href="index.php" style="display: flex; align-items: center;">
          <img src="dist/img/asf.png" style="max-width: 100%; height: auto; width: 100px;">
        </a>
      </div>
    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="index.php" class="d-block"><?php echo ucfirst(strtolower($_SESSION['username'])); ?></a>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <?PHP
            if($_SESSION['username'] == 'Produccion' || $_SESSION['role'] == 'admin'){
              echo '
                <li class="nav-item">
                  <a href="altaProductos.php" class="nav-link">
                    <i class="nav-icon fas fa-barcode"></i>
                    <p>
                      Alta de productos
                    </p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="bajaProductos.php" class="nav-link">
                    <i class="nav-icon fas fa-dolly"></i> <!-- Carrito de carga -->

                    <p>
                      Salida de productos
                    </p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="generador.php" class="nav-link">
                    <i class="nav-icon fas fa-qrcode"></i>
                    <p>
                      Generador de códigos ASF
                    </p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="generadorq.php" class="nav-link">
                    <i class="nav-icon fas fa-qrcode"></i>
                    <p>
                      Generador de códigos Quinagro
                    </p>
                  </a>
                </li>
              ';
            }
          ?>
          <?PHP
            if($_SESSION['level'] == '2' || $_SESSION['role'] == 'admin'){
              // Fecha en que se añadió la etiqueta "Nuevo"
              $fechaEtiqueta = strtotime('2025-03-28'); // Reemplaza con la fecha actual
              $fechaActual = time();
              $diferenciaDias = floor(($fechaActual - $fechaEtiqueta) / (60 * 60 * 24));
              
              echo '
                <li class="nav-item">
                  <a href="inventario.php" class="nav-link">
                    <i class="nav-icon fa fa-pencil-square-o"></i>
                    <p>
                      Inventario
                      ' . ($diferenciaDias < 8 ? '<span class="right badge badge-danger">Nuevo</span>' : '') . '
                    </p>
                  </a>
                </li>';
              }
          ?>


          <?PHP
            if($_SESSION['level'] == '1' || $_SESSION['level'] == '2' || $_SESSION['level'] == '3'){
              echo '
                <li class="nav-item">
                  <a href="pedidos.php" class="nav-link">
                    <i class="nav-icon fa fa-sticky-note-o"></i>
                    <p>
                      Pedidos
                    </p>
                  </a>
                </li>
              ';
            }
          ?>  
          <?php
          if($_SESSION['level'] == '2'){
            echo'
                <li class="nav-item">
                  <a href="#" class="nav-link">
                    <i class="nav-icon fa fa-gears"></i>
                    <p>
                      Producción
                      <i class="right fas fa-angle-left"></i>
                    </p>
                  </a>
                  <ul class="nav nav-treeview">
                    <li class="nav-item">
                      <a href="almacen.php" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Almacen</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="production.php" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Produccion</p>
                      </a>
                    </li>
                  </ul>
                </li>
              ';
            }
          ?>
          <?PHP
            if($_SESSION['role'] == 'admin' && $_SESSION['username'] != 'Almacen' && $_SESSION['username'] != 'Produccion' ){
              // Fecha en que se añadió la etiqueta "Nuevo"
              $fechaEtiquetaVendedores = strtotime('2025-03-30'); // Reemplaza con la fecha actual
              $diferenciaDiasVendedores = floor(($fechaActual - $fechaEtiquetaVendedores) / (60 * 60 * 24));
              
              echo '
                <li class="nav-item">
                  <a href="vendedores.php" class="nav-link">
                    <i class="nav-icon fa fa-user-circle"></i>
                    <p>
                      Vendedores ASF
                      ' . ($diferenciaDiasVendedores < 8 ? '<span class="right badge badge-danger">Nuevo</span>' : '') . '
                    </p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="vendedoresq.php" class="nav-link">
                    <i class="nav-icon fa fa-user-circle"></i>
                    <p>
                      Vendedores Quinagro
                      ' . ($diferenciaDiasVendedores < 8 ? '<span class="right badge badge-danger">Nuevo</span>' : '') . '
                    </p>
                  </a>
                </li>
              ';
            }
          ?>
          

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>