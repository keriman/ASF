<?php
session_start();

class UserManager {
    private $db;

    public function __construct() {
        $this->db = new SQLite3('../assets/db/inventory.db');
        $this->checkAndUpdateTable();
    }

    private function checkAndUpdateTable() {
        // Verificar si la tabla existe
        $tableExists = $this->db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        
        if ($tableExists) {
            // Verificar si la columna role existe
            $hasRoleColumn = $this->db->querySingle("PRAGMA table_info(users)") !== null;
            
            if (!$hasRoleColumn) {
                // Crear tabla temporal con la nueva estructura
                $this->db->exec("CREATE TABLE users_temp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    role TEXT NOT NULL CHECK(role IN ('admin', 'user')),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");

                // Copiar datos existentes
                $this->db->exec("INSERT INTO users_temp (id, username, password, role, created_at)
                               SELECT id, username, password, 'user' as role, created_at FROM users");

                // Eliminar tabla antigua y renombrar la nueva
                $this->db->exec("DROP TABLE users");
                $this->db->exec("ALTER TABLE users_temp RENAME TO users");
            }
        } else {
            // Crear la tabla si no existe
            $this->db->exec("CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('admin', 'user')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }
    }

    public function addUser($username, $password, $role) {
        try {
            // Verificar si el usuario ya existe
            $checkStmt = $this->db->prepare('SELECT id FROM users WHERE username = :username');
            $checkStmt->bindValue(':username', $username, SQLITE3_TEXT);
            $result = $checkStmt->execute();

            if ($result->fetchArray()) {
                throw new Exception("El nombre de usuario ya existe");
            }

            // Insertar nuevo usuario
            $stmt = $this->db->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $this->db->lastErrorMsg());
            }

            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
            $stmt->bindValue(':role', $role, SQLITE3_TEXT);

            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception("Error al crear usuario: " . $e->getMessage());
        }
    }

    public function __destruct() {
        $this->db->close();
    }
}

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validar datos
        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['confirm_password']) || empty($_POST['role'])) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Las contraseñas no coinciden");
        }

        if (strlen($_POST['password']) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres");
        }

        if (!in_array($_POST['role'], ['admin', 'user'])) {
            throw new Exception("Rol no válido");
        }

        // Crear usuario
        $userManager = new UserManager();
        if ($userManager->addUser($_POST['username'], $_POST['password'], $_POST['role'])) {
            $_SESSION['message'] = "Usuario creado exitosamente";
            header("Location: ../pages/login.php");
            exit();
        } else {
            throw new Exception("Error al crear el usuario");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = true;
        $_SESSION['message'] = $e->getMessage();
        header("Location: ../pages/login.php");
        exit();
    }
}
?>