<?php
	date_default_timezone_set ("America/Mexico_City");
    include '../conexiones/database.php';
	
	session_start();
	if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
	    header('Location: ../login.php');
	}
	if ($_SESSION["tipo"] != "gerencia") {
	  header('Location: ../index.php');
	}

	$conectar=mysql_connect($host, $user, $clave);
	mysql_select_db($datbase, $conectar);
	mysql_set_charset('utf8', $conectar);

	function getVendorsForCurrentUser($usuario) {
	    // Consulta que obtiene SOLO vendedores que existen EXACTAMENTE en las tablas de catálogo
	    $query = "
	        SELECT DISTINCT ap.vendedor
	        FROM adm_pedidos ap
	        WHERE ap.vendedor != '' 
	          AND ap.status IN (10, 20, 30, 40, 50)  -- Todos los estados para gerencia
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
	          AND ap.status IN (10, 20, 30, 40, 50)
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

	// Función para decodificar entidades HTML
	function html_entity_decode_deep($value) {
		$value = is_array($value) ?
					array_map('html_entity_decode_deep', $value) :
					html_entity_decode($value, ENT_QUOTES | ENT_HTML401, 'UTF-8');
		return $value;
	}

	$authorizedVendors = getVendorsForCurrentUser($usuario);

	$subs_usuario = $usuario;
	//$agencia_cons = $id_agencia;
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
						$criterio =	"WHERE ";
				} else {
						$criterio =	"WHERE vendedor LIKE '%".strtoupper($dato_cons)."%' AND";
				}
		} elseif ($concepto_cons == "destino") {
				$criterio =	"WHERE destino = '".strtoupper($dato_cons)."' AND";
		} elseif ($concepto_cons == "salida") {
				$criterio =	"WHERE fecha_salida = '".$dato_cons."' AND";
		} else {
				$dato_cons = "TODOS";
				$concepto_cons = "vendedor";
				$criterio =	"WHERE ";
		}
	} elseif (isset($_GET['bb'])) {
				$dato_cons = htmlentities($_GET['bb']);
				$concepto_cons = "vendedor";
				$criterio =	"WHERE vendedor LIKE '%".$dato_cons."%' AND";		
	} else {
				$dato_cons = "TODOS";
				$concepto_cons = "vendedor";
				$criterio =	"WHERE ";
	}
	
	if ($concepto_cons == "folio") {
			$criterio =	$criterio;
	} else {
		if (isset($_GET['st'])) {
  			$dato_st = "= ".htmlentities($_GET['st']);
  			if ($dato_st == "= 0") {
  				$dato_st = "IN (10, 20, 30) or (status IN (40,50) and date_add(fecha_salida, interval -5 day))";
  			}
  		} else {
  			$dato_st = "IN (10, 20, 30) or (status IN (40,50) and date_add(fecha_salida, interval -5 day))";
  		}
		$criterio =	$criterio." status ".$dato_st;
	}

	$buscar = "SELECT folio, vendedor, destino, fecha_salida, status FROM adm_pedidos ".$criterio." ORDER BY vendedor, status, folio desc LIMIT 1000";
	
	$resultado=mysql_query($buscar)	or die("Problemas en el select: ".mysql_error());
	
	// Array que mapea vendedores con codificación especial a su formato normal
	$vendedoresEspeciales = array(
	    'ANGEL BOLA&Ntilde;OS' => 'ANGEL BOLAÑOS',
	    'DANIEL ORDOÑEZ' => 'DANIEL ORDOÑEZ'
	);
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta http-equiv="refresh" content="600">
    <meta charset="utf-8">
    <title>AgroSantaFe/gerencia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de Pedidos">
		<meta name="author" content="Eduardo Aburto Salas" />
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
body, p, h1, h2, h3, h4, h5, h6, div, span, a, .card .description, .card .category, .card-just-text .content {
    color: #000000 !important;
}

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
    color: #7a9e9f;
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
.card[data-color="blue"] .category {
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
/* Estilos para las pestañas de vendedores */
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

/* Estilos específicos para pedidos sin vendedor */
.empty-vendor-container {
    border-top: 2px dashed #ddd;
    padding-top: 10px;
}

/* Estilos para los checkboxes */
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
.btn-primary, .btn-success, .btn-danger, .btn-info, .btn-warning {
    color: white !important;
}
    </style>


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
					       				<h6>Status:</h6> 
					              <select name="st" id="st" class="form-control">
					                <option value="0" <? if ($dato_st == "IN (10, 20, 30, 40, 50)"){echo('selected');} ?>></option>
					                <option value="10" <? if ($dato_st == "= 10"){echo('selected');} ?>>PENDIENTE</option>
					                <option value="20" <? if ($dato_st == "= 20"){echo('selected');} ?>>CANCELADO</option>
					                <option value="30" <? if ($dato_st == "= 30"){echo('selected');} ?>>EN ALMACEN</option>
					                <option value="40" <? if ($dato_st == "= 40"){echo('selected');} ?>>ENTREGADO COMPLETO</option>
					                <option value="50" <? if ($dato_st == "= 50"){echo('selected');} ?>>ENTREGADO INCOMPLETO</option>
					              </select>
					        </div>
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
						       	<button type="submit" class="btn btn-primary btn-block">Consultar</button>
						    </div>
				    	</p>
			 	    </form>
	  			</div>
			</div>
		</div>
	</div>
	
	<!-- Botón Exportar Todos -->
    <div class="row">
        <div class="content-row">
            <div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 col-xl-1">
                <a href="exportar_excel.php?vendedor=TODOS" class="btn btn-success" role="button">Exportar Todos</a>
            </div>
        </div>
    </div>
	
	<!-- Pestañas de vendedores -->
	<div class="row">
	    <div class="content-row">
	        <div class="panel panel-default">
	            <div class="panel-body">
	                <!-- Pestañas en múltiples filas -->
	                <div class="vendor-tabs-container">
					    <div class="vendor-tab active" data-vendor="todos">TODOS</div>
					    <?php 
					    // Generar pestañas SOLO para vendedores que están exactamente en catálogo
					    foreach ($authorizedVendors as $vendor) {
					        // Normalizar el nombre del vendedor para el atributo data-vendor (para JavaScript)
					        $vendorDataAttr = preg_replace('/[^a-zA-Z0-9_]/', '_', $vendor);
					        
					        echo '<div class="vendor-tab authorized-vendor" data-vendor="' . $vendorDataAttr . '">' . htmlspecialchars($vendor) . '</div>';
					    }
					    ?>
					</div>
	            </div>
	        </div>
	    </div>
	</div>

<?
if (mysql_num_rows($resultado)>0) {
		$numreg = 0;
		while($rowc = mysql_fetch_array($resultado)){ 
		    
		    // Normalizar el nombre del vendedor si tiene entidades HTML
		    $vendedorOriginal = $rowc['vendedor'];
		    $vendedorNormalizado = html_entity_decode($vendedorOriginal, ENT_QUOTES | ENT_HTML401, 'UTF-8');
		    
		    // Si existe en el mapeo, usar el nombre normalizado
		    if(array_key_exists($vendedorOriginal, $vendedoresEspeciales)) {
		        $vendedorNormalizado = $vendedoresEspeciales[$vendedorOriginal];
		    }
		    
			if ($anterior != $vendedorNormalizado) {
				// Si no es el primer vendedor, cerramos la estructura anterior
				if ($numreg > 0){
					echo("</div>"); // Cerrar div row
					echo("</form>"); // Cerrar form
					echo("</div>"); // Cerrar container
				}

				// Abrir nueva estructura para el vendedor actual
				$vendorClass = (empty(trim($vendedorNormalizado))) ? 'empty-vendor-container' : '';
				
				echo("<div class='container bootstrap snippets bootdeys $vendorClass'>");
				echo("<form action='exportar_excel.php' method='post' id='form_".preg_replace('/[^a-zA-Z0-9_]/', '_', $vendedorNormalizado)."'>");
				echo("<input type='hidden' name='vendedor' value='".htmlspecialchars($vendedorOriginal)."'>");
				echo("<div class='row'>");
				echo("<div style='display: flex; justify-content: space-between; align-items: center; width: 100%;'>");
				echo("<div>");
				echo("<H4 data-original-vendedor='".htmlspecialchars($vendedorOriginal)."'>".$vendedorNormalizado."</H4>");
				echo("<label style='margin-left: 20px;'>");
				echo("<input type='checkbox' class='select-all' data-vendor='".htmlspecialchars(preg_replace('/[^a-zA-Z0-9_]/', '_', $vendedorNormalizado))."'> Seleccionar Todos");
				echo("</label>");
				echo("</div>");
				echo("<button type='submit' class='btn btn-success btn-sm'>");
				echo("<i class='glyphicon glyphicon-export'></i> Exportar Seleccionados</button>");
				echo("</div>");
				echo("</div>"); // Cerrar el div de la cabecera
				
				// Nuevo contenedor para las tarjetas
				echo("<div class='row'>");
			}
// card
?>

		<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-xs-12 content-card">
				<!-- Checkbox para seleccionar la tarjeta -->
				<div class="checkbox-container">
					<input type="checkbox" name="pedidos[]" value="<?=$rowc['folio'];?>" class="select-card">
				</div>
				<div class="card-big-shadow">
						<div class="card card-just-text" data-background="color" 
<?
				            	if ($rowc['status'] == 50){
					            		echo("data-color='brown'");
				            	} elseif ($rowc['status'] == 40){
					            		echo("data-color='blue'");
				            	} elseif ($rowc['status'] == 30){
				            		if ($rowc['fecha_salida'] == date("Y-m-d")){
					            		echo("data-color='orange'");
									} else {				            			
					            		echo("data-color='green'");
				            		}
				            	} elseif ($rowc['status'] == 20){
					            		echo("data-color='purple'");
				            	} else {
					            		echo("data-color='yellow'");
					        	}
?>
						data-radius="none">
								<div class="content">
<?
							echo("<h6 class='category'>Fecha de Salida: ".$rowc['fecha_salida']."</h6>");
							echo("<h4 class='title'>Folio: ".$rowc['folio']."</h4>");
							echo("<h6 class='category'>Destino: ".$rowc['destino']."</h6>");

							$detalle = "SELECT id, folio, producto, presentacion, cantidad FROM prc_pedidos WHERE procesado = 0 AND folio = ".$rowc['folio']." LIMIT 20";
							$res_detalle=mysql_query($detalle)	or die("Problemas en el select: ".mysql_error());
							$articulo = 0;
							if (mysql_num_rows($res_detalle)>0) {
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

							$obs = "SELECT observaciones FROM obs_pedidos WHERE folio = ".$rowc['folio']." LIMIT 20";
							$res_obs=mysql_query($obs)	or die("Problemas en el select: ".mysql_error());
							$num_obs = 0;
							if (mysql_num_rows($res_obs)>0) {
								echo("<p class='description'><b>Observaciones:</b></p>");
								while($rowo = mysql_fetch_array($res_obs)){ 
									$num_obs += 1;
									echo("<p class='description'>".$num_obs.".- ".$rowo['observaciones']."</p>");
								}
							}
?>
								</div>
						</div> 
				</div>
		</div>

<?
			$numreg += 1;
			$anterior = $vendedorNormalizado;
		}
		
		// Cerrar el último conjunto de elementos
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

    // Mapa de nombres especiales con entidades HTML a nombres normalizados
    const specialVendorNames = {
        'ANGEL BOLA&NTILDE;OS': 'ANGEL_BOLANOS',
        'DANIEL ORDO&NTILDE;E': 'DANIEL_ORDONE'
    };
    
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
        const vendorContainers = document.querySelectorAll('.container.bootstrap.snippets.bootdeys:not([data-empty-vendor="true"])');
        
        vendorContainers.forEach(function(container) {
            const vendorNameElement = container.querySelector('H4');
            
            if (vendorNameElement) {
                const vendorName = vendorNameElement.textContent.trim();
                const originalVendor = vendorNameElement.getAttribute('data-original-vendedor') || vendorName;
                
                // Si el nombre del vendedor está vacío o solo contiene espacios
                if (!vendorName || vendorName === '' || vendorName === '&nbsp;' || /^\s*$/.test(vendorName)) {
                    // Solo mostrar cuando la pestaña es "todos"
                    container.style.display = (vendorId === 'todos') ? '' : 'none';
                } else {
                    // Normalizar el nombre del vendedor para comparación
                    const normalizedVendorName = normalizeVendorName(vendorName);
                    
                    // Normalizar el ID de vendedor seleccionado (sin incluir el guión bajo, para comparar solo el nombre)
                    const normalizedVendorId = vendorId.replace(/_/g, '');
                    
                    // Log para depuración
                    console.log("Comparando vendedor:", {
                        containerVendor: normalizedVendorName,
                        selectedVendor: vendorId,
                        normalizedSelectedVendor: normalizedVendorId,
                        match: normalizedVendorName === vendorId || normalizedVendorName.replace(/_/g, '') === normalizedVendorId
                    });
                    
                    // Verificar coincidencia de varias maneras
                    if (vendorId === 'todos' || 
                        normalizedVendorName === vendorId || 
                        normalizedVendorName.replace(/_/g, '') === normalizedVendorId ||
                        (specialVendorNames[originalVendor] && (specialVendorNames[originalVendor] === vendorId))) {
                        container.style.display = '';
                    } else {
                        container.style.display = 'none';
                    }
                }
            } else {
                // Si no hay elemento de vendedor, solo mostrar en "todos"
                container.style.display = (vendorId === 'todos') ? '' : 'none';
            }
        });
        
        // Manejar contenedor de pedidos sin vendedor
        const emptyVendorContainer = document.querySelector('.container.bootstrap.snippets.bootdeys[data-empty-vendor="true"]');
        if (emptyVendorContainer) {
            emptyVendorContainer.style.display = (vendorId === 'todos' || vendorId === 'sin_vendedor') ? '' : 'none';
        }
        
        // Guardar la selección en localStorage
        localStorage.setItem('selectedVendorTabGerencia', vendorId);
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
    const savedTab = localStorage.getItem('selectedVendorTabGerencia');
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
    
    // Lista reducida de folios problemáticos que no están relacionados con la codificación
    const problematicFolios = ['336500', '3810', '3673', '366', '3', '0', '373200', '2865', '2390', '1512'];
    
    // Crear un contenedor separado para TODOS los pedidos sin vendedor
    //const emptyVendorContainer = document.createElement('div');
    //emptyVendorContainer.className = 'container bootstrap snippets bootdeys empty-vendor-container';
    //emptyVendorContainer.setAttribute('data-empty-vendor', 'true');
    //emptyVendorContainer.innerHTML = '<div class="row"><div style="display: flex; justify-content: space-between; align-items: center; width: 100%;"><div><H4>[Sin Vendedor]</H4><label style="margin-left: 20px;"><input type="checkbox" class="select-all" data-vendor="sin_vendedor"> Seleccionar Todos</label></div><button type="submit" class="btn btn-success btn-sm"><i class="glyphicon glyphicon-export"></i> Exportar Seleccionados</button></div></div>';
    
    // Crear un formulario para el envío
    const emptyVendorForm = document.createElement('form');
    emptyVendorForm.action = 'exportar_excel.php';
    emptyVendorForm.method = 'post';
    emptyVendorForm.id = 'form_sin_vendedor';
    
    // Insertar el formulario dentro del contenedor
    //emptyVendorContainer.appendChild(emptyVendorForm);
    
    // Una fila para las tarjetas dentro del contenedor
    const emptyCardRow = document.createElement('div');
    emptyCardRow.className = 'row';
    emptyVendorForm.appendChild(emptyCardRow);
    
    // Buscar tarjetas sin vendedor y moverlas al nuevo contenedor
    document.querySelectorAll('.card-just-text').forEach(card => {
        // Criterio 1: Folio problemático
        const titleElement = card.querySelector('.title');
        let isEmptyVendorCard = false;
        
        if (titleElement) {
            const folioText = titleElement.textContent;
            problematicFolios.forEach(folio => {
                if (folioText.includes(`Folio: ${folio}`)) {
                    isEmptyVendorCard = true;
                }
            });
        }
        
        // Criterio 2: Destino vacío
        if (!isEmptyVendorCard) {
            const destinoElements = card.querySelectorAll('.category');
            destinoElements.forEach(el => {
                if (el.textContent.trim() === 'Destino:') {
                    isEmptyVendorCard = true;
                }
            });
        }
        
        // Si es una tarjeta sin vendedor, moverla al nuevo contenedor
        if (isEmptyVendorCard) {
            // Obtener el elemento padre correcto (content-card)
            const cardContainer = card.closest('.content-card');
            if (cardContainer) {
                // Clonar la tarjeta para evitar problemas de referencia
                const cardClone = cardContainer.cloneNode(true);
                // Añadirla al nuevo contenedor
                emptyCardRow.appendChild(cardClone);
                // Ocultar o eliminar la original
                cardContainer.style.display = 'none';
            }
        }
    });
    
    // Añadir el contenedor especial al final del documento
    document.body.appendChild(emptyVendorContainer);
    
    // Añadir manejo de eventos para el checkbox de "seleccionar todos" en la sección de pedidos sin vendedor
    const noVendorSelectAll = emptyVendorContainer.querySelector('.select-all');
    if (noVendorSelectAll) {
        noVendorSelectAll.addEventListener('change', function() {
            const checkboxes = emptyVendorContainer.querySelectorAll('input[name="pedidos[]"]');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }
    
    // Añadir validación al formulario de pedidos sin vendedor
    const noVendorForm = document.getElementById('form_sin_vendedor');
    if (noVendorForm) {
        noVendorForm.addEventListener('submit', function(e) {
            const checkboxes = this.querySelectorAll('input[name="pedidos[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Por favor, selecciona al menos un pedido para exportar.');
            }
        });
    }
});
</script>

</body>
</html>