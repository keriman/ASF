package mx.com.ksolutions.asf_pedidos

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageButton
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class ProductosEditAdapter(
    private val onEditClick: (ApiService.ProductoPedido) -> Unit,
    private val onDeleteClick: (ApiService.ProductoPedido) -> Unit
) : RecyclerView.Adapter<ProductosEditAdapter.ProductoViewHolder>() {

    private var productos: List<ApiService.ProductoPedido> = emptyList()

    fun setProductos(newProductos: List<ApiService.ProductoPedido>) {
        productos = newProductos
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductoViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_producto_edit, parent, false)
        return ProductoViewHolder(view)
    }

    override fun onBindViewHolder(holder: ProductoViewHolder, position: Int) {
        holder.bind(productos[position])
    }

    override fun getItemCount() = productos.size

    inner class ProductoViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvProducto: TextView = itemView.findViewById(R.id.tvProducto)
        private val tvPresentacion: TextView = itemView.findViewById(R.id.tvPresentacion)
        private val tvCantidad: TextView = itemView.findViewById(R.id.tvCantidad)
        private val btnEdit: ImageButton = itemView.findViewById(R.id.btnEdit)
        private val btnDelete: ImageButton = itemView.findViewById(R.id.btnDelete)

        fun bind(producto: ApiService.ProductoPedido) {
            tvProducto.text = producto.producto
            tvPresentacion.text = producto.presentacion
            tvCantidad.text = "Cantidad: ${producto.cantidad}"

            btnEdit.setOnClickListener {
                onEditClick(producto)
            }

            btnDelete.setOnClickListener {
                onDeleteClick(producto)
            }
        }
    }
}