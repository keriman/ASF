<?php
	//almacen\index.php
    include '../conexiones/database.php';

	// Agregar este array al inicio de tu código PHP, después de la conexión a la base de datos
	$availableColors = ['blue', 'green', 'yellow', 'brown', 'purple', 'orange'];
	
	session_start();
	if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
	    header('Location: ../login.php');
	}
	if ($_SESSION["tipo"] != "almacen") {
	  header('Location: ../index.php?active_tab=' . $tab);
	}

	$conectar=mysql_connect($host, $user, $clave);
	mysql_select_db($datbase, $conectar);
	mysql_set_charset('utf8', $conectar);

	date_default_timezone_set ("America/Mexico_City");


	function getVendorsForCurrentUser($usuario) {
	    // Consulta que obtiene SOLO vendedores que existen EXACTAMENTE en las tablas de catálogo
	    $query = "
	        SELECT DISTINCT ap.vendedor
	        FROM adm_pedidos ap
	        WHERE ap.vendedor != '' 
	          AND ap.status IN (30, 40, 50)
	          AND (
	              ap.vendedor IN (SELECT nombre FROM vendedores)
	              OR 
	              ap.vendedor IN (SELECT nombre FROM vendedores_quinagro)
	          )
	        ORDER BY ap.vendedor
	    ";
	    
	    $result = mysql_query($query) or die("Error en la consulta: " . mysql_error());
	    
	    $authorizedVendors = array();
	    while ($row = mysql_fetch_array($result)) {
	        $authorizedVendors[] = $row['vendedor'];
	    }
	    
	    return $authorizedVendors;
	}

	function getExcludedVendors() {
	    $query = "
	        SELECT DISTINCT ap.vendedor, COUNT(*) as total_pedidos
	        FROM adm_pedidos ap
	        WHERE ap.vendedor != '' 
	          AND ap.status IN (30, 40, 50)
	          AND ap.vendedor NOT IN (SELECT nombre FROM vendedores)
	          AND ap.vendedor NOT IN (SELECT nombre FROM vendedores_quinagro)
	        GROUP BY ap.vendedor
	        ORDER BY ap.vendedor
	    ";
	    
	    $result = mysql_query($query);
	    $excluded = array();
	    
	    while ($row = mysql_fetch_array($result)) {
	        $excluded[] = array(
	            'nombre' => $row['vendedor'],
	            'pedidos' => $row['total_pedidos']
	        );
	    }
	    
	    return $excluded;
	}

	function getVendorNameMapping() {
	    $query = "SELECT nombre_incorrecto, nombre_correcto FROM vendedor_aliases";
	    $result = mysql_query($query);
	    
	    $mapping = array();
	    while ($row = mysql_fetch_array($result)) {
	        $mapping[$row['nombre_incorrecto']] = $row['nombre_correcto'];
	    }
	    
	    return $mapping;
	}

	function getUnmappedVendors() {
	    $query = "
	        SELECT DISTINCT ap.vendedor, 
	               CASE 
	                   WHEN v.nombre IS NOT NULL THEN 'En vendedores'
	                   WHEN vq.nombre IS NOT NULL THEN 'En vendedores_quinagro'
	                   WHEN va.nombre_correcto IS NOT NULL THEN 'Tiene alias'
	                   ELSE 'NO MAPEADO'
	               END as estado
	        FROM adm_pedidos ap
	        LEFT JOIN vendedores v ON ap.vendedor = v.nombre
	        LEFT JOIN vendedores_quinagro vq ON ap.vendedor = vq.nombre
	        LEFT JOIN vendedor_aliases va ON ap.vendedor = va.nombre_incorrecto
	        WHERE ap.vendedor != '' AND ap.status IN (30, 40, 50)
	        ORDER BY estado, ap.vendedor
	    ";
	    
	    $result = mysql_query($query);
	    $vendors = array();
	    
	    while ($row = mysql_fetch_array($result)) {
	        $vendors[] = array(
	            'nombre' => $row['vendedor'],
	            'estado' => $row['estado']
	        );
	    }
	    
	    return $vendors;
	}


	function getVendorBaseColor($vendorName) {
	    global $availableColors;
	    // Usar el hash del nombre para obtener un índice consistente
	    $hash = crc32($vendorName);
	    $colorIndex = abs($hash % count($availableColors));
	    return $availableColors[$colorIndex];
	}

	// Agregar esta función después de la conexión a la base de datos
	function getDateStyle($deliveryDate) {
	    // Convertir la fecha de entrega a timestamp
	    $deliveryTimestamp = strtotime($deliveryDate);
	    $today = strtotime('today');
	    
	    // Obtener el inicio y fin de cada período
	    $currentWeekStart = strtotime('monday this week', $today);
	    $currentWeekEnd = strtotime('sunday this week', $today);
	    
	    $nextWeekStart = strtotime('monday next week', $today);
	    $nextWeekEnd = strtotime('sunday next week', $today);
	    
	    $twoWeeksStart = strtotime('monday next week', $nextWeekStart);
	    $twoWeeksEnd = strtotime('sunday next week', $nextWeekStart);
	    
	    // Si la fecha es anterior a hoy, retornar estilo normal
	    if ($deliveryTimestamp < $today) {
	        return '';
	    }
	    
	    // Si la fecha es de esta semana, texto rojo
	    if ($deliveryTimestamp >= $today && $deliveryTimestamp <= $currentWeekEnd) {
	        return 'style="color: red; font-weight: bold;"';
	    }
	    
	    // Si la fecha es de la próxima semana, texto naranja
	    if ($deliveryTimestamp >= $nextWeekStart && $deliveryTimestamp <= $nextWeekEnd) {
	        return 'style="color: orange; font-weight: bold;"';
	    }
	    
	    // Si la fecha es de dos semanas después, texto verde
	    if ($deliveryTimestamp >= $twoWeeksStart && $deliveryTimestamp <= $twoWeeksEnd) {
	        return 'style="color: green; font-weight: bold;"';
	    }
	    
	    // Para fechas más allá de dos semanas, sin estilo
	    return '';
	}


	$usuario = $_SESSION["username"];
	$authorizedVendors = getVendorsForCurrentUser($usuario);


	$subs_usuario = $usuario;

	$usuario_cons = $usuario;

	$vista = 'SEMANA';	
	if (isset($_GET['jj'])) {
		$vista = htmlentities($_GET['jj']);
	}

	if (isset($_GET['vv']) and isset($_GET['dd'])) {
	    $concepto_cons = htmlentities($_GET['dd']);
	    $dato_cons = htmlentities($_GET['vv']);
	}

	if ($dato_cons > "" and $concepto_cons > "") {
		if ($concepto_cons == "folio") {
			$criterio =	"WHERE adm_pedidos.folio = '".$dato_cons."'";
		} elseif ($concepto_cons == "vendedor") {
				if (strtoupper($dato_cons) == "TODOS") {
						$criterio = "WHERE ";
				} else {
						$criterio = "WHERE vendedor LIKE '%".strtoupper($dato_cons)."%' AND";
				}
		} elseif ($concepto_cons == "destino") {
				$criterio = "WHERE destino = '".strtoupper($dato_cons)."' AND";
		} elseif ($concepto_cons == "salida") {
				$criterio = "WHERE fecha_salida = '".$dato_cons."' AND";
		} else {
				$dato_cons = "TODOS";
				$concepto_cons = "vendedor";
				$criterio = "WHERE ";
		}
	} elseif (isset($_GET['bb'])) {
				$dato_cons = htmlentities($_GET['bb']);
				$concepto_cons = "vendedor";
				$criterio = "WHERE vendedor LIKE '%".$dato_cons."%' AND";		
	} else {
				$dato_cons = "TODOS";
				$concepto_cons = "vendedor";
				$criterio = "WHERE ";
	}

	if ($concepto_cons == "folio") {
			$criterio = $criterio;
	} else {
		// Modificado para mostrar todos los pedidos incluyendo OficinaX y Quinagro
		$criterio = $criterio." status in (30, 40, 50) AND ";

		if ($vista == "HOY") {
			$criterio = $criterio."(FR > CURDATE())";
		} elseif ($vista == "PEND") {
			$criterio = $criterio."(status = 30)";
		} else {
			$criterio = $criterio."(WEEKOFYEAR(fecha_salida) = WEEKOFYEAR(CURDATE()))";
		}		
	}
	
	// Consulta modificada para incluir todos los pedidos sin filtrar por vendedor específico
	$buscar = "SELECT folio, vendedor, destino, cliente, ruta, fecha_salida, status FROM adm_pedidos ".$criterio." ORDER BY vendedor, status, fecha_salida, folio LIMIT 1000";

	
	$resultado=mysql_query($buscar)	or die("Problemas en el select: ".mysql_error());
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta http-equiv="refresh" content="600">
    <meta charset="utf-8">
    <title>AgroSantaFe/almacen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de Pedidos">
	<meta name="author" content="Eduardo Aburto Salas" />
	<link rel="shortcut icon" href="../images/ico_agrosantafe.ico"> 
    <link rel="stylesheet" href="../dist/css/site.min.css">
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,800,700,400italic,600italic,700italic,800italic,300italic" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="../dist/js/site.min.js"></script>
		<script type="text/javascript" charset="UTF-8">
		function submitform()
		{
		    document.forms["form5"].submit();
		};
	</script>

<link href="https://netdna.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
<style type="text/css">
    	body{margin-top:10px;}

.card-big-shadow {
    max-width: 320px;
    position: relative;
}

.coloured-cards .card {
    margin-top: 10px;
}

.card[data-radius="none"] {
    border-radius: 0px;
}
.card {
    border-radius: 8px;
    box-shadow: 0 2px 2px rgba(204, 197, 185, 0.5);
    background-color: #FFFFFF;
    color: #252422;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}


.card[data-background="image"] .title, .card[data-background="image"] .stats, .card[data-background="image"] .category, .card[data-background="image"] .description, .card[data-background="image"] .content, .card[data-background="image"] .card-footer, .card[data-background="image"] small, .card[data-background="image"] .content a, .card[data-background="color"] .title, .card[data-background="color"] .stats, .card[data-background="color"] .category, .card[data-background="color"] .description, .card[data-background="color"] .content, .card[data-background="color"] .card-footer, .card[data-background="color"] small, .card[data-background="color"] .content a {
    color: #000000;
}
.card.card-just-text .content {
    padding: 10px 12px;
    text-align: left;
}
.card .content {
    padding: 10px 10px 10px 10px;
}
.card[data-color="blue"] .category {
    color: #000;
}

.card .category, .card .label {
    font-size: 14px;
    margin-bottom: 0px;
}
.card-big-shadow:before {
    background-image: url("http://static.tumblr.com/i21wc39/coTmrkw40/shadow.png");
    background-position: center bottom;
    background-repeat: no-repeat;
    background-size: 100% 100%;
    bottom: -12%;
    content: "";
    display: block;
    left: -12%;
    position: absolute;
    right: 0;
    top: 0;
    z-index: 0;
}
h4, .h4 {
    font-size: 1.5em;
    font-weight: 600;
    line-height: 1.2em;
}
h6, .h6 {
    font-size: 0.9em;
    font-weight: 600;
    text-transform: uppercase;
}
.card .description {
    font-size: 12px;
    color: #66615b;
}
.content-card{
    margin-top:5px;    
}
a:hover, a:focus {
    text-decoration: none;
}

/*======== COLORS ===========*/
.card[data-color="blue"] {
    background: #b8d8d8;
}
.card[data-color="blue"] .description {
    color: #000;
}

.card[data-color="green"] {
    background: #d5e5a3;
}
.card[data-color="green"] .description {
    color: #000;
}
.card[data-color="green"] .category {
    color: #000;
}

.card[data-color="yellow"] {
    background: #ffe28c;
}
.card[data-color="yellow"] .description {
    color: #000;
}
.card[data-color="yellow"] .category {
    color: #000;
}

.card[data-color="brown"] {
    background: #d6c1ab;
}
.card[data-color="brown"] .description {
    color: #000;
}
.card[data-color="brown"] .category {
    color: #000;
}

.card[data-color="purple"] {
    background: #baa9ba;
}
.card[data-color="purple"] .description {
    color: #000;
}
.card[data-color="purple"] .category {
    color: #000;
}

.card[data-color="orange"] {
    background: #ff8f5e;
}
.card[data-color="orange"] .description {
    color: #000;
}
.card[data-color="orange"] .category {
    color: #000;
}
.checkbox-container {
    position: absolute;
    top: 5px;
    right: 25px;
    z-index: 2;
}
.select-card {
    transform: scale(1.3);
    cursor: pointer;
}
.vendor-tabs-container {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 15px;
}

.vendor-tab {
    padding: 8px 12px;
    background-color: #f8f8f8;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
}

.vendor-tab:hover {
    background-color: #e8e8e8;
}

.vendor-tab.active {
    background-color: #337ab7;
    color: white !important;
    border-color: #2e6da4;
}

/* Estilos adicionales para mejorar la visualización */
.container.bootstrap.snippets.bootdeys {
    margin-top: 15px;
}

/* Agregar estilo para diferenciar pedidos de OficinaX y Quinagro */
.vendedor-oficinax {
    border-left: 5px solid #ff7043; /* Borde naranja para OficinaX */
}
.vendedor-quinagro {
    border-left: 5px solid #8bc34a; /* Borde verde para Quinagro */
}
.input-group {
    margin-bottom: 15px;
}

.input-group-addon {
    background-color: #f8f9fa;
    border-right: none;
}

#vendorSearch {
    border-left: none;
    box-shadow: none;
    border-radius: 0 4px 4px 0;
}

#vendorSearch:focus {
    border-color: #ced4da;
    outline: 0;
    box-shadow: none;
}

/* Opcional: Estilo para vendedores autorizados */
.vendor-tab.authorized-vendor {
    border-left: 3px solid #28a745; /* Borde verde para vendedores autorizados */
}

/* Opcional: Estilo para vendedores no autorizados */
.vendor-tab:not(.authorized-vendor) {
    border-left: 3px solid #ffc107; /* Borde amarillo para vendedores no autorizados */
}
    </style>
<?php
// Verificar si se recibió un parámetro active_tab
$active_tab = isset($_GET['active_tab']) ? $_GET['active_tab'] : '';

if (!empty($active_tab)) {
    echo '<script>
        // Establecer la pestaña activa que viene como parámetro
        document.addEventListener("DOMContentLoaded", function() {
            localStorage.setItem("selectedVendorTab", "' . $active_tab . '");
        });
    </script>';
}
?>

</head>
<body>
<!--<button class="btn btn-danger" type="submit"  onclick="window.location.href='../usuarios/salir.php';">Salir</button>-->		
	<div class="row">
      		<div class="content-row">
          		<div class="panel panel-default">
            			<div class="panel-body">
					<form id="form5" action="" method="GET">
						<p>
					       		<div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
					              		<select name="dd" id="dd" class="form-control">
					                		<option value="ninguno" <? if ($concepto_cons == 'ninguno'){echo('selected');} ?>></option>
					                		<option value="folio" <? if ($concepto_cons == 'folio'){echo('selected');} ?>>Folio</option>
					                		<option value="vendedor" <? if ($concepto_cons == 'vendedor'){echo('selected');} ?>>Vendedor</option>
					                		<option value="destino" <? if ($concepto_cons == 'destino'){echo('selected');} ?>>Destino</option>
					                		<option value="salida" <? if ($concepto_cons == 'salida'){echo('selected');} ?>>Fecha de Salida</option>
					              		</select>
					        	</div>
						      	<div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
									<input type="text" name="vv" id="vv" class="form-control" value="<?=$dato_cons;?>" autocomplete="off">
						      	</div>
					       		<div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
					              		<select name="jj" id="jj" class="form-control">
					                		<option value="HOY" <? if ($vista == 'HOY'){echo('selected');} ?>>Capturados HOY</option>
					                		<option value="SEMANA" <? if ($vista == 'SEMANA'){echo('selected');} ?>>Para la semana</option>
					                		<option value="PEND" <? if ($vista == 'PEND'){echo('selected');} ?>>Pendientes</option>
					              		</select>
					        	</div>
						      	<div class="col-xs-12 col-sm-6 col-md-2 col-lg-2">
						          	<button type="submit" class="btn btn-primary btn-block">Consultar</button>
						      	</div>
				    		</p>
			 		</form>
		  		</div>
			</div>
			<div class="content-row">
			    <div class="panel panel-default">
			        <div class="panel-body">
			            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-4">
			                <div class="input-group">
			                    <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
			                    <input type="text" id="vendorSearch" class="form-control" placeholder="Buscar vendedor...">
			                </div>
			            </div>
			        </div>
			    </div>
			</div>
			<div class="content-row">
			    <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 col-xl-1">
			        <a href="exportar_excel.php?vendedor=TODOS" class="btn btn-success" role="button">Exportar Todos</a>
			    </div>
			</div>
			<div class="content-row">
			    <div class="panel panel-default">
			        <div class="panel-body">
			            <!-- Pestañas en múltiples filas -->
						<div class="vendor-tabs-container">
						    <div class="vendor-tab active" data-vendor="todos">TODOS</div>
						    <?php 
						    // Generar pestañas SOLO para vendedores que están exactamente en catálogo
						    $authorizedVendors = getVendorsForCurrentUser($usuario);
						    
						    foreach ($authorizedVendors as $vendor) {
						        // Normalizar el nombre SOLO para el atributo data-vendor (para JavaScript)
						        $vendorDataAttr = preg_replace('/[^a-zA-Z0-9_]/', '_', $vendor);
						        
						        echo '<div class="vendor-tab authorized-vendor" data-vendor="' . $vendorDataAttr . '">' . htmlspecialchars($vendor) . '</div>';
						    }
						    ?>
						</div>
			        </div>
			    </div>
			</div>
		</div>
	</div>

<?
if (mysql_num_rows($resultado)>0) {
    $numreg = 0;
    while($rowc = mysql_fetch_array($resultado)) { 
        if ($anterior != $rowc['vendedor']) {
            // Si no es el primer vendedor, cerramos la estructura anterior
            if ($numreg > 0) {
                echo("</div>"); // Cerrar div row
                echo("</form>"); // Cerrar form
                echo("</div>"); // Cerrar container
            }

            $vendorClass = (empty(trim($rowc['vendedor']))) ? 'empty-vendor-container' : '';
            
            // Añadir clases para identificar vendedores especiales
            $specialClass = '';
            if (stripos($rowc['vendedor'], 'OFICINAX') !== false) {
                $specialClass = 'vendedor-oficinax';
            } elseif (stripos($rowc['vendedor'], 'QUINAGRO') !== false) {
                $specialClass = 'vendedor-quinagro';
            }

            // Abrir nueva estructura para el vendedor actual
            echo("<div class='container bootstrap snippets bootdeys $vendorClass $specialClass'>");
            echo("<form action='exportar_excel.php' method='post' id='form_".preg_replace('/[^a-zA-Z0-9_]/', '_', $rowc['vendedor'])."'>");
            echo("<input type='hidden' name='vendedor' value='".htmlspecialchars($rowc['vendedor'])."'>");
            echo("<div class='row'>");
            echo("<div style='display: flex; justify-content: space-between; align-items: center; width: 100%;'>");
            echo("<div>");
            echo("<H4>".$rowc['vendedor']."</H4>");
            echo("<label style='margin-left: 20px;'>");
            echo("<input type='checkbox' class='select-all' data-vendor='".htmlspecialchars(preg_replace('/[^a-zA-Z0-9_]/', '_', $rowc['vendedor']))."'> Seleccionar Todos");
            echo("</label>");
            echo("</div>");
            echo("<button type='submit' class='btn btn-success btn-sm'>");
            echo("<i class='glyphicon glyphicon-export'></i> Exportar Seleccionados</button>");
            echo("</div>");
            echo("</div>"); // Cerrar el div de la cabecera
            
            // Nuevo contenedor para las tarjetas
            echo("<div class='row'>");
        }
        
        // IMPORTANTE: ELIMINAR LA PRIMERA TARJETA VACÍA
        // Mantener solo esta tarjeta:
        
        // Calcular el color de la tarjeta
        $baseColor = getVendorBaseColor($rowc['vendedor']);
        
        // Colores especiales para OficinaX y Quinagro
        if (stripos($rowc['vendedor'], 'OFICINAX') !== false) {
            $baseColor = 'orange'; // Color específico para OficinaX
        } elseif (stripos($rowc['vendedor'], 'QUINAGRO') !== false) {
            $baseColor = 'green'; // Color específico para Quinagro
        }
        
        if ($rowc['status'] == 50) {
            $color = 'brown';  // Mantener brown para entregado incompleto
        } elseif ($rowc['status'] == 40) {
            $color = $baseColor;  // Color del vendedor para entregado completo
        } elseif ($rowc['status'] == 30) {
            if ($rowc['fecha_salida'] == date("Y-m-d")) {
                $color = 'orange';  // Mantener orange para salida hoy
            } else {
                $color = $baseColor;  // Color del vendedor para en almacén
            }
        } elseif ($rowc['status'] == 20) {
            $color = 'purple';  // Mantener purple para cancelado
        } else {
            $color = $baseColor;  // Color del vendedor para pendiente
        }
        

        if ($rowc['folio'] == 3343 ) {
		    // Este es el pedido problemático o un pedido sin vendedor
		    echo("<div class='col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 content-card no-vendor-card' data-folio='".$rowc['folio']."'>");
		} else {
		    // Pedido normal
		    echo("<div class='col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 content-card'>");
		}

        echo("<div class='checkbox-container'>");
        echo("<input type='checkbox' name='pedidos[]' value='".$rowc['folio']."' class='select-card'>");
        echo("</div>");
        echo("<div class='card-big-shadow'>");
        echo("<div class='card card-just-text' data-background='color' data-color='".$color."' data-radius='none'>");
        echo("<div class='content'>");
        
        // Contenido de la tarjeta
        echo("<h6 class='category' ".getDateStyle($rowc['fecha_salida']).">Fecha de Salida: ".$rowc['fecha_salida']."</h6>");
        echo("<h4 class='title'>Folio: ".$rowc['folio']."</h4>");
        echo("<h6 class='category'>Destino: ".$rowc['destino']."</h6>");
        
        // Mostrar cliente y ruta si están definidos
        if (!empty($rowc['cliente'])) {
            echo("<h6 class='category'>Cliente: ".$rowc['cliente']."</h6>");
        }
        if (!empty($rowc['ruta'])) {
            echo("<h6 class='category'>Ruta: ".$rowc['ruta']."</h6>");
        }

        // Mostrar productos
        $articulo = 0;
        $detalle = "SELECT id, folio, producto, presentacion, cantidad FROM prc_pedidos WHERE procesado = 0 AND folio = ".$rowc['folio']." LIMIT 20";
        $res_detalle = mysql_query($detalle) or die("Problemas en el select: ".mysql_error());
        if (mysql_num_rows($res_detalle) > 0) {
            echo("<p class='description'><b>Productos:</b></p>");
            while($rowd = mysql_fetch_array($res_detalle)) { 
                $articulo += 1;
                echo("<p class='description'>".$articulo."./ ".$rowd['producto']." / ".$rowd['presentacion']." / Cant: ".$rowd['cantidad']."</p>");
            }
        }
        
        // Mostrar status
        echo("<h6 class='category'>Status: ");
        if ($rowc['status'] == 50) {
            echo("Entregado Incompleto");
        } elseif ($rowc['status'] == 40) {
            echo("Entregado Completo");
        } elseif ($rowc['status'] == 30) {
            echo("En Almacen");
        } elseif ($rowc['status'] == 20) {
            echo("Cancelado");
        } else {
            echo("Pendiente");
        }
        echo("</h6>");
        
        // Mostrar acciones
        if ($rowc['status'] == 30) {
            echo("<br/><p class='description'>Acciones: ");
            //echo("  <a href='alta/regresar.php?gg=".$rowc['folio']."'><img src='../images/regresar.png' width='20' height='20' title='Regresar a captura' /></a>");
            //echo(" _ _ _ ");
            echo("  <a href='alta/entregado_incompleto.php?gg=".$rowc['folio']."'><img src='../images/aprobado_marron.png' width='40' height='20' title='Entregado Incompleto' /></a>");
            echo(" _ _ _ ");
            echo("  <a href='alta/entregado_completo.php?gg=".$rowc['folio']."'><img src='../images/aprobado.png' width='20' height='20' title='Entregado Completo' /></a>");
            echo("</p>");
        }
        
        // Mostrar observaciones
        $obs = "SELECT observaciones, modificada FROM obs_pedidos WHERE folio = ".$rowc['folio']." ORDER BY FR LIMIT 20";
        $res_obs = mysql_query($obs) or die("Problemas en el select: ".mysql_error());
        $num_obs = 0;
        if (mysql_num_rows($res_obs) > 0) {
            echo("<p class='description'><b>Observaciones:</b></p>");
            while($rowo = mysql_fetch_array($res_obs)) { 
                $num_obs += 1;
                $estilo = "";
                
                // Resaltar observaciones pendientes
                if (strpos($rowo['observaciones'], 'PENDIENTE:') === 0) {
                    $estilo = "style='color: #e65100; font-weight: bold;'";
                }
                
                // Mostrar la observación con el estilo adecuado
                echo("<p class='description' ".$estilo.">".$num_obs.".- ".$rowo['observaciones']);
                
                // Añadir la marca "M" para observaciones modificadas
                if ($rowo['modificada'] == 1) {
                    echo(" <span style='color: red; font-weight: bold;'>M</span>");
                }
                
                echo("</p>");
            }
        }
        
        // Cerrar la tarjeta
        echo("</div>"); // Cerrar content
        echo("</div>"); // Cerrar card
        echo("</div>"); // Cerrar card-big-shadow
        echo("</div>"); // Cerrar content-card
        
        $numreg += 1;
        $anterior = $rowc['vendedor'];
    }
    
    // Cerrar el último conjunto de elementos si hubo al menos un registro
    if ($numreg > 0) {
        echo("</div>"); // Cerrar row de las tarjetas
        echo("</form>"); // Cerrar form
        echo("</div>"); // Cerrar container
    }
}
?>

		</div>
</div>

<script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
<script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Lista de vendedores autorizados (generada desde PHP)
    const authorizedVendors = [
        <?php 
        foreach ($authorizedVendors as $index => $vendor) {
            $normalizedVendor = preg_replace('/[^a-zA-Z0-9_]/', '_', $vendor);
            echo '"' . $normalizedVendor . '"';
            if ($index < count($authorizedVendors) - 1) echo ',';
        }
        ?>
    ];
    
    // Función para decodificar entidades HTML
    function decodeHtmlEntities(text) {
        if (!text) return '';
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        return textArea.value;
    }

    // Función para normalizar nombres (JavaScript, no PHP)
    function normalizeVendorName(name) {
        name = decodeHtmlEntities(name);
        
        return name.trim()
                  .toUpperCase()
                  .normalize('NFD')
                  .replace(/[\u0300-\u036f]/g, '') // Eliminar acentos
                  .replace(/\s+/g, '_')           // Reemplazar espacios con guiones bajos
                  .replace(/[^a-zA-Z0-9_]/g, ''); // Eliminar caracteres especiales
    }
    
    // Función para aplicar el filtro según el vendedor seleccionado
    function applyVendorFilter(vendorId) {
        console.log("Filtrando por vendedor:", vendorId);
        
        // Activar la pestaña seleccionada
        const tabs = document.querySelectorAll('.vendor-tab');
        tabs.forEach(function(t) {
            t.classList.remove('active');
            if (t.getAttribute('data-vendor') === vendorId) {
                t.classList.add('active');
            }
        });
        
        // Mostrar/ocultar los contenedores de vendedores
        const vendorContainers = document.querySelectorAll('.container.bootstrap.snippets.bootdeys');
        
        vendorContainers.forEach(function(container) {
            const vendorNameElement = container.querySelector('H4');
            
            if (vendorNameElement) {
                const vendorName = vendorNameElement.textContent.trim();
                
                // Si el nombre del vendedor está vacío
                if (!vendorName || vendorName === '' || vendorName === '&nbsp;' || /^\s*$/.test(vendorName)) {
                    container.style.display = (vendorId === 'todos') ? '' : 'none';
                } else {
                    // Para pedidos con vendedor, aplicar filtro
                    const normalizedVendorName = normalizeVendorName(vendorName);
                    
                    if (vendorId === 'todos') {
                        // Solo mostrar contenedores de vendedores autorizados
                        container.style.display = authorizedVendors.includes(normalizedVendorName) ? '' : 'none';
                    } else {
                        // Mostrar solo si coincide exactamente
                        const normalizedVendorId = vendorId.replace(/_/g, '');
                        const containerVendorId = normalizedVendorName.replace(/_/g, '');
                        
                        if (normalizedVendorName === vendorId || containerVendorId === normalizedVendorId) {
                            container.style.display = '';
                        } else {
                            container.style.display = 'none';
                        }
                    }
                }
            } else {
                container.style.display = (vendorId === 'todos') ? '' : 'none';
            }
        });
        
        // Guardar la selección en localStorage
        localStorage.setItem('selectedVendorTab', vendorId);
        
        // Ocultar tarjetas sin vendedor cuando no se selecciona "todos"
        if (vendorId !== 'todos') {
            document.querySelectorAll('.no-vendor-card').forEach(card => {
                card.style.display = 'none';
            });
        }
    }

    // Función para actualizar los enlaces de acción con el parámetro de pestaña
    function updateActionLinks() {
        const currentTab = localStorage.getItem('selectedVendorTab') || 'todos';
        const actionLinks = document.querySelectorAll('a[href^="alta/"]');
        
        actionLinks.forEach(link => {
            const currentHref = link.getAttribute('href');
            // Solo modificar si no tiene ya el parámetro tab
            if (!currentHref.includes('tab=')) {
                const separator = currentHref.includes('?') ? '&' : '?';
                link.setAttribute('href', currentHref + separator + 'tab=' + currentTab);
            }
        });
    }

    // Funcionalidad del buscador de vendedores
    const searchInput = document.getElementById('vendorSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            // Filtrar las pestañas de vendedores
            const vendorTabs = document.querySelectorAll('.vendor-tab:not([data-vendor="todos"])');
            
            vendorTabs.forEach(tab => {
                const vendorName = tab.textContent.toLowerCase();
                if (vendorName.includes(searchTerm)) {
                    tab.style.display = '';
                } else {
                    tab.style.display = 'none';
                }
            });
            
            // La pestaña "TODOS" siempre debe estar visible
            const todosTab = document.querySelector('.vendor-tab[data-vendor="todos"]');
            if (todosTab) {
                todosTab.style.display = '';
            }
            
            // Si hay un término de búsqueda, filtrar los contenedores
            if (searchTerm) {
                if (todosTab) {
                    document.querySelectorAll('.vendor-tab').forEach(t => t.classList.remove('active'));
                    todosTab.classList.add('active');
                }
                
                const vendorContainers = document.querySelectorAll('.container.bootstrap.snippets.bootdeys');
                
                vendorContainers.forEach(container => {
                    const vendorNameElement = container.querySelector('H4');
                    if (vendorNameElement) {
                        const vendorName = vendorNameElement.textContent.toLowerCase().trim();
                        const normalizedVendorName = normalizeVendorName(vendorName.toUpperCase());
                        
                        if (vendorName.includes(searchTerm) && authorizedVendors.includes(normalizedVendorName)) {
                            container.style.display = '';
                        } else {
                            container.style.display = 'none';
                        }
                    }
                });
                
                localStorage.setItem('selectedVendorTab', 'todos');
                localStorage.setItem('searchTerm', searchTerm);
            } else {
                const savedTab = localStorage.getItem('selectedVendorTab') || 'todos';
                setTimeout(() => {
                    applyVendorFilter(savedTab);
                }, 100);
                localStorage.removeItem('searchTerm');
            }
        });
        
        // Verificar si hay un término de búsqueda guardado al cargar la página
        const savedSearchTerm = localStorage.getItem('searchTerm');
        if (savedSearchTerm) {
            searchInput.value = savedSearchTerm;
            const inputEvent = new Event('input', { bubbles: true });
            searchInput.dispatchEvent(inputEvent);
        }
    }
    
    // Identificar tarjetas problemáticas
    document.querySelectorAll('.content-card .title').forEach(titleElement => {
        if (titleElement.textContent.includes('Folio: 3343')) {
            titleElement.closest('.content-card').classList.add('no-vendor-card');
        }
    });

    // Asegurarse de que los ID de los vendedores en las pestañas coincidan con los contenedores
    const containers = document.querySelectorAll('.container.bootstrap.snippets.bootdeys');
    containers.forEach(function(container) {
        const vendorNameElement = container.querySelector('H4');
        if (vendorNameElement) {
            const vendorName = vendorNameElement.textContent.trim();
            const normalizedVendorName = normalizeVendorName(vendorName);
            container.setAttribute('data-vendor', normalizedVendorName);
        }
    });
    
    // Event listeners para pestañas
    const tabs = document.querySelectorAll('.vendor-tab');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const vendorId = this.getAttribute('data-vendor');
            applyVendorFilter(vendorId);
            updateActionLinks();
        });
    });
    
    // Aplicar filtro inicial
    const savedTab = localStorage.getItem('selectedVendorTab') || 'todos';
    setTimeout(() => {
        applyVendorFilter(savedTab);
        updateActionLinks();
    }, 200);
    
    // Funcionalidad para seleccionar todos los pedidos de un vendedor
    const selectAllCheckboxes = document.querySelectorAll('.select-all');
    
    selectAllCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const vendor = this.getAttribute('data-vendor');
            const form = document.getElementById('form_' + vendor);
            if (form) {
                const checkboxes = form.querySelectorAll('input[name="pedidos[]"]');
                
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            }
        });
    });
    
    // Validar que se seleccione al menos un pedido antes de exportar
    const exportForms = document.querySelectorAll('form[action="exportar_excel.php"]');
    
    exportForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const checkboxes = this.querySelectorAll('input[name="pedidos[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Por favor, selecciona al menos un pedido para exportar.');
            }
        });
    });

    // Manejo de parámetros de URL para pestañas activas
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('active_tab');
    if (activeTab && activeTab !== '') {
        setTimeout(() => {
            applyVendorFilter(activeTab);
        }, 300);
    }

    console.log('✅ Script de almacén cargado correctamente');
    console.log('Vendedores autorizados:', authorizedVendors);
});
</script>
</body>
</html>