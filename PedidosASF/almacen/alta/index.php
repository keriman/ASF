<?php
date_default_timezone_set('America/Mexico_City');
include '../../conexiones/database.php';
session_start();
if ( !isset($_SESSION['username']) && !isset($_SESSION['userid']) ){
	header('Location: ../../login.php');
}
if ($_SESSION["tipo"] != "almacen" && $_SESSION["tipo"] != "oficina") {
	header('Location: ../../index.php');
}

$conectar=mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);
mysql_set_charset('utf8', $conectar);

$usuario = $_SESSION["username"];
$subs_usuario = $usuario;
// Inicializar variables
$folio = "";
$vendedor = "";
$destino = "";
$cliente = "";
$ruta = "";
$salida = date("Y/m/d");
$status = 10;
$subs_folio = 0;  // Inicializar con 0

// Verificar si existe el parámetro GET
if (isset($_GET['gg']) && $_GET['gg'] !== '') {
    $subs_folio = htmlentities($_GET['gg']);

    // Verificamos el folio
    $buscar = "SELECT folio, vendedor, destino, cliente, ruta, fecha_salida, status FROM adm_pedidos WHERE folio = " . intval($subs_folio) . " LIMIT 1";
    
    $resultado = mysql_query($buscar);
    if ($resultado && mysql_num_rows($resultado) > 0) {
        if ($row = mysql_fetch_array($resultado)) { 
            $folio = $row['folio'];
            $vendedor = $row['vendedor'];
            $destino = $row['destino'];
            $cliente = $row['cliente'];
            $ruta = $row['ruta'];
            $salida = $row['fecha_salida'];
            $status = $row['status'];
        }
    } else {
        // Si no existe el folio en la base de datos, usamos el valor del GET
        $folio = $subs_folio;
    }
} else {
    // Si no hay parámetro GET, obtener el siguiente folio disponible
    $buscar = "SELECT MAX(folio) as ultimo_folio FROM adm_pedidos";
    $resultado = mysql_query($buscar);
    if ($resultado && $row = mysql_fetch_array($resultado)) {
        $folio = intval($row['ultimo_folio']) + 1;
    } else {
        $folio = 1; // Si no hay registros, empezar con 1
    }
}

// Determinar la página a la que debe regresar según el tipo de usuario
$return_page = ($_SESSION["tipo"] == "almacen") ? "../index.php" : "../index.php";

// Verificamos el detalle
$buscar = "SELECT id, folio, producto, presentacion, cantidad FROM prc_pedidos WHERE procesado = 0 AND folio = ".$subs_folio;
//echo("<br/>".$buscar);

$res_prod=mysql_query($buscar)	or die("Problemas en el select: ".mysql_error());
?>
<!doctype html>
	<html>
	<head>
		<meta charset="utf-8">
		<title>AgroSantaFe/pedidos</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Registro de Pedidos">
		<meta name="author" content="Eduardo Aburto Salas" />
		<link rel="shortcut icon" href="../../images/ico_agrosantafe.ico"> 
		<link rel="stylesheet" href="../../dist/css/site.min.css">
		<link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,800,700,400italic,600italic,700italic,800italic,300italic" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="../../dist/js/site.min.js"></script>
		<style>
			input[list]::-webkit-calendar-picker-indicator {
				display: none;
			}

			.form-control[list] {
				background-color: #fff;
			}

			.form-control[list]:focus {
				border-color: #80bdff;
				outline: 0;
				box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
			}
		</style>
	</head>
	<body>
		<nav>
			<div align="right">
				<a href="<?php echo $return_page; ?>">
					<img src="../../images/atras.png" width="60" height="60" title="regresar" />
				</a>
			</div>
		</nav>

		<div class="content-row">
			<div class="panel panel-default">
				<div class="panel-body">

					<form id="form_pedidos" action="alta_pedidos.php" method="POST"> 
						<div class="row">
							<div class="col-xs-12 col-sm-12 col-md-8 col-lg-4">
								<h2 class="content-row-title">Registro de Pedidos</h2>
							</div>
						</div>

						<div class="row">
							<div class="input-group mb-3">
								<div class="col-xs-12 col-sm-4 col-md-4 col-lg-2">
									<span class="input-group-text" id="ff">Folio</span>
									<input type="text" name="ff" id="ff" class="form-control" value="<?=$folio;?>" autocomplete="off" 
									<?php if($folio!=""){echo " readonly";} ?>>
								</div>
								<div class="col-xs-12 col-sm-8 col-md-6 col-lg-4">
									<span class="input-group-text" id="vv">Vendedor</span>
									<input type="text" name="vv" id="vv" class="form-control" value="<?=$vendedor;?>" 
									<?php if($status>20){echo " readonly";} ?>>
								</div>
								<div class="col-xs-12 col-sm-8 col-md-6 col-lg-4">
									<span class="input-group-text" id="rr">Destino</span>
									<input type="text" name="rr" id="rr" class="form-control" value="<?=$destino;?>" 
									<?php if($status>20){echo " readonly";} ?>>
								</div>
								<div class="col-xs-12 col-sm-4 col-md-4 col-lg-2">
									<span class="input-group-text" id="fs">Fecha de Salida</span>
									<input type="text" name="fs" id="fs" class="form-control" value="<?=$salida;?>" 
									<?php if($status>20){echo " readonly";} ?>>
								</div>
							</div>
						</div>

						<!-- Nueva fila para los campos cliente y ruta -->
						<div class="row">
							<div class="input-group mb-3">
								<div class="col-xs-12 col-sm-8 col-md-6 col-lg-5">
									<span class="input-group-text" id="cl">Cliente</span>
									<input type="text" name="cl" id="cl" class="form-control" value="<?=$cliente;?>" 
									<?php if($status>20){echo " readonly";} ?>>
								</div>
								<div class="col-xs-12 col-sm-8 col-md-6 col-lg-5">
									<span class="input-group-text" id="rt">Ruta</span>
									<input type="text" name="rt" id="rt" class="form-control" value="<?=$ruta;?>" 
									<?php if($status>20){echo " readonly";} ?>>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="form-group">
								<label for="cc">Observaciones:</label><br/>
								<?	
								if ($subs_folio > 0) { 
									$num_obs = 0;
									$obs = "SELECT observaciones, FR, modificada FROM obs_pedidos WHERE folio = ".$folio." ORDER BY FR LIMIT 25";
									$res_obs=mysql_query($obs)	or die("Problemas en el select: ".mysql_error());
									if (mysql_num_rows($res_obs)>0) {
										$text_obs = "";
										while($rowo = mysql_fetch_array($res_obs)){ 
											$num_obs += 1;
											$text_obs = date('Y-m-d', strtotime($rowo['FR']))." - ".$rowo['observaciones'];
											
											// Agregar la marca "M" en rojo si la observación fue modificada
											if ($rowo['modificada'] == 1) {
												echo($text_obs." <span style='color: red; font-weight: bold;'>M</span><br/>");
											} else {
												echo($text_obs."<br/>");
											}
										}
									}
								}
								?>
								<textarea class="form-control" id="cc" name="cc" rows="3" value=""></textarea>
							</div>
						</div>

						<div class="row">
							<div class='col-xs-12 col-sm-8 col-md-6 col-lg-4'>
								<button class='btn btn-primary' type='submit' name='guardar_pedido' value ='Guardar'>Guardar</button>
							</div>
						</div>

					</form>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="content-row">
				<div class="panel panel-default">
					<div class="panel-body">
						<div class="col-md-12">
							<table class="table table-striped">
								<tr>
									<th>Producto</th>
									<th>Presentacion</th>
									<th>Cantidad</th>
									<th>. </th>
								</tr>
								<?
								if ($subs_folio > 0) { 
									if (mysql_num_rows($res_prod)>0) {
										$numreg = 0;
										while($rowc = mysql_fetch_array($res_prod)){ 
											echo("<tr>");
											echo("  <td>".$rowc['producto']."</td>");
											echo("  <td>".$rowc['presentacion']."</td>");
											echo("  <td>".$rowc['cantidad']."</td>");
											echo("  <td align='center'>");
											if ($status < 40){
												echo("  <a href='quitar.php?dd=".$rowc['id']."&ff=".$rowc['folio']."'>");
												echo(" 		<img src='../../images/borrar.png' width='20' height='20' title='borrar' />");
												echo(" </a>");
											}
											echo("  </td>");
											echo("</tr>"); 
											$numreg += 1;
										}
										echo("<tr>");
										echo("  <td><b>".number_format($numreg, 0)." productos</b></td>");
										echo("  <td> </td>");
										echo("  <td> </td>");
										echo("  <td> </td>");
										echo("</tr>");
									}
								}
								?>

								<form id="form_detalle" action="alta_productos.php" method="POST"> 
									<tr>
										<td>
											<!-- Reemplaza la sección del datalist con este código -->
											<input type="hidden" name="ff" id="ff" class="form-control" value="<?=$subs_folio;?>">
											<input type="text" name="pp" id="pp" class="form-control" list="productos" autocomplete="off" placeholder="Buscar producto...">
											<datalist id="productos">
												<?php
										  // Consulta para obtener los productos usando mysql_*
												$query_products = "SELECT name FROM products ORDER BY name";
												$result_products = mysql_query($query_products);
												
												if ($result_products) {
													while ($row = mysql_fetch_assoc($result_products)) {
														echo "<option value='" . htmlspecialchars($row['name']) . "'>";
													}
												}
												?>
											</datalist>
										</td>
										<td>
											<input type="text" name="pr" id="pr" class="form-control">
										</td>
										<td>
											<input type="text" name="cc" id="cc" class="form-control" value="1">
										</td>
										<td> </td>
									</tr>
									<?
									if ($subs_folio > 0 && $status < 40){
										echo("<tr>");
										echo("	<td>");
										echo("		<button class='btn btn-primary' type='submit' name='Agregar_productos' value ='Agregar'>Agregar</button>");
										echo("	</td>");
										echo("	<td> </td>");
										echo("	<td> </td>");
										echo("	<td> </td>");
										echo("</tr>");
									}
									?>
								</form>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const productInput = document.getElementById('pp');
				const presentacionInput = document.getElementById('pr');
				
				productInput.addEventListener('change', function() {
					if (!this.value) return;
					
        // Mostrar un indicador de carga en el campo de presentación
        presentacionInput.value = 'Cargando...';
        
        fetch('get_presentacion.php?product=' + encodeURIComponent(this.value))
        .then(response => {
        	if (!response.ok) {
        		throw new Error('Error en la respuesta del servidor');
        	}
        	return response.json();
        })
        .then(data => {
        	if (data.success) {
        		presentacionInput.value = data.description || '';
        	} else {
        		presentacionInput.value = '';
        		console.log('Error:', data.message || data.error);
        	}
        })
        .catch(error => {
        	console.error('Error:', error);
        	presentacionInput.value = '';
        });
      });
			});
		</script>
	</body>
	</html>