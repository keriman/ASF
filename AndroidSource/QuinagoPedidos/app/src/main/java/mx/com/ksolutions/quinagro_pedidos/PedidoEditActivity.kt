package mx.com.ksolutions.quinagro_pedidos

import android.app.AlertDialog
import android.app.DatePickerDialog
import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import kotlinx.coroutines.launch
import mx.com.ksolutions.quinagro_pedidos.databinding.ActivityPedidoEditBinding
import java.text.SimpleDateFormat
import java.util.*

class PedidoEditActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPedidoEditBinding
    private lateinit var viewModel: PedidoEditViewModel
    private lateinit var productosAdapter: ProductosEditAdapter
    private lateinit var sessionManager: SessionManager

    private var folio: Int = 0

    companion object {
        const val EXTRA_FOLIO = "extra_folio"
        private const val REQUEST_ADD_PRODUCT = 1001

        fun getIntent(context: Context, folio: Int): Intent {
            return Intent(context, PedidoEditActivity::class.java).apply {
                putExtra(EXTRA_FOLIO, folio)
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPedidoEditBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Configurar ActionBar
        supportActionBar?.apply {
            title = "Editar Pedido"
            setDisplayHomeAsUpEnabled(true)
        }

        // Obtener folio del intent
        folio = intent.getIntExtra(EXTRA_FOLIO, 0)
        if (folio == 0) {
            Toast.makeText(this, "Error: Folio no válido", Toast.LENGTH_SHORT).show()
            finish()
            return
        }

        sessionManager = SessionManager(this)

        // Configurar ViewModel
        val factory = PedidoEditViewModelFactory(sessionManager, folio)
        viewModel = ViewModelProvider(this, factory)[PedidoEditViewModel::class.java]

        setupViews()
        setupObservers()
    }

    private fun setupViews() {
        // Configurar RecyclerView para productos
        productosAdapter = ProductosEditAdapter(
            onEditClick = { producto ->
                showEditProductDialog(producto)
            },
            onDeleteClick = { producto ->
                showDeleteProductDialog(producto)
            }
        )

        binding.rvProductos.apply {
            layoutManager = LinearLayoutManager(this@PedidoEditActivity)
            adapter = productosAdapter
        }

        // Configurar botón de guardar
        binding.btnGuardar.setOnClickListener {
            saveChanges()
        }

        // Configurar date picker para fecha
        binding.etFechaSalida.setOnClickListener {
            showDatePicker()
        }

        // Hacer que el campo de fecha no sea editable manualmente
        binding.etFechaSalida.isFocusable = false
        binding.etFechaSalida.isClickable = true

        // Configurar botón de agregar producto
        binding.btnAgregarProducto.setOnClickListener {
            // Navegar a la actividad de agregar producto
            val intent = Intent(this, AddProductActivity::class.java)
            intent.putExtra("folio", folio.toString())
            startActivityForResult(intent, REQUEST_ADD_PRODUCT)
        }
    }

    private fun setupObservers() {
        viewModel.pedidoDetalles.observe(this) { detalles ->
            detalles?.let {
                updateUI(it)
            }
        }

        viewModel.isLoading.observe(this) { isLoading ->
            binding.progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
            binding.scrollView.visibility = if (isLoading) View.GONE else View.VISIBLE
        }

        viewModel.isUpdating.observe(this) { isUpdating ->
            binding.btnGuardar.isEnabled = !isUpdating
            binding.progressBar.visibility = if (isUpdating) View.VISIBLE else View.GONE
        }

        viewModel.errorMessage.observe(this) { error ->
            if (error.isNotEmpty()) {
                Toast.makeText(this, error, Toast.LENGTH_LONG).show()
                viewModel.clearErrorMessage()
            }
        }

        viewModel.updateSuccess.observe(this) { success ->
            if (success) {
                Toast.makeText(this, "Pedido actualizado correctamente", Toast.LENGTH_SHORT).show()
                setResult(RESULT_OK)
                finish()
            }
        }

        // Observar eliminación de producto exitosa
        viewModel.deleteSuccess.observe(this) { success ->
            if (success) {
                Toast.makeText(this, "Producto eliminado correctamente", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun updateUI(detalles: ApiService.DetallesPedido) {
        val pedido = detalles.pedido

        // Llenar campos editables
        binding.etCliente.setText(pedido.cliente)
        binding.etDestino.setText(pedido.destino)
        binding.etRuta.setText(pedido.ruta)

        // Formatear y mostrar fecha
        val fechaFormateada = FormatUtils.formatDate(pedido.fecha_salida)
        binding.etFechaSalida.setText(fechaFormateada)

        // Mostrar información no editable
        binding.tvFolio.text = "Folio: ${pedido.folio}"
        binding.tvVendedor.text = "Vendedor: ${pedido.vendedor}"
        binding.tvEstado.text = "Estado: ${getStatusText(pedido.status)}"

        // Actualizar lista de productos
        productosAdapter.setProductos(detalles.productos)

        // Mostrar/ocultar mensaje de no productos
        if (detalles.productos.isEmpty()) {
            binding.tvNoProductos.visibility = View.VISIBLE
            binding.rvProductos.visibility = View.GONE
        } else {
            binding.tvNoProductos.visibility = View.GONE
            binding.rvProductos.visibility = View.VISIBLE
        }
    }

    private fun showDatePicker() {
        val calendar = Calendar.getInstance()

        // Si ya hay una fecha, usarla como punto de partida
        val currentDate = binding.etFechaSalida.text.toString()
        if (currentDate.isNotEmpty()) {
            try {
                val formatter = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())
                val date = formatter.parse(currentDate)
                date?.let {
                    calendar.time = it
                }
            } catch (e: Exception) {
                // Ignorar error de parsing
            }
        }

        val datePickerDialog = DatePickerDialog(
            this,
            { _, year, month, dayOfMonth ->
                val selectedDate = Calendar.getInstance()
                selectedDate.set(year, month, dayOfMonth)

                val formatter = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())
                binding.etFechaSalida.setText(formatter.format(selectedDate.time))
            },
            calendar.get(Calendar.YEAR),
            calendar.get(Calendar.MONTH),
            calendar.get(Calendar.DAY_OF_MONTH)
        )

        datePickerDialog.show()
    }

    private fun saveChanges() {
        val cliente = binding.etCliente.text.toString().trim()
        val destino = binding.etDestino.text.toString().trim()
        val ruta = binding.etRuta.text.toString().trim()
        val fechaSalida = binding.etFechaSalida.text.toString().trim()

        if (destino.isEmpty()) {
            Toast.makeText(this, "El destino es requerido", Toast.LENGTH_SHORT).show()
            return
        }

        viewModel.updatePedidoDetalles(cliente, destino, ruta, fechaSalida)
    }

    private fun showEditProductDialog(producto: ApiService.ProductoPedido) {
        val dialogView = layoutInflater.inflate(R.layout.dialog_edit_product, null)
        val etCantidad = dialogView.findViewById<EditText>(R.id.etCantidad)

        // Mostrar cantidad actual
        etCantidad.setText(producto.cantidad.toString())

        AlertDialog.Builder(this)
            .setTitle("Editar ${producto.producto}")
            .setView(dialogView)
            .setPositiveButton("Guardar") { _, _ ->
                val nuevaCantidad = etCantidad.text.toString().toIntOrNull() ?: 0
                if (nuevaCantidad > 0) {
                    viewModel.updateProducto(producto, nuevaCantidad)
                } else {
                    Toast.makeText(this, "La cantidad debe ser mayor a 0", Toast.LENGTH_SHORT).show()
                }
            }
            .setNegativeButton("Cancelar", null)
            .show()
    }

    private fun showDeleteProductDialog(producto: ApiService.ProductoPedido) {
        AlertDialog.Builder(this)
            .setTitle("Eliminar Producto")
            .setMessage("¿Está seguro de eliminar ${producto.producto}?")
            .setPositiveButton("Eliminar") { _, _ ->
                viewModel.deleteProducto(producto)
            }
            .setNegativeButton("Cancelar", null)
            .show()
    }

    private fun getStatusText(status: Int): String {
        return when (status) {
            10 -> "En proceso"
            20 -> "Completado"
            30 -> "Cancelado"
            else -> "Desconocido"
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        onBackPressed()
        return true
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == REQUEST_ADD_PRODUCT && resultCode == RESULT_OK) {
            // Recargar los detalles después de agregar un producto
            viewModel.loadPedidoDetalles()
        }
    }
}