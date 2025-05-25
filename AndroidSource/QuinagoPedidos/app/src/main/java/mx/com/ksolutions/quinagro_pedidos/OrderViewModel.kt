package mx.com.ksolutions.quinagro_pedidos

import android.util.Log
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch
import java.net.UnknownHostException

data class ProductPreview(
    val product: String,
    val quantity: Int,
    val comments: String = ""
)

class OrderViewModel(private val sessionManager: SessionManager) : ViewModel() {

    private val _orderReset = MutableLiveData<Boolean>()
    val orderReset: LiveData<Boolean> = _orderReset

    private val _nextFolio = MutableLiveData<String>()
    val nextFolio: LiveData<String> = _nextFolio

    private val _folioChanged = MutableLiveData<Boolean>()
    val folioChanged: LiveData<Boolean> = _folioChanged

    private val _errorMessage = MutableLiveData<String>()
    val errorMessage: LiveData<String> = _errorMessage

    private val _isLoading = MutableLiveData<Boolean>()
    val isLoading: LiveData<Boolean> = _isLoading

    private var lastOperation: (() -> Unit)? = null

    init {
        _orderReset.value = false
        _folioChanged.value = false
        _isLoading.value = false
    }

    private val repository = OrderRepository()

    private val _orderSaved = MutableLiveData<Boolean>()
    val orderSaved: LiveData<Boolean> = _orderSaved

    private val _productAdded = MutableLiveData<Boolean>()
    val productAdded: LiveData<Boolean> = _productAdded

    private val _productRemoved = MutableLiveData<Boolean>()
    val productRemoved: LiveData<Boolean> = _productRemoved

    private val _currentFolio = MutableLiveData<String>()
    val currentFolio: LiveData<String> = _currentFolio

    private val _previewText = MutableLiveData<String>()
    val previewText: LiveData<String> = _previewText

    private val productList = mutableListOf<ProductPreview>()

    // Propiedades para productos - Corregido para usar el tipo correcto
    private val _products = MutableLiveData<List<ApiService.Product>>()
    val products: LiveData<List<ApiService.Product>> = _products

    private val _isProductsLoading = MutableLiveData<Boolean>()
    val isProductsLoading: LiveData<Boolean> = _isProductsLoading

    private val productRepository = ProductRepository()

    // Método para cargar productos actualizado
    fun loadProducts() {
        _isProductsLoading.value = true
        lastOperation = { loadProducts() }

        viewModelScope.launch {
            try {
                val response = productRepository.getProducts()

                if (response.isSuccessful && response.body() != null) {
                    val productsResponse = response.body()!!
                    if (!productsResponse.error && productsResponse.products != null) {
                        _products.postValue(productsResponse.products)
                        Log.d("OrderViewModel", "Productos cargados: ${productsResponse.products.size}")
                    } else {
                        _errorMessage.postValue(productsResponse.message ?: "Error al cargar productos")
                    }
                } else {
                    Log.e("OrderViewModel", "Error al cargar productos: ${response.errorBody()?.string()}")
                    _errorMessage.postValue("No se pudieron cargar los productos")
                }
            } catch (e: Exception) {
                Log.e("OrderViewModel", "Excepción al cargar productos: ${e.message}")
                _errorMessage.postValue("Error: ${e.message ?: "Desconocido"}")
            } finally {
                _isProductsLoading.postValue(false)
            }
        }
    }

    fun resetOrder() {
        _orderReset.value = true
        _currentFolio.value = ""
        _orderSaved.value = false
        _productAdded.value = false
        _folioChanged.value = false
        productList.clear()
        _previewText.value = ""
        _orderReset.value = false
    }

    // Variables de estado para los campos del pedido
    private val _client = MutableLiveData<String>()
    private val _destination = MutableLiveData<String>()
    private val _ruta = MutableLiveData<String>()
    private val _date = MutableLiveData<String>()

    fun saveOrder(folio: String, vendor: String, client: String, destination: String, ruta: String, date: String, comments: String) {
        val vendorUsername = sessionManager.getVendorUsername()
        if (vendorUsername.isNullOrEmpty()) {
            _errorMessage.value = "No se ha seleccionado un vendedor válido"
            return
        }

        _client.value = client
        _destination.value = destination
        _ruta.value = ruta
        _date.value = date

        _isLoading.value = true
        lastOperation = { saveOrder(folio, vendor, client, destination, ruta, date, comments) }

        viewModelScope.launch {
            try {
                if (currentFolio.value.isNullOrEmpty()) {
                    val response = repository.getNextFolio()
                    if (!response.isSuccessful || response.body() == null) {
                        _errorMessage.postValue("No se pudo verificar el folio")
                        _isLoading.postValue(false)
                        return@launch
                    }

                    val nextFolioValue = response.body()?.nextFolio?.toString() ?: "1"
                    if (folio != nextFolioValue) {
                        _nextFolio.postValue(nextFolioValue)
                        _folioChanged.postValue(true)
                        _isLoading.postValue(false)
                        return@launch
                    }
                }

                val response = repository.saveOrder(folio, vendor, client, destination, ruta, date, comments)

                if (response.isSuccessful && response.body() != null) {
                    val saveResponse = response.body()!!

                    if (saveResponse.status == "success") {
                        _orderSaved.postValue(true)
                        _currentFolio.postValue(folio)
                        updatePreview()
                    } else {
                        _orderSaved.postValue(false)
                        _errorMessage.postValue(saveResponse.message ?: "Error desconocido al guardar el pedido")
                    }
                } else {
                    _orderSaved.postValue(false)
                    _errorMessage.postValue("Error al guardar pedido (${response.code()}): ${response.errorBody()?.string() ?: "Sin detalles"}")
                }
            } catch (e: Exception) {
                _errorMessage.postValue(e.message ?: "Error desconocido al guardar el pedido")
                _orderSaved.postValue(false)
            } finally {
                _isLoading.postValue(false)
            }
        }
    }

    fun addProduct(product: String, presentation: String, quantity: Int, comments: String) {
        _isLoading.value = true
        lastOperation = { addProduct(product, presentation, quantity, comments) }

        viewModelScope.launch {
            try {
                val currentFolio = _currentFolio.value
                if (currentFolio.isNullOrEmpty()) {
                    _productAdded.value = false
                    _isLoading.postValue(false)
                    _errorMessage.postValue("Primero debe guardar el pedido")
                    return@launch
                }

                val response = repository.addProduct(currentFolio, product, presentation, quantity, comments)
                if (response.isSuccessful) {
                    _productAdded.value = true
                    productList.add(ProductPreview(product, quantity, comments))
                    updatePreviewText()
                } else {
                    _productAdded.value = false
                    _errorMessage.postValue("Error al agregar producto (${response.code()}): ${response.errorBody()?.string() ?: "Sin detalles"}")
                }
            } catch (e: UnknownHostException) {
                Log.e("OrderViewModel", "Error de conexión: ${e.message}")
                _productAdded.value = false
                _errorMessage.postValue("No hay conexión a internet o el servidor no está disponible")
            } catch (e: Exception) {
                Log.e("OrderViewModel", "Error al agregar producto: ${e.message}")
                _productAdded.value = false
                _errorMessage.postValue("Error: ${e.message ?: "Desconocido"}")
            } finally {
                _isLoading.postValue(false)
            }
        }
    }

    fun removeProduct(index: Int) {
        _isLoading.value = true

        viewModelScope.launch {
            try {
                if (index < 0 || index >= productList.size) {
                    _productRemoved.value = false
                    _isLoading.postValue(false)
                    _errorMessage.postValue("Índice de producto no válido")
                    return@launch
                }

                val currentFolio = _currentFolio.value
                if (currentFolio.isNullOrEmpty()) {
                    _productRemoved.value = false
                    _isLoading.postValue(false)
                    _errorMessage.postValue("No hay un pedido activo")
                    return@launch
                }

                val productToRemove = productList[index]
                lastOperation = { removeProduct(index) }

                val response = repository.removeProduct(
                    folio = currentFolio,
                    product = productToRemove.product,
                    quantity = productToRemove.quantity.toString()
                )

                if (response.isSuccessful) {
                    productList.removeAt(index)
                    updatePreviewText()
                    _productRemoved.value = true
                } else {
                    Log.e("OrderViewModel", "Error al eliminar producto en el servidor: ${response.code()}")
                    _productRemoved.value = false
                    _errorMessage.postValue("Error al eliminar producto (${response.code()}): ${response.errorBody()?.string() ?: "Sin detalles"}")
                }
            } catch (e: UnknownHostException) {
                Log.e("OrderViewModel", "Error de conexión: ${e.message}")
                _productRemoved.value = false
                _errorMessage.postValue("No hay conexión a internet o el servidor no está disponible")
            } catch (e: Exception) {
                Log.e("OrderViewModel", "Error al eliminar producto: ${e.message}")
                _productRemoved.value = false
                _errorMessage.postValue("Error: ${e.message ?: "Desconocido"}")
            } finally {
                _isLoading.postValue(false)
            }
        }
    }

    private fun updatePreviewText() {
        val preview = buildString {
            appendLine("PRODUCTOS AGREGADOS:")
            appendLine("-------------------")
            if (productList.isEmpty()) {
                appendLine("No hay productos agregados")
            } else {
                productList.forEachIndexed { index, product ->
                    appendLine("${index + 1}. ${product.product}")
                    appendLine("   -Cantidad: ${product.quantity}")
                    if (product.comments.isNotEmpty()) {
                        appendLine("   -Comentarios: ${product.comments}")
                    }
                    appendLine()
                }
                appendLine("-------------------")
                appendLine("Toca aquí para eliminar productos")
            }
        }
        _previewText.value = preview
    }

    fun setCurrentFolio(folio: String) {
        _currentFolio.value = folio
    }

    fun getProductCount(): Int {
        return productList.size
    }

    fun getProductsForDisplay(): List<String> {
        return productList.mapIndexed { index, product ->
            "${index + 1}. ${product.product} (${product.quantity})"
        }
    }

    fun loadNextFolio() {
        _isLoading.value = true
        lastOperation = { loadNextFolio() }

        viewModelScope.launch {
            try {
                ensureAuthenticated()

                try {
                    val response = repository.getNextFolio()

                    if (response.isSuccessful && response.body() != null) {
                        Log.d("OrderViewModel", "Folio obtenido exitosamente: ${response.body()?.nextFolio}")
                        _nextFolio.postValue(response.body()?.nextFolio?.toString() ?: "1")
                    } else {
                        Log.e("OrderViewModel", "Error al obtener folio: ${response.code()}, usando valor por defecto")
                        _nextFolio.postValue("1")
                    }
                } catch (e: Exception) {
                    Log.e("OrderViewModel", "Excepción al obtener folio: ${e.message}")
                    _nextFolio.postValue("1")
                }
            } catch (e: Exception) {
                Log.e("OrderViewModel", "Error general: ${e.message}")
                _nextFolio.postValue("1")
            } finally {
                _isLoading.postValue(false)
            }
        }
    }

    private suspend fun ensureAuthenticated(): Boolean {
        val vendorUsername = sessionManager.getVendorUsername()

        try {
            val username = vendorUsername ?: "Vendedor"

            Log.d("OrderViewModel", "Intentando autenticar con usuario: $username")
            val response = repository.login(username, username)

            if (response.isSuccessful) {
                Log.d("OrderViewModel", "Autenticación exitosa: ${response.body()}")
                response.body()?.let { loginResponse ->
                    if (loginResponse.success && !loginResponse.error) {
                        sessionManager.setLoggedIn(true)
                        sessionManager.setUserType(loginResponse.tipo ?: "oficina")
                        return true
                    } else {
                        Log.d("OrderViewModel", "Credenciales incorrectas: ${loginResponse.message}")
                        sessionManager.setLoggedIn(true)
                        sessionManager.setUserType("oficina")
                        return true
                    }
                } ?: run {
                    Log.d("OrderViewModel", "Respuesta vacía")
                    sessionManager.setLoggedIn(true)
                    sessionManager.setUserType("oficina")
                    return true
                }
            } else {
                Log.d("OrderViewModel", "Error en autenticación: ${response.code()}")
                sessionManager.setLoggedIn(true)
                sessionManager.setUserType("oficina")
                return true
            }
        } catch (e: Exception) {
            Log.d("OrderViewModel", "Excepción en autenticación: ${e.message}")
            sessionManager.setLoggedIn(true)
            sessionManager.setUserType("oficina")
            return true
        }
    }

    fun clearErrorMessage() {
        _errorMessage.value = ""
    }

    fun retryLastOperation() {
        lastOperation?.invoke()
    }

    // Método para filtrar productos por categoría - corregido
    fun getProductsByCategory(category: String): List<String> {
        val productList = _products.value ?: emptyList()
        return productList
            .filter { it.description == category }
            .map { it.name }
            .sorted()
    }

    // Método para obtener todos los productos - corregido
    fun getAllProductNames(): List<String> {
        val productList = _products.value ?: emptyList()
        return productList
            .map { it.name }
            .sorted()
    }

    private fun updatePreview() {
        val sb = StringBuilder()

        sb.appendLine("FOLIO: ${_currentFolio.value ?: "N/A"}")
        sb.appendLine("VENDEDOR: ${sessionManager.getVendorName() ?: ""}")
        sb.appendLine("CLIENTE: ${_client.value ?: ""}")
        sb.appendLine("DESTINO: ${_destination.value ?: ""}")
        sb.appendLine("RUTA: ${_ruta.value ?: ""}")
        sb.appendLine("FECHA: ${_date.value ?: ""}")
        sb.appendLine("")

        updatePreviewText()
    }
}

class OrderViewModelFactory(private val sessionManager: SessionManager) : ViewModelProvider.Factory {
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(OrderViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return OrderViewModel(sessionManager) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}