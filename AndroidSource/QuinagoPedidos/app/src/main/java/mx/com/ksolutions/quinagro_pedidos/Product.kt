// Product.kt
package mx.com.ksolutions.quinagro_pedidos

data class Product(
    val id: Int,
    val name: String,
    val description: String?, // Usaremos este campo como categoría
    val stock: Int,
    val createdAt: String,
    val updatedAt: String
) {
    // Método de utilidad para obtener la categoría del producto
    fun getCategory(): String {
        return description ?: "Sin categoría"
    }
}

// Clase para manejar la respuesta del servidor
data class ProductsResponse(
    val error: Boolean,
    val message: String?,
    val products: List<Product>?
)