package mx.com.ksolutions.quinagro_pedidos

import android.util.Log
import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch
import java.net.UnknownHostException

class PedidoDetallesViewModel(
    private val sessionManager: SessionManager
) : ViewModel() {
    private val TAG = "PedidoDetallesViewModel"

    // LiveData para manejar el estado de carga
    private val _isLoading = MutableLiveData<Boolean>()
    val isLoading: LiveData<Boolean> = _isLoading

    // LiveData para manejar errores
    private val _errorMessage = MutableLiveData<String>()
    val errorMessage: LiveData<String> = _errorMessage

    // LiveData para los detalles del pedido
    private val _pedidoDetalles = MutableLiveData<ApiService.DetallesPedido>()
    val pedidoDetalles: LiveData<ApiService.DetallesPedido> = _pedidoDetalles

    // Repositorio de pedidos
    private val repository = PedidosRepository()

    // Variable para la última operación fallida
    private var lastOperation: (() -> Unit)? = null

    // Método para cargar los detalles de un pedido
    fun loadPedidoDetalles(folio: Int) {
        Log.d(TAG, "Iniciando carga de detalles para folio: $folio")
        _isLoading.value = true

        // Guardar la operación para posible reintento
        lastOperation = { loadPedidoDetalles(folio) }

        viewModelScope.launch {
            try {
                val username = sessionManager.getVendorName() ?: sessionManager.getVendorName()
                if (username.isNullOrEmpty()) {
                    Log.e(TAG, "No hay vendedor seleccionado")
                    _errorMessage.postValue("No se ha seleccionado un vendedor válido")
                    _isLoading.postValue(false)
                    return@launch
                }

                Log.d(TAG, "Solicitando detalles con usuario: $username")
                val detalles = repository.getPedidoDetalles(folio, username)
                _pedidoDetalles.postValue(detalles)
                _isLoading.postValue(false)
                Log.d(TAG, "Detalles cargados correctamente")
            } catch (e: UnknownHostException) {
                Log.e(TAG, "Error de conexión", e)
                _errorMessage.postValue("Error de conexión: Verifique su conexión a internet")
                _isLoading.postValue(false)
            } catch (e: Exception) {
                Log.e(TAG, "Error al cargar detalles", e)
                _errorMessage.postValue("Error: ${e.message ?: "Desconocido"}")
                _isLoading.postValue(false)
            }
        }
    }

    fun clearErrorMessage() {
        _errorMessage.value = ""
    }

    fun retryLastOperation() {
        lastOperation?.invoke()
    }

    // Factory para crear el ViewModel con dependencias
    class PedidoDetallesViewModelFactory(
        private val sessionManager: SessionManager
    ) : ViewModelProvider.Factory {
        override fun <T : ViewModel> create(modelClass: Class<T>): T {
            if (modelClass.isAssignableFrom(PedidoDetallesViewModel::class.java)) {
                @Suppress("UNCHECKED_CAST")
                return PedidoDetallesViewModel(sessionManager) as T
            }
            throw IllegalArgumentException("Unknown ViewModel class")
        }
    }
}