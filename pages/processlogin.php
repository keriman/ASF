<?php
// Incluir la conexión a la base de datos
require 'conexion.php'; // Asegúrate de que este archivo esté en la ruta correcta

// Iniciar sesión para almacenar datos del usuario
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Validar que los campos no estén vacíos
    if (empty($usuario) || empty($password)) {
        echo "Por favor, complete todos los campos.";
        exit;
    }

    try {
        // Preparar la consulta para buscar el usuario en la base de datos
        $stmt = $pdo->prepare("SELECT id, user, password, level FROM Usuarios WHERE user = :user");
        $stmt->execute(['user' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verificar la contraseña
            if (password_verify($password, $user['password'])) {
                // Autenticación exitosa: almacenar datos en la sesión
                $_SESSION['id'] = $user['id'];
                $_SESSION['user'] = $user['user'];
                $_SESSION['level'] = $user['level'];

                // Redirigir al panel de control u otra página
                header("Location: ../index.php");
                exit;
            } else {
                // Contraseña incorrecta
                echo "Contraseña incorrecta.";
            }
        } else {
            // Usuario no encontrado
            echo "Usuario no encontrado.";
        }
    } catch (PDOException $e) {
        echo "Error al procesar el inicio de sesión: " . $e->getMessage();
    }
}
?>
