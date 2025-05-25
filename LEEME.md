# ASF - Agro Stock Flow

## Panorama General

ASF (Agro Stock Flow) es un conjunto integral de aplicaciones web diseñadas para optimizar y gestionar las operaciones de almacén, especialmente adaptadas para la gestión de productos agrícolas. Proporciona una variedad de herramientas para manejar diversos aspectos de la gestión de almacenes, desde el control de inventario hasta el procesamiento de pedidos.

El proyecto ASF consta de los siguientes módulos clave:

*   **AlmacenASF:** El módulo principal de gestión de almacenes, responsable de supervisar el almacenamiento, la organización y el movimiento de mercancías dentro del almacén.
*   **GenerarQR:** Una utilidad para generar códigos QR, utilizados para la identificación de artículos, el seguimiento o la vinculación a la información del producto.
*   **InventarioASF:** Dedicado a la gestión de inventario, este módulo se encarga de rastrear los niveles de stock, gestionar los datos de inventario y apoyar los procesos de inventariado.
*   **PedidosASF:** Centrado en la gestión de pedidos, este módulo facilita el procesamiento de los pedidos de los clientes, desde la recepción hasta el cumplimiento. Incluye una integración específica para "Quinagro".

Las aplicaciones dentro del conjunto ASF se desarrollan principalmente utilizando **PHP** para la lógica del lado del servidor y **Node.js** para ciertas funcionalidades o servicios (específicamente AlmacenASF). Las interfaces de usuario se construyen utilizando el tema **AdminLTE**, proporcionando una experiencia de usuario consistente y receptiva.

### Nota sobre las Horas Totales de Trabajo
Es importante mencionar que la cantidad total de horas de trabajo invertidas en este proyecto no se puede determinar con precisión a partir de la información disponible en el código base.

## Descripción de los Módulos

### AlmacenASF

**AlmacenASF** es el sistema central de gestión de almacenes dentro del conjunto ASF. Es una aplicación **Node.js** diseñada para proporcionar un control integral sobre las operaciones del almacén.

Las características y aspectos clave de AlmacenASF incluyen:

*   **Gestión Central de Almacén:** Gestiona el almacenamiento, la organización y el movimiento de mercancías (como se indica en `warehouse.js`).
*   **Gestión de Producción:** Incluye funcionalidades para el seguimiento o la gestión de los procesos de producción (sugerido por `production.html` y `assets/js/production.js`).
*   **Backend en Tiempo Real:** Utiliza `backend.js` y `server.js` para manejar el procesamiento de datos en tiempo real y la comunicación para las actividades del almacén.
*   **Interfaz de Usuario:** Proporciona una interfaz basada en web (probablemente `production.html` y `warehouse.html`) para que los usuarios interactúen con el sistema.

### GenerarQR

**GenerarQR** es un módulo dedicado para generar e imprimir códigos QR dentro del conjunto ASF. Es una aplicación **PHP** que aprovecha la biblioteca `phpqrcode` (ubicada en el directorio raíz del proyecto `lib/`) para crear códigos QR.

Las funcionalidades y scripts clave incluyen:

*   **Generación de Códigos QR:** Scripts como `generar_qr.php`, `generar_qr_simple.php` y `generar_qr_simple_q.php` son responsables de crear códigos QR basados en los datos de entrada. Las variantes `_q` probablemente pertenecen a la integración "Quinagro".
*   **Impresión de Códigos QR:** Scripts como `imprimir.php`, `imprimir_simple.php` e `imprimir_todos.php` (y sus variantes `_q`) facilitan la impresión de los códigos QR generados.
*   **Interfaz de Usuario:** `index.php` e `indexQ.php` sirven como interfaces web para que los usuarios activen la generación e impresión de códigos QR.

### InventarioASF

**InventarioASF** es el módulo de gestión de inventario del conjunto ASF. Esta aplicación **PHP** proporciona herramientas para el seguimiento del stock, la gestión de datos de productos y la optimización de las operaciones de inventario. Utiliza una base de datos SQLite (`InventarioASF/assets/db/inventory.db`) para el almacenamiento de datos.

Las características y componentes clave incluyen:

*   **Lógica Central de Inventario:** Manejada por scripts como `InventarioASF/process/InventorySystem.php`, que contiene la lógica de negocio principal para las operaciones de inventario.
*   **Integración de Códigos de Barras:** Amplias funcionalidades de códigos de barras están disponibles a través de scripts como `barcode_scanner.php` (para escanear), `assign_barcode.php` (para asociar códigos de barras con productos) y `manage_barcodes.php` (para la gestión general de códigos de barras).
*   **Autenticación de Usuarios:** El acceso seguro se gestiona a través de `InventarioASF/auth.php` y el directorio `InventarioASF/login/`.
*   **Operaciones por Lotes:** Admite el procesamiento de inventario por lotes, con scripts como `process_batch.php` para manejar actualizaciones por lotes y `batch_history.php` para rastrear las actividades de los lotes.
*   **Gestión de Productos:** Funciones como `import-products.php` permiten la importación de datos de productos.
*   **Actualizaciones en Tiempo Real:** Incorpora un componente `InventarioASF/websocket_server/websocket_server.php`, lo que indica el uso de WebSockets para la comunicación en tiempo real de cambios o actualizaciones de inventario.
*   **Interfaz de Usuario:** El principal punto de interacción del usuario es `InventarioASF/index.php`.

### PedidosASF

**PedidosASF** es el módulo de gestión de pedidos del conjunto ASF. Esta aplicación **PHP** está diseñada para manejar los pedidos de los clientes, desde la creación hasta el cumplimiento, e incluye integraciones especializadas.

Las características y componentes clave incluyen:

*   **Núcleo de Gestión de Pedidos y Productos:** El directorio `PedidosASF/pedidos/`, particularmente `PedidosASF/pedidos/alta/alta_pedidos.php` y `PedidosASF/pedidos/alta/alta_productos.php`, forma el núcleo para crear y gestionar pedidos y productos.
*   **Puntos de Acceso API (Endpoints):**
    *   `PedidosASF/API_ASF/`: Proporciona una gama de funcionalidades API que incluyen la creación de productos (`app_alta_productos.php`), guardado de pedidos (`app_save_order.php`), inicio de sesión de usuarios (`app_login.php`) y recuperación de datos de ventas (`get_ventas.php`).
    *   `PedidosASF/API_Quinagro/`: Una estructura API paralela específicamente para "Quinagro", que refleja muchas de las funcionalidades de la API de ASF. Esto sugiere que Quinagro podría ser un cliente distinto o un modo operativo especializado que requiere su propio conjunto de API.
*   **Interfaz de Administración:** El directorio `PedidosASF/admin/` probablemente proporciona herramientas administrativas, incluida la gestión de vendedores (`vendedores.php`).
*   **Interfaz de Operaciones de Almacén:** El directorio `PedidosASF/almacen/` ofrece funcionalidades adaptadas para el personal de almacén, incluido el procesamiento de pedidos y la gestión de productos, con versiones distintas para ASF y Quinagro (p. ej., `procesar_vendedores.php` vs `procesar_vendedoresq.php`).
*   **Conectividad de Base de Datos:** Las conexiones a la base de datos se gestionan de forma centralizada a través de `PedidosASF/conexiones/database.php`.
*   **Gestión y Autenticación de Usuarios:** El acceso de usuarios y el inicio de sesión son manejados por scripts en el directorio `PedidosASF/usuarios/` y varios archivos de inicio de sesión raíz dentro de `PedidosASF/` (p. ej., `login.php`, `login2.php`).
*   **Informes/Exportación:** Funciones como `exportar_excel.php` en `PedidosASF/almacen/` y `PedidosASF/gerencia/` indican capacidades para la exportación de datos.
*   **Interfaz de Usuario Principal:** `PedidosASF/index.php` sirve como el punto de entrada principal para los usuarios que interactúan con este módulo.

## Aplicaciones Android

El ecosistema ASF también incluye aplicaciones móviles para Android, diseñadas para extender la funcionalidad de gestión de pedidos a dispositivos móviles.

*   **ASFPedidos:** Esta aplicación es probablemente un cliente móvil para el sistema `PedidosASF`. Permite a los usuarios gestionar pedidos, interactuando presumiblemente con los puntos de acceso de `PedidosASF/API_ASF/` para sincronizar datos y realizar operaciones.
*   **QuinagoPedidos:** Similar a ASFPedidos, esta aplicación parece ser una versión específica para las operaciones de "Quinagro". Se conectaría a los puntos de acceso de `PedidosASF/API_Quinagro/`, permitiendo la gestión de pedidos adaptada a los requisitos de Quinagro.

Estas aplicaciones móviles proporcionan flexibilidad y acceso en tiempo real a la gestión de pedidos para usuarios en movimiento o aquellos que operan directamente en el campo o en el almacén.

## Componentes Compartidos y Estructura del Directorio Raíz

Más allá de los módulos específicos, varios componentes y archivos en el directorio raíz del proyecto juegan roles cruciales en el ecosistema general de ASF:

*   **`api/`**: Este directorio alberga scripts PHP que probablemente sirven como una API de propósito general para operaciones comunes como búsquedas de inventario (`inventory.php`), recuperación de datos de productos (`products.php`) y finalización de procesos (`finalize.php`). También contiene un archivo `config.php`, lo que sugiere un punto de configuración centralizado para estos servicios API.

*   **Tema AdminLTE (`build/`, `dist/`, `docs/`, `plugins/`, `pages/`)**: Una porción significativa del directorio raíz está dedicada al tema AdminLTE, que forma la base de la interfaz de usuario para las aplicaciones PHP.
    *   `build/`: Contiene scripts de construcción y configuraciones para el tema AdminLTE.
    *   `dist/`: Contiene los archivos CSS, JavaScript e imágenes distribuibles (compilados) para AdminLTE.
    *   `docs/`: Incluye documentación para el tema AdminLTE.
    *   `plugins/`: Contiene varios plugins de jQuery y otras bibliotecas de JavaScript utilizadas por AdminLTE para proporcionar componentes de interfaz de usuario enriquecidos (por ejemplo, selectores de fecha, gráficos, tablas de datos).
    *   `pages/`: Contiene páginas de ejemplo y demostraciones de elementos de interfaz de usuario proporcionadas por AdminLTE, que pueden haber sido utilizadas como plantillas o referencias.

*   **`lib/phpqrcode/`**: Este directorio contiene la biblioteca `phpqrcode`, utilizada por el módulo `GenerarQR` (y potencialmente otras partes del sistema) para generar códigos QR.

*   **Archivos PHP Raíz**: Muchos archivos PHP existen en el directorio raíz. Estos se pueden categorizar ampliamente:
    *   **Puntos de Entrada y Funcionalidad Principal:**
        *   `index.php`: Probablemente la página de destino principal o el panel central para todo el conjunto de aplicaciones ASF.
        *   `login.php`: La página de inicio de sesión principal para la autenticación de usuarios.
        *   `almacen.php`, `pedidos.php`, `inventario.php`: Estos archivos parecen ser puntos de entrada principales o controladores para acceder a las funcionalidades de las secciones/módulos de Almacén, Pedidos e Inventario respectivamente.
    *   **Gestión de Productos y Vendedores:**
        *   `altaProductos.php`: Script para agregar nuevos productos.
        *   `bajaProductos.php`: Script para eliminar o desactivar productos.
        *   `products/`: Este directorio (que contiene `get_products.php`) parece estar relacionado con la gestión de datos de productos.
        *   `vendedores.php`, `vendedoresq.php`: Scripts para gestionar vendedores, siendo `vendedoresq.php` probablemente específico para Quinagro.
    *   **Generación y Registro:**
        *   `generador.php`, `generadorq.php`: Puntos de acceso alternativos o directos para la generación de códigos QR, con `generadorq.php` específico para Quinagro.
        *   `register.php`: Página de registro de usuarios.
    *   **Otras Utilidades:**
        *   `production.php`: Podría ser un script relacionado con la visualización del estado de producción o la gestión de datos relacionados con la producción.
        *   `assets/`: Contiene activos estáticos compartidos como imágenes, JavaScript (p. ej. `moment-with-locales.min.js`) y CSS utilizados en diferentes scripts PHP en la raíz.
        *   `temp/qrcodes/`: Un directorio temporal para almacenar imágenes de códigos QR generadas.

*   **Configuración y Registros (Logs):**
    *   `.htaccess`: Archivo de configuración del servidor Apache, probablemente para reescritura de URL o control de acceso.
    *   `composer.json`: Configuración para la gestión de paquetes PHP (Composer), principalmente para las herramientas de construcción de AdminLTE en este contexto.
    *   `package.json`, `package-lock.json`: Configuración para la gestión de paquetes Node.js (npm), probablemente para AlmacenASF y las herramientas de construcción de AdminLTE.
    *   `php_errors.log`: Un archivo de registro para errores de PHP que ocurren en los scripts de nivel raíz.
