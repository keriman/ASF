package mx.com.ksolutions.quinagro_pedidos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider

class PedidoEditViewModelFactory(
    private val sessionManager: SessionManager,
    private val folio: Int
) : ViewModelProvider.Factory {

    @Suppress("UNCHECKED_CAST")
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(PedidoEditViewModel::class.java)) {
            return PedidoEditViewModel(sessionManager, folio) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}