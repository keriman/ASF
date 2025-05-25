<?php
// websocket_server.php
// Este script debe ejecutarse en el servidor para manejar la comunicación entre ESP32 y la aplicación web.

// Incluir la biblioteca Ratchet de WebSockets
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Clase principal del servidor WebSocket
class ButtonServer implements MessageComponentInterface {
    protected $clients;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "WebSocket Server started\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        // Almacenar la nueva conexión
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        
        // Decodificar el mensaje JSON
        $data = json_decode($msg, true);
        
        // Si el mensaje es del ESP32 y es para guardar el lote
        if (isset($data['action']) && $data['action'] === 'save_batch') {
            $this->broadcastToWebClients($from->resourceId, 'save_batch');
        }
        
        // Enviar el mensaje a todos los clientes conectados excepto al remitente
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }
    
    // Método para enviar un mensaje a todos los clientes web (navegadores)
    protected function broadcastToWebClients($fromId, $action) {
        // Crear un mensaje para la página web
        $message = json_encode([
            'from' => 'ESP32',
            'fromId' => $fromId,
            'action' => $action,
            'timestamp' => time()
        ]);
        
        // Enviar a todos los clientes que no sean el ESP32
        foreach ($this->clients as $client) {
            if ($client->resourceId != $fromId) {
                echo "Broadcasting action '$action' to client {$client->resourceId}\n";
                $client->send($message);
            }
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        // Eliminar la conexión cerrada
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Crear y ejecutar el servidor
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ButtonServer()
        )
    ),
    1337  // Puerto que debe coincidir con el configurado en el ESP32
);

echo "WebSocket Server running on port 1337\n";
$server->run();