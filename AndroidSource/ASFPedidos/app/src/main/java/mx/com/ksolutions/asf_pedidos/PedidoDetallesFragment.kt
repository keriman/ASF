package mx.com.ksolutions.asf_pedidos

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.util.Log
import android.view.Menu
import android.view.MenuItem
import android.view.View
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.ViewModelProvider
import androidx.recyclerview.widget.DividerItemDecoration
import androidx.recyclerview.widget.LinearLayoutManager
import mx.com.ksolutions.asf_pedidos.databinding.ActivityPedidodetallesBinding

class PedidoDetallesActivity : AppCompatActivity() {
    private val TAG = "PedidoDetallesActivity"

    private lateinit var binding: ActivityPedidodetallesBinding
    private lateinit var viewModel: PedidoDetallesViewModel
    private lateinit var sessionManager: SessionManager
    private lateinit var productosAdapter: ProductosAdapter
    private lateinit var observacionesAdapter: ObservacionesAdapter

    companion object {
        private const val EXTRA_FOLIO = "extra_folio"
        private const val REQUEST_EDIT_PEDIDO = 1000

        fun getIntent(context: Context, folio: Int): Intent {
            return Intent(context, PedidoDetallesActivity::class.java).apply {
                putExtra(EXTRA_FOLIO, folio)
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Inflate layout
        binding = ActivityPedidodetallesBinding.inflate(layoutInflater)
        setContentView(binding.root)

        try {
            // Configurar Toolbar
            setSupportActionBar(binding.toolbar)
            supportActionBar?.setDisplayHomeAsUpEnabled(true)
            supportActionBar?.title = "Detalle de Pedido"

            // Inicializar SessionManager
            sessionManager = SessionManager(this)

            // Configurar RecyclerViews
            setupRecyclerViews()

            // Inicializar ViewModel
            val factory = PedidoDetallesViewModel.PedidoDetallesViewModelFactory(sessionManager)
            viewModel = ViewModelProvider(this, factory)[PedidoDetallesViewModel::class.java]

            // Configurar observadores
            setupObservers()

            // Obtener el folio de los extras
            val folio = intent.getIntExtra(EXTRA_FOLIO, -1)
            if (folio <= 0) {
                showError("Folio inválido. No se puede mostrar los detalles.")
                finish()
                return
            }

            Log.d(TAG, "Folio recibido: $folio")

            // Cargar detalles del pedido
            viewModel.loadPedidoDetalles(folio)
        } catch (e: Exception) {
            Log.e(TAG, "Error en onCreate", e)
            showError("Error al inicializar: ${e.message}")
            finish()
        }
    }

    override fun onCreateOptionsMenu(menu: Menu): Boolean {
        menuInflater.inflate(R.menu.menu_pedido_detalles, menu)
        return true
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        return when (item.itemId) {
            android.R.id.home -> {
                finish()
                true
            }
            R.id.action_edit -> {
                navigateToEditActivity()
                true
            }
            else -> super.onOptionsItemSelected(item)
        }
    }

    private fun navigateToEditActivity() {
        val folio = intent.getIntExtra(EXTRA_FOLIO, -1)
        if (folio > 0) {
            val intent = PedidoEditActivity.getIntent(this, folio)
            startActivityForResult(intent, REQUEST_EDIT_PEDIDO)
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == REQUEST_EDIT_PEDIDO && resultCode == RESULT_OK) {
            // Recargar los detalles después de editar
            val folio = intent.getIntExtra(EXTRA_FOLIO, -1)
            if (folio > 0) {
                viewModel.loadPedidoDetalles(folio)
            }
        }
    }

    private fun setupRecyclerViews() {
        // Configurar RecyclerView de Productos
        productosAdapter = ProductosAdapter()
        binding.rvProductos.apply {
            layoutManager = LinearLayoutManager(this@PedidoDetallesActivity)
            addItemDecoration(DividerItemDecoration(this@PedidoDetallesActivity, DividerItemDecoration.VERTICAL))
            adapter = productosAdapter
        }

        // Configurar RecyclerView de Observaciones
        observacionesAdapter = ObservacionesAdapter()
        binding.rvObservaciones.apply {
            layoutManager = LinearLayoutManager(this@PedidoDetallesActivity)
            addItemDecoration(DividerItemDecoration(this@PedidoDetallesActivity, DividerItemDecoration.VERTICAL))
            adapter = observacionesAdapter
        }
    }

    private fun setupObservers() {
        // Observar estado de carga
        viewModel.isLoading.observe(this) { isLoading ->
            binding.progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
            binding.contentLayout.visibility = if (isLoading) View.GONE else View.VISIBLE
        }

        // Observar mensajes de error
        viewModel.errorMessage.observe(this) { errorMessage ->
            if (errorMessage.isNotEmpty()) {
                showError(errorMessage)
                viewModel.clearErrorMessage()
            }
        }

        // Observar detalles del pedido
        viewModel.pedidoDetalles.observe(this) { detalles ->
            try {
                Log.d(TAG, "Actualizando UI con detalles recibidos")

                // Actualizar información del pedido
                val statusText = when (detalles.pedido.status) {
                    10 -> "Pendiente"
                    20 -> "En proceso"
                    30 -> "Completado"
                    else -> "Desconocido"
                }

                binding.tvPedidoInfo.text = """
                    Folio: ${detalles.pedido.folio}
                    Cliente: ${detalles.pedido.cliente}
                    Destino: ${detalles.pedido.destino}
                    Ruta: ${detalles.pedido.ruta}
                    Fecha: ${detalles.pedido.fecha_salida}
                    Estado: $statusText
                """.trimIndent()

                // Actualizar productos
                if (detalles.productos.isEmpty()) {
                    binding.tvNoProductos.visibility = View.VISIBLE
                    binding.rvProductos.visibility = View.GONE
                } else {
                    binding.tvNoProductos.visibility = View.GONE
                    binding.rvProductos.visibility = View.VISIBLE
                    productosAdapter.setProductos(detalles.productos)
                }

                // Actualizar observaciones
                if (detalles.observaciones.isEmpty()) {
                    binding.tvNoObservaciones.visibility = View.VISIBLE
                    binding.rvObservaciones.visibility = View.GONE
                } else {
                    binding.tvNoObservaciones.visibility = View.GONE
                    binding.rvObservaciones.visibility = View.VISIBLE
                    observacionesAdapter.setObservaciones(detalles.observaciones)
                }

                // Hacer visible todo el contenido
                binding.contentLayout.visibility = View.VISIBLE
            } catch (e: Exception) {
                Log.e(TAG, "Error al actualizar UI con detalles", e)
                showError("Error al mostrar los detalles: ${e.message}")
            }
        }
    }

    private fun showError(message: String) {
        Log.e(TAG, "Error: $message")
        AlertDialog.Builder(this)
            .setTitle("Error")
            .setMessage(message)
            .setPositiveButton("Aceptar") { _, _ ->
                // Si es un error crítico, cerrar la actividad
                if (viewModel.pedidoDetalles.value == null) {
                    finish()
                }
            }
            .show()
    }
}