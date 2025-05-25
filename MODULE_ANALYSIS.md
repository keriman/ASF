## Module Analysis Report

This report details the functionality and estimated development time for each identified module in the repository, excluding Android apps.

**Important Note on Development Time Estimates**: The `git log` history available for most modules did not reflect their actual development timelines. Most paths only showed a single, recent commit made by a bot for README file generation, which occurred after the modules were already developed. Therefore, unless otherwise specified (e.g., for third-party library integration), the development time estimates provided below are based on an assessment of the module's features, complexity, and lines of code, representing an educated guess of the effort involved.

---

### 1. `AlmacenASF` Module

*   **Functionality**: `AlmacenASF` is a web application designed for managing fertilizer factory orders.
    *   It features a Node.js/Express backend with MongoDB for data storage, providing REST APIs for order management (fetching, confirming/deconfirming).
    *   WebSockets are used for real-time communication, enabling live updates for order processing.
    *   It includes an automated feature to delete old confirmed orders.
    *   A separate Node.js/Express server (`server.js`) serves the static HTML frontend files (`warehouse.html` and `production.html`).
    *   The frontend (`production.js` for `production.html`) displays incoming orders in real-time, allows order confirmation, and uses DataTables, SweetAlert, and browser notifications.
    *   Another frontend component (`warehouse.js` for `warehouse.html`) allows users to input and send new orders via WebSockets.
    *   Dependencies include Express, Mongoose, and WS (WebSockets).
*   **Estimated Development Time**: Several weeks. (Git log uninformative).

---

### 2. `GenerarQR` Module

*   **Functionality**: A PHP-based application for generating and managing QR codes for products.
    *   Lists products from a database.
    *   Generates QR codes (encoding product ID, name, barcode in JSON) using the `phpqrcode` library and saves them as PNG files.
    *   Displays generated QR codes with product details.
    *   Provides print-friendly layouts for QR codes.
    *   Includes indications of batch printing capabilities.
*   **Estimated Development Time**: A few days to a couple of weeks. (Git log uninformative).

---

### 3. `InventarioASF` Module

*   **Functionality**: A comprehensive PHP-based inventory management system.
    *   **Main UI (`index.php`)**: View products (categorized), stock levels, history; admin functions for product management; stock updates; search/filtering. Uses SQLite via `InventorySystem.php` (or `InventorySystem2.php`).
    *   **Core Logic (`process/InventorySystem.php`)**: SQLite-based backend for product CRUD, stock updates, and history logging.
    *   **Barcode Scanning (`barcode_scanner.php`)**: UI for inventory updates via barcode scanning (or manual entry) in batches; allows assigning unknown barcodes.
    *   **WebSocket Server (`websocket_server/websocket_server.php`)**: Ratchet WebSocket server (port 1337) for real-time communication, likely with external devices (e.g., ESP32) for actions like saving batches.
    *   **Authentication (`auth.php`)**: Manages user login/logout against a MySQL `usuarios` table, with role-based access (admin/user).
*   **Estimated Development Time**: 4 to 8 weeks. (Git log uninformative).

---

### 4. `PedidosASF` Module

*   **Functionality**: An extensive PHP-based sales order management system for an agricultural company, using a MySQL database (`agrosant_pedidos`).
    *   **Role-Based Access**: Different interfaces/capabilities for 'almacen' (warehouse), 'gerencia' (management), 'oficina' (office), and 'admin' users.
    *   **APIs (`API_ASF`, `API_Quinagro`)**: Provide endpoints (likely for mobile apps or other clients) to fetch products (from different tables, `products` and `products_quinagro`, suggesting distinct product lines) and save orders. `app_save_order.php` is robust, with dynamic SQL building and PDO/MySQLi fallback.
    *   **Almacen (Warehouse) Section**: Dashboard for warehouse users to view orders (filtered, color-coded by status/vendor/date), update statuses, and export to Excel. Uses deprecated `mysql_*` functions.
    *   **Pedidos (Order Management) Section**: Allows office/warehouse users to create and update orders, with status changes logged. Uses deprecated `mysql_*` functions.
    *   **Admin Section**: Interface for managing seller information. Uses deprecated `mysql_*` functions.
    *   The codebase shows a mix of modern PDO and deprecated `mysql_*` database connection methods, indicating potentially varied stages or authors of development.
*   **Estimated Development Time**: 6 to 12 weeks. (Git log uninformative).

---

### 5. `api/` Module (Root Level)

*   **Functionality**: Provides a JSON API to interact with the inventory system (specifically `InventarioASF/process/InventorySystem2.php`).
    *   **Configuration (`api/config.php`)**: Sets JSON headers, CORS, includes `InventorySystem2.php`, and provides helper functions for responses.
    *   **Endpoints**:
        *   `api/products.php` (GET): Lists all products.
        *   `api/inventory.php` (POST): Updates stock for a product.
        *   `api/finalize.php` (POST): Finalizes an inventory "session" by processing a batch of products, using database transactions for atomicity.
*   **Estimated Development Time**: A few days to 1 week (assuming familiarity with the underlying `InventorySystem` class). (Git log uninformative).

---

### 6. `lib/phpqrcode/` Library

*   **Functionality**: A standard third-party PHP library for generating QR Code 2-D barcodes. It's based on `libqrencode`.
*   **Standard vs. Customized**: Appears to be an unmodified version of the library.
*   **Integration Time**: The effort to integrate such a library is typically **less than a day**. The exact date of integration is not available from the git logs. No development time for the library itself is attributed to this project.

---

### 7. Root PHP Files

*   **Files**: `almacen.php`, `altaProductos.php`, `bajaProductos.php`, `generador.php`, `generadorq.php`, `index.php`, `inventario.php`, `login.php`, `pedidos.php`, `production.php`, `register.php`, `vendedores.php`, `vendedoresq.php`.
*   **Functionality**: These files primarily act as **entry points or simple controllers** for the web application. They use a templating pattern, including common UI elements (headers, navigation from `pages/`) and specific content scripts from the `pages/` directory. `login.php` and `register.php` handle authentication. Complex logic is delegated.
*   **Estimated Development Time**: Minimal. Collectively, perhaps **2-4 days** for their setup as template assemblers. (Git log uninformative).

---

### 8. UI Framework (AdminLTE and associated plugins)

*   **Components**: Located in `build/`, `dist/`, `docs/`, and `plugins/`.
*   **Functionality**: AdminLTE is the primary UI framework, providing the visual theme, page structure (header, sidebar, content), responsive design, and a rich ecosystem of UI components and integrated third-party plugins (e.g., Bootstrap, FontAwesome, DataTables, Chart.js, Moment.js, Select2). This framework is used by the root PHP files and `pages/` scripts.
*   **Integration/Customization Time**: The effort to download and integrate a standard theme like AdminLTE might take **a few days** for basic setup. The git logs do not show evidence of specific customizations to the AdminLTE core files themselves. The primary effort is in *using* the framework, which is covered in the estimates for other modules. (Git log uninformative regarding integration date or specific customizations).

---

This concludes the analysis based on the available information.
