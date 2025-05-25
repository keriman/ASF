<?php
	//pedidos/index.php
    include '../conexiones/database.php';
	
	// Agregar este array al inicio de tu código PHP, después de la conexión a la base de datos
	$availableColors = ['blue', 'green', 'yellow', 'brown', 'purple', 'orange'];

	session_start();
	if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
	    header('Location: ../login.php');
	}
	if ($_SESSION["tipo"] != "oficina") {
	  header('Location: ../index.php');
	}

	$conectar=mysql_connect($host, $user, $clave);
	mysql_select_db($datbase, $conectar);
	mysql_set_charset('utf8', $conectar);

	date_default_timezone_set ("America/Mexico_City");

	// Función para obtener un color basado en el nombre del vendedor
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


	function getDeliveryDateColor($deliveryDate) {
	    // Convert delivery date to timestamp
	    $deliveryTimestamp = strtotime($deliveryDate);
	    $today = strtotime('today');
	    
	    // Get start and end of each period
	    $currentWeekStart = strtotime('monday this week', $today);
	    $currentWeekEnd = strtotime('sunday this week', $today);
	    
	    $nextWeekStart = strtotime('monday next week', $today);
	    $nextWeekEnd = strtotime('sunday next week', $today);
	    
	    $twoWeeksStart = strtotime('monday next week', $nextWeekStart);
	    $twoWeeksEnd = strtotime('sunday next week', $nextWeekStart);
	    
	    $threeWeeksStart = strtotime('monday next week', $twoWeeksStart);
	    
	    // If date is from current week
	    if ($deliveryTimestamp >= $currentWeekStart && $deliveryTimestamp <= $currentWeekEnd) {
	        return 'red';
	    }
	    
	    // If date is from next week
	    if ($deliveryTimestamp >= $nextWeekStart && $deliveryTimestamp <= $nextWeekEnd) {
	        return 'orange';
	    }
	    
	    // If date is from two weeks ahead
	    if ($deliveryTimestamp >= $twoWeeksStart && $deliveryTimestamp <= $twoWeeksEnd) {
	        return 'yellow';
	    }
	    
	    // If date is three weeks ahead or more
	    if ($deliveryTimestamp >= $threeWeeksStart) {
	        return 'green';
	    }
	    
	    // For past dates, return the default vendor color
	    return null;
	}

	$usuario = $_SESSION["username"];
	$subs_usuario = $usuario;
	$usuario_cons = $usuario;

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
				$criterio = "WHERE ";			
				$concepto_cons = "vendedor";
				$dato_cons = "TODOS";
		}
	} elseif (isset($_GET['bb'])) {
				$dato_cons = htmlentities($_GET['bb']);
				$concepto_cons = "vendedor";
				$criterio = "WHERE vendedor LIKE '%".$dato_cons."%' AND";		
	} else {
				$criterio = "WHERE ";			
				$concepto_cons = "vendedor";
				$dato_cons = "TODOS";
	}

	if ($concepto_cons == "folio") {
	    $criterio =	$criterio;
	} else {
	    $criterio = $criterio." (status = 10 OR status = 30) ";
	    // Se elimina la restricción "AND usuario = '".$usuario_cons."'"
	}
	
	$buscar = "SELECT folio, vendedor, destino, cliente, ruta, fecha_salida, status FROM adm_pedidos ".$criterio." ORDER BY vendedor, fecha_salida, folio desc LIMIT 1000";

	
	$resultado=mysql_query($buscar)	or die("Problemas en el select: ".mysql_error());

	function getVendorsForCurrentUser($usuario) {
	    // Consulta para obtener todos los vendedores únicos de pedidos
	    $query = "SELECT DISTINCT vendedor FROM adm_pedidos WHERE vendedor != '' ORDER BY vendedor";
	    $result = mysql_query($query) or die("Error en la consulta: " . mysql_error());
	    
	    // Crear array de vendedores de pedidos
	    $vendorsFromPedidos = array();
	    while ($row = mysql_fetch_array($result)) {
	        if (!empty($row['vendedor'])) {
	            $vendorsFromPedidos[] = $row['vendedor'];
	        }
	    }
	    
	    // Si no hay vendedores en pedidos, retornar array vacío
	    if (empty($vendorsFromPedidos)) {
	        return array();
	    }
	    
	    // Crear la lista de vendedores para la consulta IN
	    $vendorList = "'" . implode("','", array_map('mysql_real_escape_string', $vendorsFromPedidos)) . "'";
	    
	    // Consulta para obtener vendedores que existen en las tablas de catálogo
	    $catalogVendorsQuery = "
	        SELECT nombre as vendedor FROM vendedores WHERE nombre IN ($vendorList)
	        UNION
	        SELECT nombre as vendedor FROM vendedores_quinagro WHERE nombre IN ($vendorList)
	        ORDER BY vendedor
	    ";
	    
	    $catalogResult = mysql_query($catalogVendorsQuery) or die("Error en la consulta de catálogo: " . mysql_error());
	    
	    // Crear array final de vendedores autorizados
	    $authorizedVendors = array();
	    while ($row = mysql_fetch_array($catalogResult)) {
	        if (!empty($row['vendedor'])) {
	            $authorizedVendors[] = $row['vendedor'];
	        }
	    }
	    
	    return $authorizedVendors;
	}
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta http-equiv="refresh" content="600">
    <meta charset="utf-8">
    <title>AgroSantaFe/oficina</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de Pedidos">
		<meta name="author" content="Eduardo Aburto Salas" />
		<link rel="shortcut icon" href="../images/ico_agrosantafe.ico"> 
    <link rel="stylesheet" href="../dist/css/site.min.css">
    <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,800,700,400italic,600italic,700italic,800italic,300italic" rel="stylesheet" type="text/css">
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

.card[data-color="blue"] .description {
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
/* Update existing color definitions and add new ones */
.card[data-color="red"] {
    background: #ffcdd2;
}
.card[data-color="red"] .description {
    color: #000;
}
.card[data-color="red"] .category {
    color: #000;
}

.card[data-color="orange"] {
    background: #ffe0b2;
}
.card[data-color="orange"] .description {
    color: #000;
}
.card[data-color="orange"] .category {
    color: #000;
}

.card[data-color="yellow"] {
    background: #fff9c4;
}
.card[data-color="yellow"] .description {
    color: #000;
}
.card[data-color="yellow"] .category {
    color: #000;
}

.card[data-color="green"] {
    background: #c8e6c9;
}
.card[data-color="green"] .description {
    color: #000;
}
.card[data-color="green"] .category {
    color: #000;
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
    color: white;
    border-color: #2e6da4;
}

/* Estilos adicionales para mejorar la visualización */
.container.bootstrap.snippets.bootdeys {
    margin-top: 15px;
}
</style>


</head>
<body>

<div class="row">
	<!--<button class="btn btn-danger" type="submit"  onclick="window.location.href='../usuarios/salir.php';">Salir</button>-->
	<div class="content-row">
        	<div class="panel panel-default">
            		<div class="panel-body">
				<form id="form5" action="" method="GET">
					<p>
						<div class="col-xs-12 col-sm-6 col-md-2 col-lg-2 col-xl-1">
					        	<select name="dd" id="dd" class="form-control">
					        		<option value="ninguno" <? if ($concepto_cons == 'ninguno'){echo('selected');} ?>></option>
								<option value="folio" <? if ($concepto_cons == 'folio'){echo('selected');} ?>>Folio</option>
								<!--<option value="vendedor" <? if ($concepto_cons == 'vendedor'){echo('selected');} ?>>Vendedor</option>-->
								<option value="destino" <? if ($concepto_cons == 'destino'){echo('selected');} ?>>Destino</option>
								<option value="salida" <? if ($concepto_cons == 'salida'){echo('selected');} ?>>Fecha de Salida</option>
							</select>
					        </div>
						<div class="col-xs-12 col-sm-6 col-md-2 col-lg-2 col-xl-1">
							<input type="text" name="vv" id="vv" class="form-control" value="<?=$dato_cons;?>" autocomplete="off">
						</div>
						<div class="col-xs-12 col-sm-6 col-md-2 col-lg-2 col-xl-1">
							<button type="submit" class="btn btn-primary btn-block">Consultar</button>
						</div>
				    	</p>
				</form>
			</div>
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
       			<button class="btn btn-primary" type="submit" name="nuevo_pedido" value ="nuevo_pedido" onclick="window.location.href='alta/index.php';">Nuevo Pedido</button>
		</div>
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
		                // Obtener vendedores para el usuario actual
		                $vendors = getVendorsForCurrentUser($usuario_cons);
		                
		                // Mostrar pestañas para cada vendedor
		                foreach ($vendors as $vendor) {
		                    // Normalizar el nombre del vendedor para el atributo data-vendor (sin espacios ni caracteres especiales)
		                    $vendorDataAttr = preg_replace('/[^a-zA-Z0-9_]/', '_', $vendor);
		                    echo '<div class="vendor-tab" data-vendor="' . $vendorDataAttr . '">' . $vendor . '</div>';
		                }
		                ?>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
</div>

<div class="row">
	<div class="content-row">

<?
	if (mysql_num_rows($resultado)>0) {
		$numreg = 0;
		while($rowc = mysql_fetch_array($resultado)){ 
			if ($anterior != $rowc['vendedor']) {
				if ($numreg > 0){
					echo("</form>");
					echo("</div>");
					echo("</div>");
				}

				echo("<div class='container bootstrap snippets bootdeys'>");
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
			}
// card
			$articulo = 0;
?>

			<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 content-card">
				<div class="checkbox-container">
					<input type="checkbox" name="pedidos[]" value="<?php echo $rowc['folio']; ?>" class="select-card">
				</div>
				<div class="card-big-shadow">
					<div class="card card-just-text" data-background="color" 
<?
		            	
						$color = null;

						// Check status-based colors first
						if ($rowc['status'] == 50) {
						    $color = 'brown';  // Entregado incompleto
						} elseif ($rowc['status'] == 40) {
						    $color = 'blue';   // Entregado completo
						} elseif ($rowc['status'] == 20) {
						    $color = 'purple'; // Cancelado
						} else {
						    // For pending (10) and en almacén (30) status, use date-based colors
						    $dateColor = getDeliveryDateColor($rowc['fecha_salida']);
						    if ($dateColor) {
						        $color = $dateColor;
						    } else {
						        // Use default vendor color if no specific date color applies
						        $color = getVendorBaseColor($rowc['vendedor']);
						    }
						}

						echo 'data-color="' . $color . '"';

?>
						data-radius="none">
						<div class="content">
<?
							// Reemplazar la línea que muestra la fecha con:
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

							$detalle = "SELECT id, folio, producto, presentacion, cantidad FROM prc_pedidos WHERE procesado = 0 AND folio = ".$rowc['folio']." LIMIT 20";
							$res_detalle=mysql_query($detalle)	or die("Problemas en el select: ".mysql_error());
							if (mysql_num_rows($res_detalle)>0) {
								echo("<p class='description'><b>Productos:</b></p>");
								while($rowd = mysql_fetch_array($res_detalle)){ 
									$articulo += 1;
									echo("<p class='description'>".$articulo."./ ".$rowd['producto']." / ".$rowd['presentacion']." / Cant: ".$rowd['cantidad']."</p>");
								}
							}

							echo("<h6 class='category'>Status: ");
					       	if ($rowc['status'] == 50){
						   		echo("Entregado Incompleto");
					       	} elseif ($rowc['status'] == 40){
						   		echo("Entregado Completo");
					       	} elseif ($rowc['status'] == 30){
						   		echo("En Almacen");
					       	} elseif ($rowc['status'] == 20){
						   		echo("Cancelado");
					       	} else {
					       		echo("Pendiente");
					    	}

					        echo("</h6>");
			                if ($rowc['status'] == 10){
		                		echo("<br/><p class='description'>Acciones: ");
			                	echo("  <a href='alta/index.php?gg=".$rowc['folio']."'><img src='../images/editar.jpg' width='20' height='20' title='Editar' /></a>");
			                	echo(" _ _ _ ");
			                	echo("  <a href='alta/cancelar.php?gg=".$rowc['folio']."'><img src='../images/cancelar.png' width='20' height='20' title='Cancelar' /></a>");
			                	echo(" _ _ _ ");
			                	echo("  <a href='alta/cambiar_almacen.php?gg=".$rowc['folio']."'><img src='../images/aprobado.png' width='20' height='20' title='Enviar al Almacen' /></a>");
							} elseif ($rowc['status'] == 30){ 
							    echo("<br/><p class='description'>Acciones: ");
							    echo("  <a href='alta/regresar.php?gg=".$rowc['folio']."'><img src='../images/regresar.png' width='20' height='20' title='Regresar a captura' /></a>");
							    echo(" _ _ _ ");
							    echo("  <a href='alta/index.php?gg=".$rowc['folio']."'><img src='../images/editar.jpg' width='20' height='20' title='Editar' /></a>");
							    echo(" _ _ _ ");
							    echo("  <a href='alta/cancelar.php?gg=".$rowc['folio']."'><img src='../images/cancelar.png' width='20' height='20' title='Cancelar' /></a>");
							}

							$obs = "SELECT observaciones, modificada FROM obs_pedidos WHERE folio = ".$rowc['folio']." ORDER BY FR LIMIT 20";
							$res_obs=mysql_query($obs)	or die("Problemas en el select: ".mysql_error());
							$num_obs = 0;
							if (mysql_num_rows($res_obs)>0) {
							    echo("<p class='description'><b>Observaciones:</b></p>");
							    while($rowo = mysql_fetch_array($res_obs)){ 
							        $num_obs += 1;
							        if ($rowo['modificada'] == 1) {
							            echo("<p class='description'>".$num_obs.".- ".$rowo['observaciones']." <span style='color: red; font-weight: bold;'>M</span></p>");
							        } else {
							            echo("<p class='description'>".$num_obs.".- ".$rowo['observaciones']."</p>");
							        }
							    }
							}
?>
								</div>
						</div> 
				</div>
		</div>

<?
			$numreg += 1;
			$anterior = $rowc['vendedor'];
		}
		
		// Cerrar el último formulario si hubo al menos un pedido
		if ($numreg > 0) {
			echo("</form>");
		}
	}
?>

		</div>
</div>

<script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
<script src="https://netdna.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
     const searchInput = document.getElementById('vendorSearch');
    
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        // Filtrar las pestañas de vendedores
        const vendorTabs = document.querySelectorAll('.vendor-tab:not([data-vendor="todos"])');
        let visibleTabs = 0;
        
        vendorTabs.forEach(tab => {
            const vendorName = tab.textContent.toLowerCase();
            if (vendorName.includes(searchTerm)) {
                tab.style.display = '';
                visibleTabs++;
            } else {
                tab.style.display = 'none';
            }
        });
        
        // La pestaña "TODOS" siempre debe estar visible
        const todosTab = document.querySelector('.vendor-tab[data-vendor="todos"]');
        if (todosTab) {
            todosTab.style.display = '';
        }
        
        // Si hay un término de búsqueda, filtrar los contenedores de vendedores
        if (searchTerm) {
            // Activar la pestaña "TODOS" si hay un término de búsqueda
            if (todosTab) {
                document.querySelectorAll('.vendor-tab').forEach(t => t.classList.remove('active'));
                todosTab.classList.add('active');
            }
            
            // Filtrar los contenedores de vendedores
            const vendorContainers = document.querySelectorAll('.container.bootstrap.snippets.bootdeys');
            
            vendorContainers.forEach(container => {
                const vendorNameElement = container.querySelector('H4');
                if (vendorNameElement) {
                    const vendorName = vendorNameElement.textContent.toLowerCase();
                    if (vendorName.includes(searchTerm)) {
                        container.style.display = '';
                    } else {
                        container.style.display = 'none';
                    }
                }
            });
            
            // Actualizar el localStorage para reflejar que estamos en modo búsqueda
            localStorage.setItem('selectedVendorTab', 'todos');
            localStorage.setItem('searchTerm', searchTerm);
        } else {
            // Si no hay término de búsqueda, restablecer a la vista normal
            const savedTab = localStorage.getItem('selectedVendorTab') || 'todos';
            document.querySelectorAll('.vendor-tab').forEach(t => {
                if (t.getAttribute('data-vendor') === savedTab) {
                    t.click(); // Simular clic en la pestaña guardada
                }
            });
            localStorage.removeItem('searchTerm');
        }
    });
    
    // Al cargar la página, comprobar si hay un término de búsqueda guardado
    const savedSearchTerm = localStorage.getItem('searchTerm');
    if (savedSearchTerm) {
        searchInput.value = savedSearchTerm;
        // Disparar el evento input para aplicar el filtro
        const inputEvent = new Event('input', { bubbles: true });
        searchInput.dispatchEvent(inputEvent);
    }
    
    // Añadir funcionalidad para seleccionar todos los pedidos de un vendedor
    const selectAllCheckboxes = document.querySelectorAll('.select-all');
    
    selectAllCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const vendor = this.getAttribute('data-vendor');
            const form = document.getElementById('form_' + vendor);
            const checkboxes = form.querySelectorAll('input[name="pedidos[]"]');
            
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
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
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para decodificar entidades HTML en JavaScript
    function decodeHtmlEntities(text) {
        if (!text) return '';
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        return textArea.value;
    }

    // Función para normalizar el nombre del vendedor (eliminar espacios, acentos, etc.)
    function normalizeVendorName(name) {
        // Primero decodificar entidades HTML
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
                
                // Normalizar el nombre del vendedor para comparación
                const normalizedVendorName = normalizeVendorName(vendorName);
                
                // Normalizar el ID de vendedor seleccionado (para comparar tanto con como sin guiones bajos)
                const normalizedVendorId = vendorId.replace(/_/g, '');
                const containerVendorId = normalizedVendorName.replace(/_/g, '');
                
                console.log({
                    vendorName: vendorName,
                    normalized: normalizedVendorName,
                    vendorId: vendorId,
                    normalizedId: normalizedVendorId,
                    containerNormalized: containerVendorId,
                    matches: normalizedVendorName === vendorId || containerVendorId === normalizedVendorId
                });
                
                // Verificar coincidencia de varias maneras
                if (vendorId === 'todos' || 
                    normalizedVendorName === vendorId || 
                    containerVendorId === normalizedVendorId) {
                    container.style.display = '';
                } else {
                    container.style.display = 'none';
                }
            }
        });
        
        // Guardar la selección en localStorage
        localStorage.setItem('selectedVendorTab', vendorId);
    }
    
    // Añadir evento de clic a todas las pestañas
    const tabs = document.querySelectorAll('.vendor-tab');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const vendorId = this.getAttribute('data-vendor');
            applyVendorFilter(vendorId);
        });
    });
    
    // Verificar si hay una pestaña seleccionada previamente
    const savedTab = localStorage.getItem('selectedVendorTab');
    if (savedTab) {
        // Aplicar el filtro guardado
        applyVendorFilter(savedTab);
    } else {
        // Si no hay selección previa, mostrar todos
        applyVendorFilter('todos');
    }
    
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
    
    // Modificar enlaces de acciones para incluir el parámetro de la pestaña seleccionada
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
    
    // Actualizar los enlaces al cargar la página
    updateActionLinks();
    
    // Actualizar los enlaces cuando cambie la pestaña
    tabs.forEach(tab => {
        tab.addEventListener('click', updateActionLinks);
    });
});
</script>
</body>
</html>