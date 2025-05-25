package mx.com.ksolutions.asf_pedidos

import okhttp3.ResponseBody
import retrofit2.Response

class ProfileRepository {
    private val apiService = RetrofitClient.apiService

    suspend fun getProfile(): Response<ProfileResponse> {
        return apiService.getProfile()
    }

    suspend fun updateProfile(
        nombre: String,
        apellidoP: String,
        apellidoM: String,
        calle: String,
        numero: String,
        colonia: String,
        municipio: String,
        estado: String,
        celular: String,
        contactoEmergencia: String,
        telefonoEmergencia: String
    ): Response<ResponseBody> {
        return apiService.updateProfile(
            nombre, apellidoP, apellidoM, calle, numero,
            colonia, municipio, estado, celular,
            contactoEmergencia, telefonoEmergencia
        )
    }
}