<?php
session_start();
if (!isset($_SESSION['username']) && !isset($_SESSION['userid'])) {
    header('Location: ../../login2.php');
    exit;
}

include '../../conexiones/database.php';
$conectar = mysql_connect($host, $user, $clave);
mysql_select_db($datbase, $conectar);

// Obtener el último folio y sumarle 1 directamente en la consulta
$sql = "SELECT (MAX(CAST(folio AS UNSIGNED)) + 1) as siguiente_folio FROM adm_pedidos";
$resultado = mysql_query($sql);
$row = mysql_fetch_assoc($resultado);

$nextFolio = $row['siguiente_folio'];

$response = array(
    'status' => 'success',
    'nextFolio' => $nextFolio
);

header('Content-Type: application/json');
echo json_encode($response);
mysql_close($conectar);
?>
