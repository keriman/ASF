<?php

require 'conexion.php';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener valores del formulario
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    $terms = isset($_POST['terms']);

    // Validar campos requeridos
    if (empty($nombre) || empty($usuario) || empty($password) || empty($password_confirm)) {
        echo "Por favor, complete todos los campos.";
        exit;
    }

    // Validar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        echo "Las contraseñas no coinciden.";
        exit;
    }

    // Validar que el usuario aceptó los términos
    if (!$terms) {
        echo "Debe aceptar los términos y condiciones.";
        exit;
    }

    // Hash de la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar el usuario en la base de datos
    try {
        $stmt = $pdo->prepare("INSERT INTO Usuarios (user, password) VALUES (:user, :password)");
        $stmt->execute([
            'user' => $usuario,
            'password' => $hashed_password
        ]);

        echo "Usuario registrado exitosamente.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Código de error para violación de UNIQUE
            echo "El usuario ya existe.";
        } else {
            echo "Error al registrar el usuario: " . $e->getMessage();
        }
    }
}
?>
