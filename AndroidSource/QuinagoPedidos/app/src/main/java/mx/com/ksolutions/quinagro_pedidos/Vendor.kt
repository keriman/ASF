package mx.com.ksolutions.quinagro_pedidos

/**
 * Clase de datos que representa a un vendedor en el sistema
 * @property name Nombre completo del vendedor
 * @property username Nombre de usuario para el sistema (Oficina X)
 */
data class Vendor(
    val name: String,
    val username: String
) {
    companion object {
        // Lista estática de vendedores con sus usuarios correspondientes
        val VENDORS = listOf(
            Vendor("Oficina", "Oficina")
        )

        // Método para obtener un vendedor por su nombre
        fun getVendorByName(name: String): Vendor? {
            return VENDORS.find { it.name == name }
        }

        // Método para obtener un vendedor por su username
        fun getVendorByUsername(username: String): Vendor? {
            return VENDORS.find { it.username == username }
        }

        // Obtener la lista de nombres de vendedores para el spinner
        fun getVendorNames(): List<String> {
            return VENDORS.map { it.name }
        }
    }
}