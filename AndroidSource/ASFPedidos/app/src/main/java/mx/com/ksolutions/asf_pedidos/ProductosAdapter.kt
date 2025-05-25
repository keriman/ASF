package mx.com.ksolutions.asf_pedidos

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

// Adapter para Productos
class ProductosAdapter : RecyclerView.Adapter<ProductosAdapter.ProductoViewHolder>() {
    private var productos: List<ApiService.ProductoPedido> = emptyList()

    fun setProductos(newProductos: List<ApiService.ProductoPedido>) {
        productos = newProductos
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductoViewHolder {
        val itemView = LayoutInflater.from(parent.context)
            .inflate(R.layout.producto, parent, false)
        return ProductoViewHolder(itemView)
    }

    override fun onBindViewHolder(holder: ProductoViewHolder, position: Int) {
        val producto = productos[position]
        holder.bind(producto)
    }

    override fun getItemCount() = productos.size

    inner class ProductoViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvProducto: TextView = itemView.findViewById(R.id.tvProducto)
        private val tvPresentacion: TextView = itemView.findViewById(R.id.tvPresentacion)
        private val tvCantidad: TextView = itemView.findViewById(R.id.tvCantidad)
        private val tvProcesado: TextView = itemView.findViewById(R.id.tvProcesado)

        fun bind(producto: ApiService.ProductoPedido) {
            tvProducto.text = "Producto: ${producto.producto}"
            tvPresentacion.text = "Presentación: ${producto.presentacion.ifEmpty { "N/A" }}"
            tvCantidad.text = "Cantidad: ${producto.cantidad}"

            // Cambiar el aspecto del estado procesado
            val procesadoText = if (producto.procesado) "Sí" else "No"
            val procesadoTextFull = "Procesado: $procesadoText"
            tvProcesado.text = procesadoTextFull

            // Opcional: Cambiar el color del texto según el estado
            tvProcesado.setTextColor(
                itemView.resources.getColor(
                    if (producto.procesado) android.R.color.holo_green_dark
                    else android.R.color.holo_orange_dark
                )
            )
        }
    }

    fun updateProductos(nuevosProductos: List<ApiService.ProductoPedido>) {
        productos = nuevosProductos
        notifyDataSetChanged()
    }
}

// Adapter para Observaciones
class ObservacionesAdapter : RecyclerView.Adapter<ObservacionesAdapter.ObservacionViewHolder>() {
    private var observaciones: List<ApiService.ObservacionPedido> = emptyList()

    fun setObservaciones(newObservaciones: List<ApiService.ObservacionPedido>) {
        observaciones = newObservaciones
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ObservacionViewHolder {
        val itemView = LayoutInflater.from(parent.context)
            .inflate(R.layout.observaciones, parent, false)
        return ObservacionViewHolder(itemView)
    }

    override fun onBindViewHolder(holder: ObservacionViewHolder, position: Int) {
        val observacion = observaciones[position]
        holder.bind(observacion)
    }

    override fun getItemCount() = observaciones.size

    inner class ObservacionViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvObservacion: TextView = itemView.findViewById(R.id.tvObservacion)
        private val tvUsuario: TextView = itemView.findViewById(R.id.tvUsuario)
        private val tvFecha: TextView = itemView.findViewById(R.id.tvFecha)
        private val tvModificada: TextView = itemView.findViewById(R.id.tvModificada)

        fun bind(observacion: ApiService.ObservacionPedido) {
            tvObservacion.text = "Observación: ${observacion.observaciones}"
            tvUsuario.text = "Usuario: ${observacion.usuario}"

            // Formatear fecha
            val fechaFormateada = FormatUtils.formatDateTime(observacion.FR)
            tvFecha.text = "Fecha: $fechaFormateada"

            val modificadaText = if (observacion.modificada) "Sí" else "No"
            tvModificada.text = "Modificada: $modificadaText"

            // Opcional: Cambiar el color del texto de modificada
            if (observacion.modificada) {
                tvModificada.setTextColor(itemView.resources.getColor(android.R.color.holo_blue_dark))
            }
        }
    }
}