package mx.com.ksolutions.quinagro_pedidos

import com.google.gson.annotations.SerializedName
import okhttp3.ResponseBody
import retrofit2.Response
import retrofit2.http.*

interface ApiService {

    @GET("detalles.php") //OK
    suspend fun getPedidoDetalles(
        @Query("folio") folio: Int,
        @Query("username") username: String
    ): Response<BaseResponse<DetallesPedido>>

    // Clase de respuesta base (si no existe ya)
    data class BaseResponse<T>(
        val error: Boolean,
        val message: String? = null,
        val data: T? = null
    )


    @FormUrlEncoded
    @POST("control2.php") //OK
    suspend fun login(
        @Field("usuario") username: String,
        @Field("clave") password: String
    ): Response<ResponseBody>

    // Endpoint específico para la aplicación móvil
    @FormUrlEncoded
    @POST("app_login.php") //OK
    suspend fun appLogin(
        @Field("usuario") username: String,
        @Field("clave") password: String
    ): Response<LoginResponse>

    @GET("usuarios/perfil.php") //OK?
    suspend fun getProfile(): Response<ProfileResponse>

    @FormUrlEncoded
    @POST("usuarios/actualizar_perfil.php") //OK?
    suspend fun updateProfile(
        @Field("nombre") nombre: String,
        @Field("apellido_p") apellidoP: String,
        @Field("apellido_m") apellidoM: String,
        @Field("calle") calle: String,
        @Field("numero") numero: String,
        @Field("colonia") colonia: String,
        @Field("municipio") municipio: String,
        @Field("estado") estado: String,
        @Field("celular") celular: String,
        @Field("contacto_emergencia") contactoEmergencia: String,
        @Field("telefono_emergencia") telefonoEmergencia: String
    ): Response<ResponseBody>

    // Actualizado para enviar tanto el nombre visible como el usuario
    @FormUrlEncoded
    @POST("app_save_order.php") //OK
    suspend fun saveOrder(
        @Field("ff") folio: String,
        @Field("vv") vendorName: String,      // Nombre visible del vendedor
        @Field("cc") client: String,
        @Field("rr") destination: String,
        @Field("rt") ruta: String,            // Nuevo campo ruta
        @Field("fs") date: String,
        @Field("cm") comments: String,
        @Field("usuario") username: String     // Usuario del sistema (OficinaX)
    ): Response<SaveOrderResponse>

    @FormUrlEncoded
    @POST("app_alta_productos.php") //OK
    suspend fun addProduct(
        @Field("ff") folio: String,
        @Field("pp") product: String,
        @Field("pr") presentation: String,
        @Field("cc") quantity: String,
        @Field("cm") comments: String
    ): Response<ResponseBody>

    // Endpoint para eliminar productos
    @GET("app_quitar.php") //OK
    suspend fun removeProduct(
        @Query("ff") folio: String,
        @Query("pp") product: String,
        @Query("cc") quantity: String
    ): Response<ResponseBody>

    data class SaveOrderResponse(
        val status: String,
        val folio: String?,
        val message: String?,
        val warning: String?
    )

    @GET("ultimo_folio.php") //OK
    suspend fun getLastFolio(): Response<LastFolioResponse>

    data class LastFolioResponse(
        val nextFolio: Int,
        val status: String
    )

    // Añadir en ApiService.kt
    @GET("get_vendedores.php") //OK
    suspend fun getVendedores(): Response<VendorRepository.ServerVendorResponse>

    // También agrega la clase de respuesta
    data class VendedoresResponse(
        val error: Boolean,
        val message: String? = null,
        val vendedores: List<ServerVendor>?
    )

    // Clase para manejar el formato del servidor
    data class ServerVendor(
        val id: String,
        val name: String,
        val fon: String
    )
    // Añadir en ApiService.kt
    @GET("get_products.php") //OK
    suspend fun getProducts(): Response<ProductsResponse>

    @GET("get_ventas.php") //OK
    suspend fun getVentas(
        @Query("vendedor") username: String,
        @Query("pagina") page: Int = 1,
        @Query("por_pagina") perPage: Int = 10
    ): Response<VentasResponse>

    data class VentasResponse(
        val error: Boolean,
        val message: String?,
        val ventas: List<Venta>?,
        val paginacion: Paginacion?
    )

    data class Paginacion(
        val pagina_actual: Int,
        val por_pagina: Int,
        val total_registros: Int,
        val total_paginas: Int
    )

    data class Venta(
        val folio: String,
        val cliente: String,
        val destino: String,
        val ruta: String,
        val fecha_salida: String,
        val FR: String
    )

    data class DetallesPedido(
        val pedido: Pedido,
        val productos: List<ProductoPedido>,
        val observaciones: List<ObservacionPedido>
    )

    data class Pedido(
        val folio: Int,
        val vendedor: String,
        val destino: String,
        val fecha_salida: String,
        val status: Int,
        val cliente: String,
        val ruta: String
    )

    data class ProductoPedido(
        val id: Int,
        val producto: String,
        val presentacion: String,
        val cantidad: Int,
        @SerializedName("procesado")
        val procesadoRaw: Any? // Acepta cualquier tipo
    ) {
        // Propiedad calculada para convertir procesado a Boolean
        val procesado: Boolean
            get() = when (procesadoRaw) {
                is Boolean -> procesadoRaw
                is Number -> procesadoRaw.toInt() != 0 && procesadoRaw.toInt() != -1
                is String -> procesadoRaw.toBoolean() || procesadoRaw == "1"
                else -> false
            }
    }

    data class ObservacionPedido(
        val id: Int,
        val observaciones: String,
        val usuario: String,
        val FR: String,
        val modificada: Boolean
    )

    // Endpoint para actualizar detalles del pedido
    @FormUrlEncoded
    @POST("update_pedido_detalles.php")
    suspend fun updatePedidoDetalles(
        @Field("folio") folio: String,
        @Field("cliente") cliente: String,
        @Field("destino") destino: String,
        @Field("ruta") ruta: String,
        @Field("fecha_salida") fechaSalida: String,
        @Field("username") username: String
    ): Response<UpdatePedidoResponse>

    // Endpoint para actualizar un producto
    @FormUrlEncoded
    @POST("update_producto.php")
    suspend fun updateProducto(
        @Field("id") id: Int,
        @Field("producto") producto: String,
        @Field("presentacion") presentacion: String,
        @Field("cantidad") cantidad: Int,
        @Field("folio") folio: String,
        @Field("username") username: String
    ): Response<UpdateProductoResponse>

    // Endpoint para eliminar un producto
    @FormUrlEncoded
    @POST("delete_producto.php")
    suspend fun deleteProducto(
        @Field("id") id: Int,
        @Field("folio") folio: String,
        @Field("username") username: String
    ): Response<DeleteProductoResponse>

    // Clases de respuesta
    data class UpdatePedidoResponse(
        val success: Boolean,
        val message: String
    )

    data class UpdateProductoResponse(
        val success: Boolean,
        val message: String
    )

    data class DeleteProductoResponse(
        val success: Boolean,
        val message: String
    )

    // Agrega esto a tu ApiService.kt si no existe

    // Data class para productos
    data class Product(
        val id: Int,
        val name: String,
        val description: String,
        val stock: Int?,
        val created_at: String?,
        val updated_at: String?
    )

    // Modificar ProductsResponse si no está correcta
    data class ProductsResponse(
        val error: Boolean,
        val message: String? = null,
        val products: List<Product>?
    )
}