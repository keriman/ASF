package mx.com.ksolutions.asf_pedidos

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.*

class PedidoEditViewModel(
    private val sessionManager: SessionManager,
    private val folio: Int
) : ViewModel() {

    private val _pedidoDetalles = MutableLiveData<ApiService.DetallesPedido?>()
    val pedidoDetalles: LiveData<ApiService.DetallesPedido?> = _pedidoDetalles

    private val _isLoading = MutableLiveData<Boolean>()
    val isLoading: LiveData<Boolean> = _isLoading

    private val _isUpdating = MutableLiveData<Boolean>()
    val isUpdating: LiveData<Boolean> = _isUpdating

    private val _errorMessage = MutableLiveData<String>()
    val errorMessage: LiveData<String> = _errorMessage

    private val _updateSuccess = MutableLiveData<Boolean>()
    val updateSuccess: LiveData<Boolean> = _updateSuccess

    private val _deleteSuccess = MutableLiveData<Boolean>()
    val deleteSuccess: LiveData<Boolean> = _deleteSuccess

    init {
        loadPedidoDetalles()
    }

    fun loadPedidoDetalles() {
        viewModelScope.launch {
            _isLoading.value = true
            try {
                val username = sessionManager.getVendorName() ?: return@launch
                val response = RetrofitClient.apiService.getPedidoDetalles(folio, username)

                if (response.isSuccessful && response.body() != null) {
                    val baseResponse = response.body()!!
                    if (!baseResponse.error && baseResponse.data != null) {
                        _pedidoDetalles.value = baseResponse.data
                    } else {
                        _errorMessage.value = baseResponse.message ?: "Error al cargar detalles"
                    }
                } else {
                    _errorMessage.value = "Error al cargar detalles del pedido"
                }
            } catch (e: Exception) {
                _errorMessage.value = "Error de conexión: ${e.message}"
                e.printStackTrace()
            } finally {
                _isLoading.value = false
            }
        }
    }

    fun deleteProducto(producto: ApiService.ProductoPedido) {
        viewModelScope.launch {
            _isUpdating.value = true
            try {
                val username = sessionManager.getVendorName() ?: return@launch
                val response = RetrofitClient.apiService.deleteProducto(
                    id = producto.id,
                    folio = folio.toString(),
                    username = username
                )

                if (response.isSuccessful) {
                    val responseBody = response.body()
                    if (responseBody?.success == true) {
                        _deleteSuccess.value = true

                        // Ahora sí podemos recargar del servidor ya que filtra correctamente
                        loadPedidoDetalles()
                    } else {
                        _errorMessage.value = responseBody?.message ?: "Error al eliminar el producto"
                    }
                } else {
                    _errorMessage.value = "Error al eliminar el producto: ${response.code()}"
                }
            } catch (e: Exception) {
                _errorMessage.value = "Error de conexión: ${e.message}"
                e.printStackTrace()
            } finally {
                _isUpdating.value = false
            }
        }
    }

    fun updateProducto(producto: ApiService.ProductoPedido, nuevaCantidad: Int) {
        viewModelScope.launch {
            _isUpdating.value = true
            try {
                val username = sessionManager.getVendorName() ?: return@launch
                val response = RetrofitClient.apiService.updateProducto(
                    id = producto.id,
                    producto = producto.producto,
                    presentacion = producto.presentacion,
                    cantidad = nuevaCantidad,
                    folio = folio.toString(),
                    username = username
                )

                if (response.isSuccessful && response.body()?.success == true) {
                    // Recargar del servidor
                    loadPedidoDetalles()
                } else {
                    _errorMessage.value = response.body()?.message ?: "Error al actualizar el producto"
                }
            } catch (e: Exception) {
                _errorMessage.value = "Error de conexión: ${e.message}"
            } finally {
                _isUpdating.value = false
            }
        }
    }

    fun updatePedidoDetalles(cliente: String, destino: String, ruta: String, fechaSalida: String) {
        viewModelScope.launch {
            _isUpdating.value = true
            try {
                val username = sessionManager.getVendorName() ?: return@launch
                // Convertir fecha al formato esperado por el servidor
                val fechaFormatoServidor = convertToServerDateFormat(fechaSalida)

                val response = RetrofitClient.apiService.updatePedidoDetalles(
                    folio = folio.toString(),
                    cliente = cliente,
                    destino = destino,
                    ruta = ruta,
                    fechaSalida = fechaFormatoServidor,
                    username = username
                )

                if (response.isSuccessful && response.body()?.success == true) {
                    _updateSuccess.value = true
                } else {
                    _errorMessage.value = response.body()?.message ?: "Error al actualizar el pedido"
                }
            } catch (e: Exception) {
                _errorMessage.value = "Error de conexión: ${e.message}"
            } finally {
                _isUpdating.value = false
            }
        }
    }

    fun clearErrorMessage() {
        _errorMessage.value = ""
    }

    private fun convertToServerDateFormat(fecha: String): String {
        return try {
            // Suponiendo que el formato de entrada es "dd/MM/yyyy"
            val inputFormat = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())
            val outputFormat = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
            val date = inputFormat.parse(fecha)
            outputFormat.format(date ?: Date())
        } catch (e: Exception) {
            fecha // Retornar la fecha original si hay error
        }
    }
}