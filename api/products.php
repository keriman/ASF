<?php
require_once 'config.php';

try {
    $products = $inventory->listProducts();
    sendResponse($products);
} catch (Exception $e) {
    handleError($e);
}