package mx.com.ksolutions.quinagro_pedidos

import android.util.Log
import retrofit2.Response

class ProductRepository {
    private val apiService = RetrofitClient.apiService
    private val TAG = "ProductRepository"

    suspend fun getProducts(): Response<ApiService.ProductsResponse> {
        return try {
            val response = apiService.getProducts()
            Log.d(TAG, "Productos obtenidos: ${response.body()?.products?.size ?: 0}")
            response
        } catch (e: Exception) {
            Log.e(TAG, "Error al obtener productos: ${e.message}", e)
            throw e
        }
    }
}