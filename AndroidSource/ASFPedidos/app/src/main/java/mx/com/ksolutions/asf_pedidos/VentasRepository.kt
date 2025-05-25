package mx.com.ksolutions.asf_pedidos

import mx.com.ksolutions.asf_pedidos.RetrofitClient.apiService

class VentasRepository {
    suspend fun getVentas(
        username: String,
        page: Int = 1,
        perPage: Int = 10
    ): Pair<List<ApiService.Venta>, ApiService.Paginacion?> {
        return try {
            val response = apiService.getVentas(username, page, perPage)

            if (response.isSuccessful && response.body() != null) {
                val ventasResponse = response.body()!!
                if (!ventasResponse.error) {
                    Pair(ventasResponse.ventas ?: emptyList(), ventasResponse.paginacion)
                } else {
                    throw Exception(ventasResponse.message ?: "Error desconocido")
                }
            } else {
                throw Exception("Error en la respuesta: ${response.code()}")
            }
        } catch (e: Exception) {
            throw e
        }
    }
}