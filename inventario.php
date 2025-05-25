<?php
ob_start(); // Iniciar el buffer de salida
include('pages/head.php');
include('pages/navbar.php');
include('pages/menu.php');
include('pages/inventario.php');
include('pages/footer.php');
echo '<script src="plugins/jquery/jquery.min.js"></script>';
include('pages/end.php');
ob_end_flush(); // Liberar el buffer de salida
?>