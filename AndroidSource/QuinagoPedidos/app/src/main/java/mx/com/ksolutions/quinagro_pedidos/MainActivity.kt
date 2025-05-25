package mx.com.ksolutions.quinagro_pedidos

import android.app.DatePickerDialog
import android.content.Context
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.text.Editable
import android.text.TextWatcher
import android.util.Log
import android.view.View
import android.view.inputmethod.InputMethodManager
import android.widget.*
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.appcompat.app.AppCompatDelegate
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import mx.com.ksolutions.quinagro_pedidos.databinding.ActivityMainBinding
import java.text.SimpleDateFormat
import java.util.*

class MainActivity : AppCompatActivity() {
    private lateinit var binding: ActivityMainBinding
    private lateinit var viewModel: OrderViewModel
    private val calendar = Calendar.getInstance()
    private val dateFormatter = SimpleDateFormat("yyyy/MM/dd", Locale.getDefault())

    private lateinit var sessionManager: SessionManager

    private var progressDialog: AlertDialog? = null

    // Variables para almacenar la información del vendedor
    private var vendorName: String = ""
    private var vendorUsername: String = ""

    // Lista plana de todos los productos para el spinner
    private val allProducts = mutableListOf<String>()

    // Lista filtrada para el spinner (se actualizará con la búsqueda)
    private val filteredProducts = mutableListOf<String>()

    // Adaptador para el spinner de productos
    private lateinit var productAdapter: ArrayAdapter<String>

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_YES)

        binding = ActivityMainBinding.inflate(layoutInflater)
//        setContentView(binding.root)
        setContentView(R.layout.activity_main)
        Log.d("MainActivity", "Layout inflated successfully")

        val fragmentContainer = findViewById<FrameLayout>(R.id.fragment_container)
        if (fragmentContainer == null) {
            Log.e("MainActivity", "Fragment container is NULL")
        } else {
            Log.d("MainActivity", "Fragment container found")
        }

        if (savedInstanceState == null) {
            try {
                supportFragmentManager.beginTransaction()
                    .replace(R.id.fragment_container, VentasFragment())
                    .commit()
                Log.d("MainActivity", "Initial VentasFragment added successfully")
            } catch (e: Exception) {
                Log.e("MainActivity", "Error adding initial fragment", e)
            }
        }
        supportFragmentManager.addOnBackStackChangedListener {
            Log.d("MainActivity", "Cambio en la pila de fragmentos")
            val fragments = supportFragmentManager.fragments
            fragments.forEach { fragment ->
                Log.d("MainActivity", "Fragmento en pila: ${fragment.javaClass.simpleName}")
            }
        }

        // Inicializar SessionManager primero
        sessionManager = SessionManager(this)

        // Initialize RetrofitClient
        RetrofitClient.init(this)

        // Obtener información del vendedor desde el intent o de preferencias guardadas
        val sharedPref = getSharedPreferences("QuinagroPrefs", MODE_PRIVATE)
        vendorName = intent.getStringExtra("NOMBRE_VENDEDOR") ?: sharedPref.getString("NOMBRE_VENDEDOR", "") ?: ""
        vendorUsername = intent.getStringExtra("USERNAME_VENDEDOR") ?: sharedPref.getString("USERNAME_VENDEDOR", "") ?: ""

        // Guardar información del vendedor en SessionManager
        sessionManager.setVendorInfo(vendorName, vendorUsername)

        // Asegurar que la información del vendedor está disponible para sesiones futuras
        with(sharedPref.edit()) {
            putString("NOMBRE_VENDEDOR", vendorName)
            putString("USERNAME_VENDEDOR", vendorUsername)
            apply()
        }

        // Setup UI
        binding.etVendor.setText(vendorName)
        binding.etFolio.isEnabled = false
        binding.etVendor.isEnabled = false

        // Crear e inicializar el ViewModel PRIMERO
        val factory = OrderViewModelFactory(sessionManager)
        viewModel = ViewModelProvider(this, factory)[OrderViewModel::class.java]

        // Configurar UI después de inicializar el ViewModel
        setupProductSpinner()
        binding.etSearchProduct?.let {
            setupSearchFilter()
        }
        setupObservers()
        setupUI()
        setupProductListClicks()

        // Cargar productos del servidor
        viewModel.loadProducts()

        // Intentar cargar el folio
        viewModel.loadNextFolio()
    }

    fun navigateToFragment(fragment: Fragment) {
        try {
            supportFragmentManager.beginTransaction()
                .replace(R.id.fragment_container, fragment)
                .addToBackStack(null)
                .commit()
            Log.d("MainActivity", "Navigation to ${fragment.javaClass.simpleName} successful")
        } catch (e: Exception) {
            Log.e("MainActivity", "Error navigating to fragment", e)
        }
    }

    private fun setupProductSpinner() {
        productAdapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_item,
            filteredProducts
        )
        productAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        binding.spinnerProduct.adapter = productAdapter

        // Añadir listener para mostrar/ocultar el campo de nombre de producto personalizado
        binding.spinnerProduct.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, view: View?, position: Int, id: Long) {
                val selectedProduct = parent?.getItemAtPosition(position).toString()
                if (selectedProduct == "Otros") {
                    binding.tilCustomProduct.visibility = View.VISIBLE
                } else {
                    binding.tilCustomProduct.visibility = View.GONE
                }
            }

            override fun onNothingSelected(parent: AdapterView<*>?) {
                binding.tilCustomProduct.visibility = View.GONE
            }
        }
    }

    private fun setupSearchFilter() {
        binding.etSearchProduct.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) {
                // No se requiere implementación
            }

            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {
                // No se requiere implementación
            }

            override fun afterTextChanged(s: Editable?) {
                filterProducts(s.toString())
            }
        })
    }

    private fun filterProducts(searchText: String) {
        filteredProducts.clear()

        if (searchText.isEmpty()) {
            // Si no hay texto de búsqueda, mostrar todos los productos
            filteredProducts.addAll(allProducts)
        } else {
            // Filtrar productos que contienen el texto de búsqueda (ignorando mayúsculas/minúsculas)
            val searchLower = searchText.lowercase(Locale.getDefault())

            // Filtrar productos por nombre o categoría
            allProducts.forEach { product ->
                if (product.lowercase(Locale.getDefault()).contains(searchLower)) {
                    filteredProducts.add(product)
                }
            }

            // Siempre incluir "Otros" al final
            if (!"Otros".lowercase(Locale.getDefault()).contains(searchLower)) {
                filteredProducts.add("Otros")
            }
        }

        // Notificar al adaptador que los datos han cambiado
        productAdapter.notifyDataSetChanged()
    }

    // Método para obtener solo el nombre del producto sin la categoría
    private fun extractProductName(fullProductString: String): String {
        // Si es "Otros", devolvemos el texto tal cual
        if (fullProductString == "Otros") {
            return fullProductString
        }
        // Dividir por el separador ":" y tomar la segunda parte (el nombre del producto)
        return try {
            val parts = fullProductString.split(":", limit = 2)
            if (parts.size > 1) parts[1].trim() else fullProductString
        } catch (e: Exception) {
            fullProductString // En caso de error, devolver el texto original
        }
    }

    private fun setupObservers() {
        // Observador para productos - corregido para manejar nullabilidad
        viewModel.products.observe(this) { products ->
            if (products != null) {
                updateProductLists(products)
            }
        }

        // Observador para indicador de carga de productos
        viewModel.isProductsLoading.observe(this) { isLoading ->
            // Solo mostrar/ocultar indicador si existe en el layout
            binding.progressBar?.let { progressBar ->
                progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
            }
        }

        // Observer para mensajes de error
        viewModel.errorMessage.observe(this) { errorMessage ->
            if (!errorMessage.isNullOrEmpty()) {
                // Ocultar diálogo de progreso si está visible
                hideProgressDialog()

                // Mostrar diálogo de error mejorado con información
                AlertDialog.Builder(this)
                    .setTitle("Error de conexión")
                    .setMessage("Ha ocurrido un error al comunicarse con el servidor: $errorMessage\n\nPor favor verifica tu conexión a internet e intenta nuevamente.")
                    .setPositiveButton("Reintentar") { _, _ ->
                        // Reintentar operación fallida
                        viewModel.retryLastOperation()
                    }
                    .setNegativeButton("Cancelar", null)
                    .setCancelable(false)
                    .show()

                // Después de mostrar el mensaje, limpiarlo
                viewModel.clearErrorMessage()
            }
        }

        // Observer para indicador de carga
        viewModel.isLoading.observe(this) { isLoading ->
            if (isLoading) {
                showProgressDialog("Por favor espere...", "Procesando solicitud")
            } else {
                hideProgressDialog()
            }
        }

        viewModel.folioChanged.observe(this) { changed ->
            if (changed) {
                val newFolio = viewModel.nextFolio.value
                AlertDialog.Builder(this)
                    .setTitle("Folio no disponible")
                    .setMessage("El folio asignado ya no está disponible. Se asignará el nuevo folio: $newFolio")
                    .setPositiveButton("Aceptar") { dialog, _ ->
                        binding.etFolio.setText(newFolio)
                        // Intentar guardar nuevamente con el nuevo folio
                        val vendor = binding.etVendor.text.toString()
                        val client = binding.etClient.text.toString()
                        val destination = binding.etDestination.text.toString()
                        val ruta = binding.etRuta.text.toString() // Obtener valor del campo ruta
                        val date = binding.etDate.text.toString()
                        val comments = binding.etComments.text.toString()

                        if (validateInputs(newFolio ?: "", vendor, client, destination, ruta, date)) {
                            viewModel.saveOrder(newFolio ?: "", vendor, client, destination, ruta, date, comments)
                        }
                        dialog.dismiss()
                    }
                    .setCancelable(false)
                    .show()
            }
        }

        viewModel.currentFolio.observe(this) { folio ->
            Log.d("MainActivity", "Folio actual: '$folio'")
            binding.etFolio.setText(folio)

            val hasValidFolio = !folio.isNullOrEmpty()
            binding.apply {
                etFolio.isEnabled = false
                etVendor.isEnabled = false
                etDestination.isEnabled = !hasValidFolio
                etRuta.isEnabled = !hasValidFolio // Configurar campo de ruta
                etDate.isEnabled = !hasValidFolio
                btnSaveOrder.isEnabled = !hasValidFolio
            }
        }

        viewModel.orderSaved.observe(this) { saved ->
            if (!viewModel.orderReset.value!!) {
                if (saved) {
                    Toast.makeText(this, "Pedido guardado correctamente", Toast.LENGTH_SHORT).show()
                    binding.apply {
                        // Primero ocultamos la vista de productos
                        vistaProductos.visibility = View.GONE
                        btnCerrarPedido.visibility = View.VISIBLE

                        // Deshabilitar campos del pedido
                        etDestination.isEnabled = false
                        etRuta.isEnabled = false // Deshabilitar campo de ruta
                        etDate.isEnabled = false
                        btnSaveOrder.isEnabled = false

                        // Asegurarnos que la vista de productos permanezca oculta
                        vistaProductos.post {
                            vistaProductos.visibility = View.GONE
                        }
                    }
                } else {
                    Toast.makeText(this, "Error al guardar el pedido. Verifique su sesión", Toast.LENGTH_SHORT).show()
                    binding.apply {
                        vistaProductos.visibility = View.VISIBLE
                        btnCerrarPedido.visibility = View.GONE

                        // Mantener campos habilitados en caso de error
                        etDestination.isEnabled = true
                        etRuta.isEnabled = true // Mantener habilitado campo de ruta
                        etDate.isEnabled = true
                        btnSaveOrder.isEnabled = true
                    }
                }
            }
        }

        viewModel.nextFolio.observe(this) { folio ->
            if (!folio.isNullOrEmpty()) {
                binding.etFolio.setText(folio)
            } else {
                binding.etFolio.setText("1") // Folio predeterminado si no se pudo obtener

                // Mostrar mensaje informativo solo si no hay otro error ya mostrado
                if (viewModel.errorMessage.value.isNullOrEmpty()) {
                    Toast.makeText(this, "No se pudo obtener el siguiente folio. Se utilizará el folio 1.", Toast.LENGTH_LONG).show()
                }
            }
        }

        viewModel.previewText.observe(this) { previewText ->
            binding.tvTicket.text = previewText
        }

        viewModel.productAdded.observe(this) { added ->
            if (added) {
                Toast.makeText(this, "Producto agregado correctamente", Toast.LENGTH_SHORT).show()
            } else {
                //Toast.makeText(this, "Error al agregar el producto", Toast.LENGTH_SHORT).show()
            }
        }

        // Observer para productos eliminados
        viewModel.productRemoved.observe(this) { removed ->
            if (removed) {
                Toast.makeText(this, "Producto eliminado correctamente", Toast.LENGTH_SHORT).show()
            }
        }
    }

    // Método para actualizar las listas de productos
    private fun updateProductLists(products: List<ApiService.Product>) {
        // Limpiar listas existentes
        allProducts.clear()

        // Agrupar productos por categoría (usando description como categoría)
        val productsByCategory = products.groupBy { it.description }

        // Añadir productos por categoría al formato "Categoría: Producto"
        productsByCategory.forEach { (category, productsInCategory) ->
            productsInCategory.forEach { product ->
                allProducts.add("$category: ${product.name}")
            }
        }

        // Añadir opción para productos personalizados
        allProducts.add("Otros")

        // Actualizar lista filtrada
        filteredProducts.clear()
        filteredProducts.addAll(allProducts)

        // Notificar al adaptador
        productAdapter.notifyDataSetChanged()

        // Log para depuración
        Log.d("MainActivity", "Productos cargados: ${allProducts.size - 1}") // -1 por "Otros"
    }

    // Mantener los demás métodos existentes igual que antes
    private fun setupUI() {
        binding.etDate.setOnClickListener {
            showDatePicker()
        }

        binding.btnSaveOrder.setOnClickListener {
            // Mostrar un diálogo de progreso
            val progressDialog = AlertDialog.Builder(this)
                .setTitle("Guardando pedido")
                .setMessage("Por favor espere...")
                .setCancelable(false)
                .create()

            progressDialog.show()

            val folio = binding.etFolio.text.toString()
            val vendor = binding.etVendor.text.toString()
            val client = binding.etClient.text.toString()
            val destination = binding.etDestination.text.toString()
            val ruta = binding.etRuta.text.toString() // Obtener el valor del campo ruta
            val date = binding.etDate.text.toString()
            val comments = binding.etComments.text.toString()

            // Importante: registrar el valor de los comentarios para depuración
            Log.d("MainActivity", "Guardando pedido - Comentarios: '$comments'")
            Log.d("MainActivity", "Guardando pedido - Ruta: '$ruta'") // Registrar valor de ruta

            // Obtener el nombre de usuario directamente de las variables
            val vendorUsername = this.vendorUsername
            Log.d("MainActivity", "Guardando pedido - Vendedor: '$vendor', Usuario: '$vendorUsername'")

            if (validateInputs(folio, vendor, client, destination, ruta, date)) {
                viewModel.saveOrder(folio, vendor, client, destination, ruta, date, comments)

                // Establecer un temporizador para cerrar el diálogo después de un tiempo máximo
                // en caso de que no se reciba respuesta del servidor
                Handler(Looper.getMainLooper()).postDelayed({
                    if (progressDialog.isShowing) {
                        progressDialog.dismiss()
                    }
                }, 5000) // 5 segundos máximo
            } else {
                progressDialog.dismiss()
            }
        }

        binding.btnCerrarPedido.setOnClickListener {
            Toast.makeText(this, "Pedido guardado y cerrado correctamente", Toast.LENGTH_LONG).show()
            binding.apply {
                // Mostrar la vista de productos
                vistaProductos.visibility = View.VISIBLE
                // Ocultar el botón de cerrar pedido
                btnCerrarPedido.visibility = View.GONE
            }
            clearAllFields()
            enableAllInputs()
            viewModel.resetOrder()
            viewModel.loadNextFolio()
        }

        binding.btnAddProduct.setOnClickListener {
            if (filteredProducts.isEmpty()) {
                showError("No hay productos disponibles")
                return@setOnClickListener
            }

            val selectedProductString = binding.spinnerProduct.selectedItem.toString()

            // Determinar qué producto usar basado en la selección
            val product = if (selectedProductString == "Otros") {
                val customProduct = binding.etCustomProduct.text.toString()
                if (customProduct.isEmpty()) {
                    showError("Por favor ingrese el nombre del producto")
                    return@setOnClickListener
                }
                customProduct
            } else {
                // Extraer solo el nombre del producto sin la categoría
                extractProductName(selectedProductString)
            }

            val quantity = binding.etQuantity.text.toString()
            // Obtener los comentarios del campo correspondiente
            val comments = binding.etComments.text.toString()

            if (validateProductInputs(product, quantity)) {
                // Pasar los comentarios al método addProduct
                viewModel.addProduct(product, "", quantity.toInt(), comments)
                clearProductFields()
                hideKeyboard()
            }
        }
    }

    // Mantener el resto de los métodos tal como están
    private fun setupProductListClicks() {
        binding.tvTicket.setOnClickListener {
            // Si no hay productos o todavía no se ha guardado el pedido, no hacer nada
            if (viewModel.getProductCount() <= 0 || viewModel.currentFolio.value.isNullOrEmpty()) {
                return@setOnClickListener
            }

            // Mostrar un diálogo para seleccionar el producto a eliminar
            val products = viewModel.getProductsForDisplay()
            val items = products.toTypedArray()

            AlertDialog.Builder(this)
                .setTitle("Selecciona un producto para eliminar")
                .setItems(items) { dialog, which ->
                    // Mostrar diálogo de confirmación
                    AlertDialog.Builder(this)
                        .setTitle("Eliminar producto")
                        .setMessage("¿Estás seguro que deseas eliminar ${items[which]}?")
                        .setPositiveButton("Sí") { _, _ ->
                            viewModel.removeProduct(which)
                        }
                        .setNegativeButton("No", null)
                        .show()

                    dialog.dismiss()
                }
                .setNegativeButton("Cancelar", null)
                .show()
        }
    }

    private fun hideKeyboard() {
        val imm = getSystemService(Context.INPUT_METHOD_SERVICE) as InputMethodManager
        currentFocus?.let { view ->
            imm.hideSoftInputFromWindow(view.windowToken, 0)
        }
    }

    private fun clearProductFields() {
        binding.apply {
            // Verificar que el adaptador contiene elementos antes de intentar seleccionar
            if (filteredProducts.isNotEmpty()) {
                spinnerProduct.setSelection(0)
            }
            etQuantity.text?.clear()
            etCustomProduct.text?.clear()
            etComments.text?.clear()
            etSearchProduct?.text?.clear() // Limpiar campo de búsqueda si existe
            tilCustomProduct.visibility = View.GONE
            etFolio.requestFocus()
        }
    }

    private fun clearAllFields() {
        binding.apply {
            etFolio.text?.clear()
            etClient.text?.clear()
            etDestination.text?.clear()
            etRuta.text?.clear() // Limpiar campo de ruta
            etDate.text?.clear()
            etComments.text?.clear()
            if (filteredProducts.isNotEmpty()) {
                spinnerProduct.setSelection(0)
            }
            etQuantity.text?.clear()
            etCustomProduct.text?.clear()
            etSearchProduct?.text?.clear() // Limpiar campo de búsqueda si existe
            tilCustomProduct.visibility = View.GONE
            tvTicket.text = "" // Limpiar la vista previa
        }
    }

    private fun enableAllInputs() {
        binding.apply {
            etDestination.isEnabled = true
            etRuta.isEnabled = true // Habilitar campo de ruta
            etDate.isEnabled = true
            etComments.isEnabled = true
            btnCerrarPedido.visibility = View.GONE
        }
    }

    private fun validateInputs(folio: String, vendor: String, client: String, destination: String, ruta: String, date: String): Boolean {
        return when {
            client.isEmpty() -> {
                showError("El cliente es requerido")
                false
            }
            destination.isEmpty() -> {
                showError("El destino es requerido")
                false
            }
            ruta.isEmpty() -> {
                showError("La ruta es requerida")
                false
            }
            date.isEmpty() -> {
                showError("La fecha es requerida")
                false
            }
            else -> true
        }
    }

    private fun validateProductInputs(product: String, quantity: String): Boolean {
        if (viewModel.currentFolio.value.isNullOrEmpty()) {
            showError("Primero debe guardar el pedido")
            return false
        }
        return when {
            product.isEmpty() -> {
                showError("El producto es requerido")
                false
            }
            quantity.isEmpty() || quantity.toIntOrNull() == null -> {
                showError("La cantidad debe ser un número válido")
                false
            }
            else -> true
        }
    }

    private fun showError(message: String) {
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
    }

    private fun showDatePicker() {
        val datePickerDialog = DatePickerDialog(
            this,
            R.style.DatePickerTheme,
            { _, year, month, day ->
                calendar.set(Calendar.YEAR, year)
                calendar.set(Calendar.MONTH, month)
                calendar.set(Calendar.DAY_OF_MONTH, day)
                updateDateInView()
            },
            calendar.get(Calendar.YEAR),
            calendar.get(Calendar.MONTH),
            calendar.get(Calendar.DAY_OF_MONTH)
        )

        datePickerDialog.setOnShowListener { dialog ->
            val positiveButton = (dialog as DatePickerDialog).getButton(DatePickerDialog.BUTTON_POSITIVE)
            val negativeButton = dialog.getButton(DatePickerDialog.BUTTON_NEGATIVE)

            positiveButton.text = "Aceptar"
            negativeButton.text = "Cancelar"
        }

        datePickerDialog.show()
    }

    private fun updateDateInView() {
        binding.etDate.setText(dateFormatter.format(calendar.time))
    }

    private fun showProgressDialog(message: String, title: String = "Cargando") {
        hideProgressDialog() // Asegurarse de que no haya uno visible

        progressDialog = AlertDialog.Builder(this)
            .setTitle(title)
            .setMessage(message)
            .setCancelable(false)
            .create()

        progressDialog?.show()
    }

    private fun hideProgressDialog() {
        progressDialog?.dismiss()
        progressDialog = null
    }

    // No olvides cerrar el diálogo en onDestroy para evitar memory leaks
    override fun onDestroy() {
        super.onDestroy()
        hideProgressDialog()
    }
}
