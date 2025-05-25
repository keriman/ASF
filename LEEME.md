# ASF - Agro Stock Flow

## Descripción General

ASF (Agro Stock Flow) es un conjunto completo de aplicaciones web diseñado para optimizar y gestionar las operaciones de almacén, especialmente adaptado para la gestión de productos agrícolas. Proporciona una gama de herramientas para manejar varios aspectos de la gestión de almacenes, desde el control de inventario hasta el procesamiento de pedidos.

El proyecto ASF consta de los siguientes módulos clave:

*   **AlmacenASF:** El módulo principal de gestión de almacén, responsable de supervisar el almacenamiento, organización y movimiento de mercancías dentro del almacén.
*   **GenerarQR:** Una utilidad para generar códigos QR, utilizada para la identificación de artículos, seguimiento o enlace a información de productos.
*   **InventarioASF:** Dedicado a la gestión de inventarios, este módulo maneja el seguimiento de niveles de stock, gestión de datos de inventario y soporte a procesos de recuento de existencias.
*   **PedidosASF:** Enfocado en la gestión de pedidos, este módulo facilita el procesamiento de pedidos de clientes, desde la recepción hasta el cumplimiento. Incluye una integración específica para "Quinagro".

Las aplicaciones dentro de la suite ASF están desarrolladas principalmente usando **PHP** para la lógica del lado del servidor y **Node.js** para ciertas funcionalidades o servicios (específicamente AlmacenASF). Las interfaces de usuario están construidas usando el tema **AdminLTE**, proporcionando una experiencia de usuario consistente y responsiva.

## Descripción de Módulos

### AlmacenASF

**AlmacenASF** es el sistema central de gestión de almacén dentro de la suite ASF. Es una aplicación de **Node.js** diseñada para proporcionar control completo sobre las operaciones de almacén.

Las características y aspectos clave de AlmacenASF incluyen:

*   **Gestión Central del Almacén:** Gestiona el almacenamiento, organización y movimiento de mercancías (como indica `warehouse.js`).
*   **Gestión de Producción:** Incluye funcionalidades para rastrear o gestionar procesos de producción (sugerido por `production.html` y `assets/js/production.js`).
*   **Backend en Tiempo Real:** Utiliza `backend.js` y `server.js` para manejar el procesamiento de datos en tiempo real y la comunicación para actividades de almacén.
*   **Interfaz de Usuario:** Proporciona una interfaz basada en web (probablemente `production.html` y `warehouse.html`) para que los usuarios interactúen con el sistema.

### GenerarQR

**GenerarQR** es un módulo dedicado para generar e imprimir códigos QR dentro de la suite ASF. Es una aplicación de **PHP** que aprovecha la biblioteca `phpqrcode` (ubicada en el directorio `lib/` raíz del proyecto) para crear códigos QR.

Las funcionalidades y scripts clave incluyen:

*   **Generación de Códigos QR:** Scripts como `generar_qr.php`, `generar_qr_simple.php` y `generar_qr_simple_q.php` son responsables de crear códigos QR basados en datos de entrada. Las variantes `_q` probablemente se refieren a la integración "Quinagro".
*   **Impresión de Códigos QR:** Scripts como `imprimir.php`, `imprimir_simple.php` e `imprimir_todos.php` (y sus variantes `_q`) facilitan la impresión de los códigos QR generados.
*   **Interfaz de Usuario:** `index.php` e `indexQ.php` sirven como interfaces web para que los usuarios activen la generación e impresión de códigos QR.

### InventarioASF

**InventarioASF** es el módulo de gestión de inventarios de la suite ASF. Esta aplicación de **PHP** proporciona herramientas para rastrear stock, gestionar datos de productos y optimizar las operaciones de inventario. Utiliza una base de datos SQLite (`InventarioASF/assets/db/inventory.db`) para el almacenamiento de datos.

Las características y componentes clave incluyen:

*   **Lógica Central de Inventario:** Manejada por scripts como `InventarioASF/process/InventorySystem.php`, que contiene la lógica de negocio principal para las operaciones de inventario.
*   **Integración de Códigos de Barras:** Funcionalidades extensas de códigos de barras están disponibles a través de scripts como `barcode_scanner.php` (para escanear), `assign_barcode.php` (para asociar códigos de barras con productos) y `manage_barcodes.php` (para la gestión general de códigos de barras).
*   **Autenticación de Usuario:** El acceso seguro se gestiona vía `InventarioASF/auth.php` y el directorio `InventarioASF/login/`.
*   **Operaciones por Lotes:** Soporta el procesamiento de inventario en lotes, con scripts como `process_batch.php` para manejar actualizaciones por lotes y `batch_history.php` para rastrear actividades de lotes.
*   **Gestión de Productos:** Características como `import-products.php` permiten la importación de datos de productos.
*   **Actualizaciones en Tiempo Real:** Incorpora un componente `InventarioASF/websocket_server/websocket_server.php`, indicando el uso de WebSockets para comunicación en tiempo real de cambios o actualizaciones de inventario.
*   **Interfaz de Usuario:** El punto principal de interacción del usuario es `InventarioASF/index.php`.

### PedidosASF

**PedidosASF** es el módulo de gestión de pedidos de la suite ASF. Esta aplicación de **PHP** está diseñada para manejar pedidos de clientes, desde la creación hasta el cumplimiento, e incluye integraciones especializadas.

Las características y componentes clave incluyen:

*   **Núcleo de Gestión de Pedidos y Productos:** El directorio `PedidosASF/pedidos/`, particularmente `PedidosASF/pedidos/alta/alta_pedidos.php` y `PedidosASF/pedidos/alta/alta_productos.php`, forma el núcleo para crear y gestionar pedidos y productos.
*   **Endpoints de API:**
    *   `PedidosASF/API_ASF/`: Proporciona una gama de funcionalidades de API incluyendo creación de productos (`app_alta_productos.php`), guardado de pedidos (`app_save_order.php`), login de usuario (`app_login.php`) y recuperación de datos de ventas (`get_ventas.php`).
    *   `PedidosASF/API_Quinagro/`: Una estructura de API paralela específicamente para "Quinagro", reflejando muchas de las funcionalidades de la API ASF. Esto sugiere que Quinagro podría ser un cliente distinto o un modo operacional especializado que requiere su propio conjunto de API.
*   **Interfaz de Administración:** El directorio `PedidosASF/admin/` probablemente proporciona herramientas administrativas, incluyendo gestión de vendedores (`vendedores.php`).
*   **Interfaz de Operaciones de Almacén:** El directorio `PedidosASF/almacen/` ofrece funcionalidades adaptadas para el personal de almacén, incluyendo procesamiento de pedidos y gestión de productos, con versiones distintas para ASF y Quinagro (ej., `procesar_vendedores.php` vs `procesar_vendedoresq.php`).
*   **Conectividad de Base de Datos:** Las conexiones de base de datos se gestionan centralmente vía `PedidosASF/conexiones/database.php`.
*   **Gestión de Usuarios y Autenticación:** El acceso de usuarios y login se maneja por scripts en el directorio `PedidosASF/usuarios/` y varios archivos de login raíz dentro de `PedidosASF/` (ej., `login.php`, `login2.php`).
*   **Reportes/Exportación:** Características como `exportar_excel.php` en `PedidosASF/almacen/` y `PedidosASF/gerencia/` indican capacidades para exportación de datos.
*   **Interfaz Principal de Usuario:** `PedidosASF/index.php` sirve como el punto de entrada principal para usuarios que interactúan con este módulo.

## Componentes Compartidos y Estructura del Directorio Raíz

Más allá de los módulos específicos, varios componentes y archivos en el directorio raíz del proyecto juegan roles cruciales en el ecosistema general de ASF:

*   **`api/`**: Este directorio alberga scripts PHP que probablemente sirven como una API de propósito general para operaciones comunes como búsquedas de inventario (`inventory.php`), recuperación de datos de productos (`products.php`) y finalización de procesos (`finalize.php`). También contiene un archivo `config.php`, sugiriendo un punto de configuración centralizado para estos servicios de API.

*   **Tema AdminLTE (`build/`, `dist/`, `docs/`, `plugins/`, `pages/`)**: Una porción significativa del directorio raíz está dedicada al tema AdminLTE, que forma la base de la interfaz de usuario para las aplicaciones PHP.
    *   `build/`: Contiene scripts de construcción y configuraciones para el tema AdminLTE.
    *   `dist/`: Contiene los archivos CSS, JavaScript e imágenes distribuibles (compilados) para AdminLTE.
    *   `docs/`: Incluye documentación para el tema AdminLTE.
    *   `plugins/`: Contiene varios plugins jQuery y otras bibliotecas JavaScript utilizadas por AdminLTE para proporcionar componentes de UI ricos (ej., selectores de fecha, gráficos, tablas de datos).
    *   `pages/`: Contiene páginas de ejemplo y demostraciones de elementos de UI proporcionadas por AdminLTE, que pueden haber sido utilizadas como plantillas o referencias.

*   **`lib/phpqrcode/`**: Este directorio contiene la biblioteca `phpqrcode`, utilizada por el módulo `GenerarQR` (y potencialmente otras partes del sistema) para generar códigos QR.

*   **Archivos PHP Raíz**: Muchos archivos PHP existen en el directorio raíz. Estos pueden ser categorizados ampliamente:
    *   **Puntos de Entrada y Funcionalidad Central:**
        *   `index.php`: Probablemente la página de aterrizaje principal o panel central para toda la suite de aplicaciones ASF.
        *   `login.php`: La página de login principal para autenticación de usuario.
        *   `almacen.php`, `pedidos.php`, `inventario.php`: Estos archivos parecen ser puntos de entrada principales o controladores para acceder a las funcionalidades de las secciones/módulos de Almacén, Pedidos e Inventario respectivamente.
    *   **Gestión de Productos y Vendedores:**
        *   `altaProductos.php`: Script para agregar nuevos productos.
        *   `bajaProductos.php`: Script para eliminar o desactivar productos.
        *   `products/`: Este directorio (conteniendo `get_products.php`) parece relacionado con la gestión de datos de productos.
        *   `vendedores.php`, `vendedoresq.php`: Scripts para gestionar vendedores, con `vendedoresq.php` probablemente específico para Quinagro.
    *   **Generación y Registro:**
        *   `generador.php`, `generadorq.php`: Puntos de acceso alternativos o directos para generación de códigos QR, con `generadorq.php` específico para Quinagro.
        *   `register.php`: Página de registro de usuario.
    *   **Otras Utilidades:**
        *   `production.php`: Podría ser un script relacionado con ver el estado de producción o gestionar datos relacionados con producción.
        *   `assets/`: Contiene activos estáticos compartidos como imágenes, JavaScript (ej. `moment-with-locales.min.js`) y CSS utilizados a través de diferentes scripts PHP en la raíz.
        *   `temp/qrcodes/`: Un directorio temporal para almacenar imágenes de códigos QR generados.

*   **Configuración y Logs:**
    *   `.htaccess`: Archivo de configuración del servidor Apache, probablemente para reescritura de URL o control de acceso.
    *   `composer.json`: Configuración para gestión de paquetes PHP (Composer), principalmente para herramientas de construcción AdminLTE en este contexto.
    *   `package.json`, `package-lock.json`: Configuración para gestión de paquetes Node.js (npm), probablemente para AlmacenASF y herramientas de construcción AdminLTE.
    *   `php_errors.log`: Un archivo de log para errores PHP que ocurren en los scripts de nivel raíz.

## Configuración y Ejecución del Proyecto

Esta sección proporciona instrucciones generales para configurar y ejecutar el proyecto ASF. Note que la suite ASF es una colección de módulos distintos, y la configuración específica para cada uno puede variar y podría ser compleja.

### Prerrequisitos Comunes

*   **Servidor Web:** Se requiere un servidor web como Apache o Nginx con soporte PHP para los módulos basados en PHP y scripts de nivel raíz.
*   **PHP:** Necesario para `GenerarQR`, `InventarioASF`, `PedidosASF` y varios scripts de nivel raíz. Los requisitos de versión específicos no se detallan pero deberían ser compatibles con la versión AdminLTE y bibliotecas utilizadas.
*   **Node.js & npm:** Requerido para el módulo `AlmacenASF` y potencialmente para procesos de construcción AdminLTE.
*   **Sistema de Base de Datos:**
    *   SQLite: Utilizado por `InventarioASF` (`InventarioASF/assets/db/inventory.db`).
    *   Otros RDBMS (ej., MySQL, PostgreSQL): Probablemente requerido para `PedidosASF` (configurar en `PedidosASF/conexiones/database.php`) y potencialmente otros componentes PHP.
*   **Navegador Web:** Para acceder a las interfaces web.

### Pasos Generales

1.  **Clonar el Repositorio:**
    ```bash
    git clone <url_del_repositorio>
    cd <directorio_del_repositorio>
    ```

2.  **Configuración del Servidor Web:**
    *   Configure su servidor web (Apache, Nginx, etc.) para servir el proyecto. La raíz del documento debería típicamente ser el directorio raíz del proyecto.
    *   Asegúrese de que `.htaccess` (si usa Apache) esté habilitado para características como reescritura de URL.

3.  **Configuración Específica del Módulo:**

    *   **`AlmacenASF` (Módulo Node.js):**
        1.  Navegue al directorio del módulo: `cd AlmacenASF/`
        2.  Instale dependencias: `npm install`
        3.  Inicie la aplicación: Un comando como `npm start` o `node server.js` (o `node backend.js`) típicamente se usaría. Consulte `AlmacenASF/package.json` para scripts de ejecución específicos.

    *   **Módulos PHP (`GenerarQR`, `InventarioASF`, `PedidosASF`, scripts PHP raíz):**
        1.  **Configuración de Base de Datos:**
            *   Para `PedidosASF`, cree la base de datos necesaria y configure los detalles de conexión en `PedidosASF/conexiones/database.php`.
            *   Para `InventarioASF`, la base de datos SQLite es `InventarioASF/assets/db/inventory.db`. El script `InventarioASF/assets/db/setup.php` podría usarse para la creación inicial de tablas; revise su contenido antes de ejecutar.
            *   El `api/config.php` raíz también podría necesitar configuración de base de datos.
        2.  **Permisos:** Asegúrese de que el servidor web tenga permisos de escritura para directorios donde los archivos podrían crearse o modificarse, como `temp/qrcodes/` y potencialmente ubicaciones de archivos de log.
            ```bash
            chmod -R 775 temp/qrcodes/ # Ejemplo, ajuste permisos según sea necesario
            # Asegúrese de que el usuario del servidor web (ej., www-data) tenga acceso de propiedad/escritura apropiado
            ```
        3.  **Dependencias PHP (Composer):**
            *   El `composer.json` raíz principalmente parece relacionado con herramientas de construcción AdminLTE.
            *   Los módulos PHP individuales (como `InventarioASF/websocket_server/`) podrían tener sus propios archivos `composer.json`. Navegue a estos directorios y ejecute `composer install` si es aplicable.

4.  **Accediendo a las Aplicaciones:**
    *   El punto de entrada principal para las aplicaciones PHP es probablemente el `index.php` raíz.
    *   Las interfaces específicas del módulo pueden accederse vía sus archivos `index.php` respectivos (ej., `PedidosASF/index.php`, `InventarioASF/index.php`).
    *   `AlmacenASF` ejecutará como un servicio Node.js separado, típicamente en un puerto diferente, accesible vía `http://localhost:<puerto>` (el puerto específico se definirá en su configuración del servidor).

### Nota Importante

Este README proporciona una visión general de alto nivel. La configuración detallada para cada módulo, especialmente esquemas de base de datos y datos iniciales, puede requerir inspección adicional del código. Los logs de error (`php_errors.log`, logs específicos del módulo y logs del servidor web) serán cruciales para la resolución de problemas.

## Contribuir

¡Damos la bienvenida a las contribuciones al proyecto ASF! Si desea ayudar a mejorar la suite, por favor siga estas pautas generales:

1.  **Reportar Errores o Solicitar Características:**
    *   Si encuentra un error o tiene una idea para una nueva característica, por favor abra un issue en el rastreador de issues del proyecto (si está disponible, de lo contrario comuníquese a través de canales apropiados).
    *   Proporcione una descripción clara y detallada del error (incluyendo pasos para reproducir si es posible) o la solicitud de característica.

2.  **Realizar Cambios:**
    *   **Hacer Fork del Repositorio:** Comience haciendo fork del repositorio principal ASF a su propia cuenta.
    *   **Crear una Rama:** Para cualquier nueva característica o corrección de error, cree una nueva rama en su repositorio forkeado. Esto ayuda a mantener sus cambios organizados (ej., `git checkout -b feature/nombre-de-su-caracteristica` o `git checkout -b fix/descripcion-del-issue`).
    *   **Calidad del Código:**
        *   Escriba código claro y conciso.
        *   Asegúrese de que su código esté bien comentado, especialmente en áreas complejas.
        *   Trate de mantener consistencia con el estilo del código base existente.
    *   **Confirmar Sus Cambios:** Haga commits pequeños y lógicos con mensajes claros.

3.  **Enviar Pull Requests:**
    *   Una vez que sus cambios estén completos y probados, envíe un pull request (PR) desde su rama al repositorio principal ASF.
    *   Proporcione un título claro y una descripción detallada de los cambios incluidos en su PR. Explique el problema que está resolviendo o la característica que está agregando.
    *   Referencie cualquier número de issue relevante en la descripción de su PR.

¡Gracias por su interés en contribuir a ASF!
