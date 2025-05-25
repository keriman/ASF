<script>
	// Script para detectar Ctrl+S y activar el botón de guardar inventario
	(function() {
	  console.log("Configurando detector de Ctrl+S para guardar inventario...");
	  
	  // Estilo para mostrar notificaciones visuales
	  const style = document.createElement('style');
	  style.textContent = `
	    .keyboard-notification {
	      position: fixed;
	      bottom: 20px;
	      right: 20px;
	      background-color: #4CAF50;
	      color: white;
	      padding: 10px 20px;
	      border-radius: 4px;
	      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
	      z-index: 9999;
	      font-family: Arial, sans-serif;
	      transition: opacity 0.3s, transform 0.3s;
	      opacity: 0;
	      transform: translateY(20px);
	    }
	    .keyboard-notification.show {
	      opacity: 1;
	      transform: translateY(0);
	    }
	    .keyboard-notification.error {
	      background-color: #F44336;
	    }
	  `;
	  document.head.appendChild(style);
	  
	  // Función para mostrar notificaciones
	  function showNotification(message, isError = false) {
	    // Eliminar notificaciones existentes
	    const existingNotifications = document.querySelectorAll('.keyboard-notification');
	    existingNotifications.forEach(n => n.remove());
	    
	    // Crear nueva notificación
	    const notification = document.createElement('div');
	    notification.className = 'keyboard-notification' + (isError ? ' error' : '');
	    notification.textContent = message;
	    document.body.appendChild(notification);
	    
	    // Mostrar con animación
	    setTimeout(() => {
	      notification.classList.add('show');
	    }, 10);
	    
	    // Ocultar después de 3 segundos
	    setTimeout(() => {
	      notification.classList.remove('show');
	      setTimeout(() => notification.remove(), 300);
	    }, 3000);
	  }
	  
	  // Verificar si el botón de guardar inventario existe
	  function checkSaveButton() {
	    const saveButton = document.getElementById('save-batch-btn');
	    if (!saveButton) {
	      console.warn("Advertencia: No se encontró el botón de guardar (save-batch-btn) - verificando de nuevo en 2 segundos...");
	      setTimeout(checkSaveButton, 2000);
	      return false;
	    }
	    console.log("Botón de guardar encontrado:", saveButton);
	    return true;
	  }
	  
	  // Función para activar el guardado
	  function triggerSave() {
	    console.log("Detectado Ctrl+S - Intentando guardar inventario...");
	    
	    // Buscar el botón de guardar
	    const saveButton = document.getElementById('save-batch-btn');
	    
	    if (saveButton) {
	      // Verificar si está habilitado
	      if (saveButton.disabled) {
	        console.warn("No se puede guardar: El botón está deshabilitado (no hay productos en el lote)");
	        showNotification("No hay productos en el lote para guardar", true);
	        return false;
	      }
	      
	      console.log("Activando botón de guardar:", saveButton);
	      
	      // Hacer clic en el botón
	      try {
	        saveButton.click();
	        console.log("Guardado de inventario ejecutado exitosamente");
	        showNotification("Inventario guardado correctamente");
	        return true;
	      } catch (error) {
	        console.error("Error al hacer clic en el botón:", error);
	        showNotification("Error al guardar el inventario: " + error.message, true);
	        return false;
	      }
	    } else {
	      console.error("No se encontró el botón de guardar (save-batch-btn)");
	      showNotification("Error: No se encontró el botón de guardar", true);
	      return false;
	    }
	  }
	  
	  // Agregar listener para el evento de teclado
	  document.addEventListener('keydown', function(event) {
	    // Detectar Ctrl+S (o Cmd+S en Mac)
	    if ((event.ctrlKey || event.metaKey) && event.key === 's') {
	      // Prevenir el comportamiento predeterminado (guardar página)
	      event.preventDefault();
	      
	      // Activar el guardado de inventario
	      triggerSave();
	    }
	  });
	  
	  // Verificar que el botón existe
	  if (checkSaveButton()) {
	    console.log("%cAtajo de teclado Ctrl+S configurado para guardar inventario", "color:green; font-weight:bold;");
	    showNotification("Sistema de atajo Ctrl+S activado");
	  }
	})();
</script>
<script>
// Configuración de conexión con el ESP8266
(function() {
  console.log("Iniciando conexión con el dispositivo ESP8266...");
  
  // Dirección del ESP8266 - cambia a la IP correcta si mDNS no funciona
  const esp8266Url = 'http://192.168.31.34';
  // Alternativa usando IP directa
  // const esp8266Url = 'http://192.168.1.x'; // Reemplaza con la IP de tu ESP8266
  
  // Bandera para evitar múltiples guardados simultáneos
  let savingInProgress = false;
  
  // Función para verificar comandos pendientes del ESP8266
  function checkCommands() {
    // Si ya hay un guardado en progreso, esperar
    if (savingInProgress) {
      return;
    }
    
    fetch(`${esp8266Url}/check-command`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Error de red o servidor');
        }
        return response.json();
      })
      .then(data => {
        if (data.command === 'ctrl-s') {
          console.log("Comando Ctrl+S recibido desde ESP8266");
          savingInProgress = true;
          
          // Buscar el botón de guardar
          const saveBatchBtn = document.getElementById('save-batch-btn');
          
          if (saveBatchBtn) {
            // Verificar si está habilitado
            if (saveBatchBtn.disabled) {
              console.warn("No se puede guardar: El botón está deshabilitado (no hay productos en el lote)");
              savingInProgress = false;
              return;
            }
            
            console.log("Activando botón de guardar lote...");
            
            // Hacer clic en el botón
            try {
              saveBatchBtn.click();
              console.log("Lote de inventario guardado correctamente");
              
              // Restablecer la bandera después de un tiempo más largo por SweetAlert
              setTimeout(() => {
                savingInProgress = false;
              }, 5000); // 5 segundos para permitir que SweetAlert complete su proceso
            } catch (error) {
              console.error("Error al hacer clic en el botón:", error);
              savingInProgress = false;
            }
          } else {
            console.error("No se encontró el botón 'save-batch-btn'");
            savingInProgress = false;
          }
        }
      })
      .catch(error => {
        // Si hay un error, es probable que el ESP8266 no esté disponible
        // No mostramos error en consola para evitar spam si está desconectado
        savingInProgress = false;
      });
  }
  
  // Verificar el estado del ESP8266 al iniciar
  fetch(`${esp8266Url}/status`)
    .then(response => response.json())
    .then(data => {
      console.log("ESP8266 conectado:", data);
      console.log("%cDispositivo ESP8266 activado en " + data.ip, "color:green; font-weight:bold;");
    })
    .catch(error => {
      console.warn("No se pudo conectar con el ESP8266:", error);
    });
  
  // Iniciar verificación periódica de comandos (cada 1 segundo)
  const intervalId = setInterval(checkCommands, 1000);
  
  // Función para limpiar el intervalo si la página se cierra
  window.addEventListener('beforeunload', function() {
    clearInterval(intervalId);
  });
})();
</script>
<?php if (isset($_SESSION['swal_success'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Éxito',
            text: '<?php echo $_SESSION['swal_message']; ?>',
            icon: 'success',
            showConfirmButton: false,
            timer: 3000,  // Se cerrará automáticamente después de 3 segundos
            timerProgressBar: true
        }).then(() => {
            // Cuando la alerta se cierre, redirigir a la página del escáner
            window.location.href = 'altaProductos.php';
        });
    });
</script>
<?php 
    unset($_SESSION['swal_success']);
    unset($_SESSION['swal_message']);
endif; 
?>
<script>
// CÃ³digo para conectar con el ESP8266 (actualiza cada 10 segundos)
setInterval(function() {
  fetch('http://192.168.31.34/check-command')
    .then(response => response.json())
    .then(data => {
      if (data.command === 'ctrl-s') {
        // Usar tu funciÃ³n de guardado existente
        if (typeof triggerSave === 'function') {
          triggerSave();
        }
      }
    })
    .catch(error => console.error('Error al verificar comandos:', error));
}, 1000);
</script>