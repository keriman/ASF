package mx.com.ksolutions.asf_pedidos

import android.util.Log
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class VendorRepository {
    private val TAG = "VendorRepository"

    // Clase para manejar la respuesta del servidor con el formato exacto
    data class ServerVendorResponse(
        val error: Boolean,
        val vendedores: List<ServerVendor>
    )

    data class ServerVendor(
        val id: String,
        val name: String,
        val fon: String
    )

    suspend fun getVendedores(): List<Vendor> {
        return withContext(Dispatchers.IO) {
            try {
                val apiService = RetrofitClient.apiService
                val response = apiService.getVendedores()

                Log.d(TAG, "Obteniendo vendedores: ${response.raw().request.url}")

                if (response.isSuccessful) {
                    val responseBody = response.body()

                    // Log detallado de la respuesta
                    Log.d(TAG, "Respuesta del servidor: $responseBody")

                    if (responseBody?.error == false && !responseBody.vendedores.isNullOrEmpty()) {
                        // Convertir los vendedores del servidor al formato de la aplicación
                        val vendors = responseBody.vendedores.map { serverVendor ->
                            val username = findUsernameForVendor(serverVendor.name)
                            Vendor(
                                name = serverVendor.name,
                                username = username
                            )
                        }

                        Log.d(TAG, "Vendedores procesados (${vendors.size}): $vendors")
                        return@withContext vendors
                    } else {
                        Log.e(TAG, "Respuesta sin vendedores o con error: ${responseBody?.error}")
                        return@withContext Vendor.VENDORS
                    }
                } else {
                    Log.e(TAG, "Error HTTP: ${response.code()} - ${response.errorBody()?.string()}")
                    return@withContext Vendor.VENDORS
                }
            } catch (e: Exception) {
                Log.e(TAG, "Excepción: ${e.message}", e)
                return@withContext Vendor.VENDORS
            }
        }
    }

    // Función para asignar un username basado en el nombre del vendedor
    private fun findUsernameForVendor(name: String): String {
        // Primero, buscar en la lista estática si hay alguna coincidencia
        val match = Vendor.VENDORS.find { it.name == name }
        if (match != null) {
            Log.d(TAG, "Encontrado username para $name: ${match.username}")
            return match.username
        }

        // Si no hay coincidencia, asignar Oficina1 como predeterminado
        Log.d(TAG, "Asignando username predeterminado para $name: Oficina1")
        return "Oficina1"
    }
}