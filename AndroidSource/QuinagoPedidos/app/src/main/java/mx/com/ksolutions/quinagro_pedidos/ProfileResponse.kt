package mx.com.ksolutions.quinagro_pedidos

import com.google.gson.annotations.SerializedName

data class ProfileResponse(
    @SerializedName("error") val error: Boolean,
    @SerializedName("message") val message: String?,
    @SerializedName("data") val data: ProfileData?
)

data class ProfileData(
    @SerializedName("nombre") val nombre: String?,
    @SerializedName("apellido_p") val apellidoP: String?,
    @SerializedName("apellido_m") val apellidoM: String?,
    @SerializedName("calle") val calle: String?,
    @SerializedName("numero") val numero: String?,
    @SerializedName("colonia") val colonia: String?,
    @SerializedName("municipio") val municipio: String?,
    @SerializedName("estado") val estado: String?,
    @SerializedName("celular") val celular: String?,
    @SerializedName("contacto_emergencia") val contactoEmergencia: String?,
    @SerializedName("telefono_emergencia") val telefonoEmergencia: String?,
    @SerializedName("foto") val foto: String?
)