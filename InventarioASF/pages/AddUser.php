<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Usuario</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">Registrar Nuevo Usuario</h2>
            
            <?php
            if (isset($_SESSION['message'])) {
                $messageClass = isset($_SESSION['error']) ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                echo "<div class='$messageClass p-4 rounded mb-4'>" . htmlspecialchars($_SESSION['message']) . "</div>";
                unset($_SESSION['message']);
                unset($_SESSION['error']);
            }
            ?>

            <form action="../process/process_user.php" method="POST" class="space-y-6">
                <!-- Nombre de Usuario -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Confirmar Contraseña -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Rol -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Rol</label>
                    <select id="role" 
                            name="role" 
                            required 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="admin">Administrador</option>
                        <option value="user">Usuario</option>
                    </select>
                </div>

                <!-- Botón de Submit -->
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Registrar Usuario
                </button>
            </form>
        </div>
    </div>
</body>
</html>