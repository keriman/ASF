<?php
// Enable error reporting
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Force output as JSON with UTF-8
header('Content-Type: application/json; charset=utf-8');

try {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "agrosant_pedidos";

    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Set the connection to use UTF-8
    $conn->set_charset("utf8");

    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    // Query to get vendors
    $sql = "SELECT * FROM vendedores ORDER BY nombre";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $vendedores = array();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Clean each value to ensure proper UTF-8
            $id = $row["id"];
            $nombre = mb_convert_encoding($row["nombre"], 'UTF-8', 'UTF-8');
            $telefono = mb_convert_encoding($row["telefono"], 'UTF-8', 'UTF-8');
            
            $vendedores[] = array(
                "id" => $id,
                "name" => $nombre,
                "fon" => $telefono
            );
        }
        
        $response = array(
            "error" => false,
            "vendedores" => $vendedores
        );
    } else {
        $response = array(
            "error" => true,
            "message" => "No se encontraron vendedores"
        );
    }

    $conn->close();
    
    // Ensure proper JSON encoding with UTF-8 handling
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If there's still an encoding error, try sanitizing the whole array
        $cleaned_response = cleanForJson($response);
        $json_response = json_encode($cleaned_response, JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON encoding error: " . json_last_error_msg());
        }
    }
    
    echo $json_response;
    
} catch (Exception $e) {
    // If any error occurs, return it as JSON
    $error_response = array(
        "error" => true,
        "message" => $e->getMessage()
    );
    
    echo json_encode($error_response);
}

// Function to clean an array recursively for JSON encoding
function cleanForJson($data) {
    if (is_array($data)) {
        $clean = [];
        foreach ($data as $key => $value) {
            $clean[$key] = cleanForJson($value);
        }
        return $clean;
    } else {
        // Convert to UTF-8 and remove invalid characters
        return is_string($data) ? 
               iconv('UTF-8', 'UTF-8//IGNORE', $data) : 
               $data;
    }
}
?>