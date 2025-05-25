<?php
ob_start(); // Iniciar el buffer de salida
include('pages/head.php');
include('pages/navbar.php');
include('pages/menu.php');
include('pages/vendedores.php');
include('pages/footer.php');
include('pages/end.php');
ob_end_flush(); // Liberar el buffer de salida
?>