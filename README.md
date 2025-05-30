# ASF - Agro Stock Flow

## General Overview

ASF (Agro Stock Flow) is a comprehensive suite of web applications designed to streamline and manage warehouse operations, particularly tailored for agricultural product management. It provides a range of tools to handle various aspects of warehouse management, from inventory control to order processing.

The ASF project consists of the following key modules:

*   **AlmacenASF:** The core warehouse management module, responsible for overseeing storage, organization, and movement of goods within the warehouse.
*   **GenerarQR:** A utility for generating QR codes, used for item identification, tracking, or linking to product information.
*   **InventarioASF:** Dedicated to inventory management, this module handles tracking stock levels, managing inventory data, and supporting stocktaking processes.
*   **PedidosASF:** Focused on order management, this module facilitates the processing of customer orders, from reception to fulfillment. It includes a specific integration for "Quinagro".

The applications within the ASF suite are primarily developed using **PHP** for server-side logic and **Node.js** for certain functionalities or services (specifically AlmacenASF). The user interfaces are built using the **AdminLTE** theme, providing a consistent and responsive user experience.

## Module Descriptions

### AlmacenASF

**AlmacenASF** is the central warehouse management system within the ASF suite. It is a **Node.js** application designed to provide comprehensive control over warehouse operations.

Key features and aspects of AlmacenASF include:

*   **Core Warehouse Management:** Manages the storage, organization, and movement of goods (as indicated by `warehouse.js`).
*   **Production Management:** Includes functionalities for tracking or managing production processes (suggested by `production.html` and `assets/js/production.js`).
*   **Real-time Backend:** Utilizes `backend.js` and `server.js` for handling real-time data processing and communication for warehouse activities.
*   **User Interface:** Provides a web-based interface (likely `production.html` and `warehouse.html`) for users to interact with the system.

### GenerarQR

**GenerarQR** is a dedicated module for generating and printing QR codes within the ASF suite. It is a **PHP** application that leverages the `phpqrcode` library (located in the project's root `lib/` directory) to create QR codes.

Key functionalities and scripts include:

*   **QR Code Generation:** Scripts like `generar_qr.php`, `generar_qr_simple.php`, and `generar_qr_simple_q.php` are responsible for creating QR codes based on input data. The `_q` variants likely pertain to the "Quinagro" integration.
*   **QR Code Printing:** Scripts such as `imprimir.php`, `imprimir_simple.php`, and `imprimir_todos.php` (and their `_q` variants) facilitate the printing of the generated QR codes.
*   **User Interface:** `index.php` and `indexQ.php` serve as web interfaces for users to trigger QR code generation and printing.

### InventarioASF

**InventarioASF** is the inventory management module of the ASF suite. This **PHP** application provides tools for tracking stock, managing product data, and streamlining inventory operations. It uses an SQLite database (`InventarioASF/assets/db/inventory.db`) for data storage.

Key features and components include:

*   **Core Inventory Logic:** Handled by scripts like `InventarioASF/process/InventorySystem.php`, which contains the main business logic for inventory operations.
*   **Barcode Integration:** Extensive barcode functionalities are available through scripts such as `barcode_scanner.php` (for scanning), `assign_barcode.php` (for associating barcodes with products), and `manage_barcodes.php` (for overall barcode management).
*   **User Authentication:** Secure access is managed via `InventarioASF/auth.php` and the `InventarioASF/login/` directory.
*   **Batch Operations:** Supports processing of inventory in batches, with scripts like `process_batch.php` for handling batch updates and `batch_history.php` for tracking batch activities.
*   **Product Management:** Features like `import-products.php` allow for importing product data.
*   **Real-time Updates:** Incorporates a `InventarioASF/websocket_server/websocket_server.php` component, indicating the use of WebSockets for real-time communication of inventory changes or updates.
*   **User Interface:** The main user interaction point is `InventarioASF/index.php`.

### PedidosASF

**PedidosASF** is the order management module of the ASF suite. This **PHP** application is designed to handle customer orders, from creation through to fulfillment, and includes specialized integrations.

Key features and components include:

*   **Order and Product Management Core:** The `PedidosASF/pedidos/` directory, particularly `PedidosASF/pedidos/alta/alta_pedidos.php` and `PedidosASF/pedidos/alta/alta_productos.php`, forms the core for creating and managing orders and products.
*   **API Endpoints:**
    *   `PedidosASF/API_ASF/`: Provides a range of API functionalities including product creation (`app_alta_productos.php`), order saving (`app_save_order.php`), user login (`app_login.php`), and retrieving sales data (`get_ventas.php`).
    *   `PedidosASF/API_Quinagro/`: A parallel API structure specifically for "Quinagro", mirroring many of the ASF API functionalities. This suggests Quinagro might be a distinct client or a specialized operational mode requiring its own API set.
*   **Administration Interface:** The `PedidosASF/admin/` directory likely provides administrative tools, including vendor management (`vendedores.php`).
*   **Warehouse Operations Interface:** The `PedidosASF/almacen/` directory offers functionalities tailored for warehouse staff, including order processing and product management, with distinct versions for ASF and Quinagro (e.g., `procesar_vendedores.php` vs `procesar_vendedoresq.php`).
*   **Database Connectivity:** Database connections are managed centrally via `PedidosASF/conexiones/database.php`.
*   **User Management and Authentication:** User access and login are handled by scripts in the `PedidosASF/usuarios/` directory and various root login files within `PedidosASF/` (e.g., `login.php`, `login2.php`).
*   **Reporting/Export:** Features like `exportar_excel.php` in `PedidosASF/almacen/` and `PedidosASF/gerencia/` indicate capabilities for data export.
*   **Main User Interface:** `PedidosASF/index.php` serves as the primary entry point for users interacting with this module.

## Shared Components and Root Directory Structure

Beyond the specific modules, several components and files in the root directory of the project play crucial roles in the overall ASF ecosystem:

*   **`api/`**: This directory houses PHP scripts that likely serve as a general-purpose API for common operations such as inventory lookups (`inventory.php`), product data retrieval (`products.php`), and finalizing processes (`finalize.php`). It also contains a `config.php` file, suggesting a centralized configuration point for these API services.

*   **AdminLTE Theme (`build/`, `dist/`, `docs/`, `plugins/`, `pages/`)**: A significant portion of the root directory is dedicated to the AdminLTE theme, which forms the basis of the user interface for the PHP applications.
    *   `build/`: Contains build scripts and configurations for the AdminLTE theme.
    *   `dist/`: Holds the distributable (compiled) CSS, JavaScript, and image files for AdminLTE.
    *   `docs/`: Includes documentation for the AdminLTE theme.
    *   `plugins/`: Contains various jQuery plugins and other JavaScript libraries used by AdminLTE to provide rich UI components (e.g., date pickers, charts, data tables).
    *   `pages/`: Contains example pages and UI element demonstrations provided by AdminLTE, which may have been used as templates or references.

*   **`lib/phpqrcode/`**: This directory contains the `phpqrcode` library, used by the `GenerarQR` module (and potentially other parts of the system) for generating QR codes.

*   **Root PHP Files**: Many PHP files exist in the root directory. These can be broadly categorized:
    *   **Entry Points & Core Functionality:**
        *   `index.php`: Likely the main landing page or central dashboard for the entire ASF application suite.
        *   `login.php`: The primary login page for user authentication.
        *   `almacen.php`, `pedidos.php`, `inventario.php`: These files appear to be main entry points or controllers for accessing the functionalities of the Almacen, Pedidos, and Inventario sections/modules respectively.
    *   **Product and Vendor Management:**
        *   `altaProductos.php`: Script for adding new products.
        *   `bajaProductos.php`: Script for removing or deactivating products.
        *   `products/`: This directory (containing `get_products.php`) seems related to product data management.
        *   `vendedores.php`, `vendedoresq.php`: Scripts for managing vendors, with `vendedoresq.php` likely specific to Quinagro.
    *   **Generation & Registration:**
        *   `generador.php`, `generadorq.php`: Alternative or direct access points for QR code generation, with `generadorq.php` specific to Quinagro.
        *   `register.php`: User registration page.
    *   **Other Utilities:**
        *   `production.php`: Could be a script related to viewing production status or managing production-related data.
        *   `assets/`: Contains shared static assets like images, JavaScript (e.g. `moment-with-locales.min.js`), and CSS used across different PHP scripts in the root.
        *   `temp/qrcodes/`: A temporary directory for storing generated QR code images.

*   **Configuration & Logs:**
    *   `.htaccess`: Apache server configuration file, likely for URL rewriting or access control.
    *   `composer.json`: Configuration for PHP (Composer) package management, primarily for AdminLTE build tools in this context.
    *   `package.json`, `package-lock.json`: Configuration for Node.js (npm) package management, likely for AlmacenASF and AdminLTE build tools.
    *   `php_errors.log`: A log file for PHP errors occurring in the root-level scripts.

## Setup and Running the Project

This section provides general instructions for setting up and running the ASF project. Note that the ASF suite is a collection of distinct modules, and the specific setup for each can vary and might be complex.

### Common Prerequisites

*   **Web Server:** A web server like Apache or Nginx with PHP support is required for the PHP-based modules and root scripts.
*   **PHP:** Necessary for `GenerarQR`, `InventarioASF`, `PedidosASF`, and various root-level scripts. Specific version requirements are not detailed but should be compatible with the AdminLTE version and used libraries.
*   **Node.js & npm:** Required for the `AlmacenASF` module and potentially for AdminLTE build processes.
*   **Database System:**
    *   SQLite: Used by `InventarioASF` (`InventarioASF/assets/db/inventory.db`).
    *   Other RDBMS (e.g., MySQL, PostgreSQL): Likely required for `PedidosASF` (configure in `PedidosASF/conexiones/database.php`) and potentially other PHP components.
*   **Web Browser:** For accessing the web interfaces.

### General Steps

1.  **Clone the Repository:**
    ```bash
    git clone <repository_url>
    cd <repository_directory>
    ```

2.  **Web Server Configuration:**
    *   Configure your web server (Apache, Nginx, etc.) to serve the project. The document root should typically be the project's root directory.
    *   Ensure `.htaccess` (if using Apache) is enabled for features like URL rewriting.

3.  **Module-Specific Setup:**

    *   **`AlmacenASF` (Node.js Module):**
        1.  Navigate to the module's directory: `cd AlmacenASF/`
        2.  Install dependencies: `npm install`
        3.  Start the application: A command like `npm start` or `node server.js` (or `node backend.js`) would typically be used. Refer to `AlmacenASF/package.json` for specific run scripts.

    *   **PHP Modules (`GenerarQR`, `InventarioASF`, `PedidosASF`, root PHP scripts):**
        1.  **Database Configuration:**
            *   For `PedidosASF`, create the necessary database and configure connection details in `PedidosASF/conexiones/database.php`.
            *   For `InventarioASF`, the SQLite database is `InventarioASF/assets/db/inventory.db`. The script `InventarioASF/assets/db/setup.php` might be used for initial table creation; review its contents before running.
            *   The root `api/config.php` might also need database configuration.
        2.  **Permissions:** Ensure that the web server has write permissions for directories where files might be created or modified, such as `temp/qrcodes/` and potentially log file locations.
            ```bash
            chmod -R 775 temp/qrcodes/ # Example, adjust permissions as necessary
            # Ensure the web server user (e.g., www-data) has appropriate ownership/write access
            ```
        3.  **PHP Dependencies (Composer):**
            *   The root `composer.json` primarily seems related to AdminLTE build tools.
            *   Individual PHP modules (like `InventarioASF/websocket_server/`) might have their own `composer.json` files. Navigate to these directories and run `composer install` if applicable.

4.  **Accessing the Applications:**
    *   The main entry point for the PHP applications is likely the root `index.php`.
    *   Module-specific interfaces can be accessed via their respective `index.php` files (e.g., `PedidosASF/index.php`, `InventarioASF/index.php`).
    *   `AlmacenASF` will run as a separate Node.js service, typically on a different port, accessible via `http://localhost:<port>` (the specific port will be defined in its server configuration).

### Important Note

This README provides a high-level overview. Detailed setup for each module, especially database schemas and initial data, may require further code inspection. Error logs (`php_errors.log`, module-specific logs, and web server logs) will be crucial for troubleshooting.

## Contributing

We welcome contributions to the ASF project! If you'd like to help improve the suite, please follow these general guidelines:

1.  **Reporting Bugs or Requesting Features:**
    *   If you find a bug or have an idea for a new feature, please open an issue on the project's issue tracker (if available, otherwise communicate through appropriate channels).
    *   Provide a clear and detailed description of the bug (including steps to reproduce if possible) or the feature request.

2.  **Making Changes:**
    *   **Fork the Repository:** Start by forking the main ASF repository to your own account.
    *   **Create a Branch:** For any new feature or bug fix, create a new branch in your forked repository. This helps keep your changes organized (e.g., `git checkout -b feature/your-feature-name` or `git checkout -b fix/issue-description`).
    *   **Code Quality:**
        *   Write clear and concise code.
        *   Ensure your code is well-commented, especially in complex areas.
        *   Try to maintain consistency with the existing codebase style.
    *   **Commit Your Changes:** Make small, logical commits with clear messages.

3.  **Submitting Pull Requests:**
    *   Once your changes are complete and tested, submit a pull request (PR) from your branch to the main ASF repository.
    *   Provide a clear title and a detailed description of the changes included in your PR. Explain the problem you're solving or the feature you're adding.
    *   Reference any relevant issue numbers in your PR description.

Thank you for your interest in contributing to ASF!
