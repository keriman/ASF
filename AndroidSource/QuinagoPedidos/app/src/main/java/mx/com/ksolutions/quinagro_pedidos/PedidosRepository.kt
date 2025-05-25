package mx.com.ksolutions.quinagro_pedidos

import android.util.Log
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class PedidosRepository {
    private val TAG = "PedidosRepository"

    suspend fun getPedidoDetalles(
        folio: Int,
        username: String
    ): ApiService.DetallesPedido {
        return withContext(Dispatchers.IO) {
            try {
                Log.d(TAG, "Solicitando detalles para folio: $folio, usuario: $username")
                val response = RetrofitClient.apiService.getPedidoDetalles(folio, username)

                if (response.isSuccessful && response.body() != null) {
                    val pedidoResponse = response.body()!!
                    if (!pedidoResponse.error) {
                        Log.d(TAG, "Detalles obtenidos correctamente: ${pedidoResponse.data?.productos?.size ?: 0} productos")
                        pedidoResponse.data ?: throw Exception("No se encontraron detalles del pedido")
                    } else {
                        Log.e(TAG, "Error en la respuesta del servidor: ${pedidoResponse.message}")
                        throw Exception(pedidoResponse.message ?: "Error desconocido")
                    }
                } else {
                    Log.e(TAG, "Error en la respuesta HTTP: ${response.code()}, ${response.errorBody()?.string()}")
                    throw Exception("Error en la respuesta: ${response.code()}")
                }
            } catch (e: Exception) {
                Log.e(TAG, "Excepción al obtener detalles del pedido: ${e.message}", e)
                throw e
            }
        }
    }
}