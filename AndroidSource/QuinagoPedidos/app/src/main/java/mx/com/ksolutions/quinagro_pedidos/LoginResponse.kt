package mx.com.ksolutions.quinagro_pedidos

import com.google.gson.annotations.SerializedName

data class LoginResponse(
    @SerializedName("success") val success: Boolean = false,
    @SerializedName("tipo") val tipo: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("error") val error: Boolean = false  // Agregamos este campo
)