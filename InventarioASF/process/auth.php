<?php
//auth.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'php_errors.log');

// Definir la función de conexión a la base de datos
function getDatabase() {
    $host = 'localhost';
    $dbname = 'agrosant_pedidos';
    $username = 'root';
    $password = '';
    
    try {
        // Crear conexión usando PDO
        $db = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        return $db;
    } catch (PDOException $e) {
        error_log("Error de conexión: " . $e->getMessage());
        die('Error del sistema: No se pudo conectar a la base de datos');
    }
}

// Manejar el inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Por favor ingrese usuario y contraseña';
        header('Location: login.php');
        exit;
    }
    
    $db = getDatabase();
    
    try {
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Inicio de sesión exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redireccionar a la página principal
            header('Location: index.php');
            exit;
        } else {
            // Credenciales incorrectas
            $_SESSION['error'] = 'Usuario o contraseña incorrectos';
            header('Location: login.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Error de inicio de sesión: " . $e->getMessage());
        $_SESSION['error'] = 'Error del sistema. Por favor intente más tarde.';
        header('Location: login.php');
        exit;
    }
}

// Cerrar sesión
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la sesión
    session_destroy();
    
    // Redireccionar a la página de inicio de sesión
    header('Location: login.php');
    exit;
}

// Función para verificar si el usuario ha iniciado sesión
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para verificar si el usuario es administrador
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Función para requerir inicio de sesión
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Debe iniciar sesión para acceder a esta página';
        header('Location: login.php');
        exit;
    }
}

// Función para requerir permisos de administrador
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        $_SESSION['error'] = 'No tiene permisos para acceder a esta función';
        header('Location: index.php');
        exit;
    }
}