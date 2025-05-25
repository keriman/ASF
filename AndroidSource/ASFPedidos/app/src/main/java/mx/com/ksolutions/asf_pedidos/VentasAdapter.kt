package mx.com.ksolutions.asf_pedidos

import android.graphics.Color
import android.graphics.Typeface
import android.text.Spannable
import android.text.SpannableString
import android.text.style.ForegroundColorSpan
import android.text.style.StyleSpan
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import android.widget.Toast
import androidx.recyclerview.widget.RecyclerView

class VentasAdapter(
    private val onClick: (ApiService.Venta) -> Unit,
    private val onFolioClick: (Int) -> Unit
) : RecyclerView.Adapter<VentasAdapter.VentaViewHolder>() {

    private val TAG = "VentasAdapter"
    private var ventas: List<ApiService.Venta> = emptyList()

    fun setVentas(newVentas: List<ApiService.Venta>) {
        ventas = newVentas
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VentaViewHolder {
        val itemView = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_venta, parent, false)
        return VentaViewHolder(itemView)
    }

    override fun onBindViewHolder(holder: VentaViewHolder, position: Int) {
        val venta = ventas[position]
        holder.bind(venta)
    }

    override fun getItemCount() = ventas.size

    inner class VentaViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvFolio: TextView = itemView.findViewById(R.id.tvFolio)
        private val tvCliente: TextView = itemView.findViewById(R.id.tvCliente)
        private val tvDestino: TextView = itemView.findViewById(R.id.tvDestino)
        private val tvRuta: TextView = itemView.findViewById(R.id.tvRuta)
        private val tvFecha: TextView = itemView.findViewById(R.id.tvFecha)

        init {
            // Listener para el clic en toda la tarjeta
            itemView.setOnClickListener {
                val position = adapterPosition
                if (position != RecyclerView.NO_POSITION) {
                    onClick(ventas[position])
                }
            }

            // Listener específico para el folio
            tvFolio.setOnClickListener {
                val position = adapterPosition
                if (position != RecyclerView.NO_POSITION) {
                    try {
                        // Intentar convertir el folio a Int
                        val folio = ventas[position].folio.toInt()
                        Log.d(TAG, "Folio clickeado: $folio")
                        onFolioClick(folio)
                    } catch (e: NumberFormatException) {
                        Log.e(TAG, "No se pudo convertir el folio: ${ventas[position].folio}", e)
                        Toast.makeText(
                            itemView.context,
                            "Error al procesar el folio",
                            Toast.LENGTH_SHORT
                        ).show()
                    }
                }
            }
        }

        fun bind(venta: ApiService.Venta) {
            // Crear texto especial para el folio, indicando que es clickeable
            val folioText = SpannableString("Folio: ${venta.folio} (Ver detalles)")
            folioText.setSpan(
                StyleSpan(Typeface.BOLD),
                0,
                7 + venta.folio.length,
                Spannable.SPAN_EXCLUSIVE_EXCLUSIVE
            )

            // Añadir color al texto "Ver detalles"
            folioText.setSpan(
                ForegroundColorSpan(Color.CYAN),
                7 + venta.folio.length,
                folioText.length,
                Spannable.SPAN_EXCLUSIVE_EXCLUSIVE
            )

            tvFolio.text = folioText

            // Formatear texto con etiquetas en negrita
            tvCliente.text = buildFormattedText("Cliente", venta.cliente)
            tvDestino.text = buildFormattedText("Destino", venta.destino)
            tvRuta.text = buildFormattedText("Ruta", venta.ruta)

            // Formatear fecha
            val fechaFormateada = FormatUtils.formatDate(venta.fecha_salida)
            tvFecha.text = buildFormattedText("Fecha", fechaFormateada)
        }

        private fun buildFormattedText(label: String, value: String): SpannableString {
            val text = SpannableString("$label: $value")
            text.setSpan(
                StyleSpan(Typeface.BOLD),
                0,
                label.length,
                Spannable.SPAN_EXCLUSIVE_EXCLUSIVE
            )
            return text
        }
    }
}