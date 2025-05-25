package mx.com.ksolutions.asf_pedidos

import android.os.Bundle
import android.view.View
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.launch
import mx.com.ksolutions.asf_pedidos.ApiService
import mx.com.ksolutions.asf_pedidos.databinding.ActivityAddProductBinding

class AddProductActivity : AppCompatActivity() {

    private lateinit var binding: ActivityAddProductBinding
    private var folio: String = ""
    private lateinit var sessionManager: SessionManager  // Tu SessionManager
    private var productos: List<ApiService.Product> = emptyList()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityAddProductBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Configurar ActionBar
        supportActionBar?.apply {
            title = "Agregar Producto"
            setDisplayHomeAsUpEnabled(true)
        }

        // Obtener folio del intent
        folio = intent.getStringExtra("folio") ?: ""
        if (folio.isEmpty()) {
            Toast.makeText(this, "Error: Folio no válido", Toast.LENGTH_SHORT).show()
            finish()
            return
        }

        sessionManager = SessionManager(this)  // Tu SessionManager

        setupViews()
        loadProducts()
    }

    private fun setupViews() {
        binding.btnAgregar.setOnClickListener {
            agregarProducto()
        }
    }

    private fun loadProducts() {
        binding.progressBar.visibility = View.VISIBLE

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.apiService.getProducts()

                if (response.isSuccessful && response.body() != null) {
                    val body = response.body()!!
                    if (!body.error && body.products != null) {
                        productos = body.products
                        setupProductSpinner()
                    } else {
                        Toast.makeText(
                            this@AddProductActivity,
                            body.message ?: "Error al cargar productos",
                            Toast.LENGTH_SHORT
                        ).show()
                    }
                } else {
                    Toast.makeText(
                        this@AddProductActivity,
                        "Error al cargar productos",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            } catch (e: Exception) {
                Toast.makeText(
                    this@AddProductActivity,
                    "Error: ${e.message}",
                    Toast.LENGTH_SHORT
                ).show()
            } finally {
                binding.progressBar.visibility = View.GONE
            }
        }
    }

    private fun setupProductSpinner() {
        // Crear lista de nombres de productos con formato "Description - Name"
        val productNames = productos.map { "${it.description} - ${it.name}" }

        val adapter = ArrayAdapter<String>(
            this,
            android.R.layout.simple_spinner_item,
            productNames
        )
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)

        binding.spinnerProducto.adapter = adapter
    }

    private fun agregarProducto() {
        val selectedPosition = binding.spinnerProducto.selectedItemPosition
        if (selectedPosition < 0 || selectedPosition >= productos.size) {
            Toast.makeText(this, "Seleccione un producto", Toast.LENGTH_SHORT).show()
            return
        }

        val selectedProduct = productos[selectedPosition]
        val cantidad = binding.etCantidad.text.toString().trim()
        val comentarios = binding.etComentarios.text.toString().trim()

        if (cantidad.isEmpty()) {
            Toast.makeText(this, "Ingrese la cantidad", Toast.LENGTH_SHORT).show()
            return
        }

        val cantidadInt = cantidad.toIntOrNull() ?: 0
        if (cantidadInt <= 0) {
            Toast.makeText(this, "La cantidad debe ser mayor a 0", Toast.LENGTH_SHORT).show()
            return
        }

        // Deshabilitar el botón mientras se procesa
        binding.btnAgregar.isEnabled = false
        binding.progressBar.visibility = View.VISIBLE

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.apiService.addProduct(
                    folio = folio,
                    product = selectedProduct.name,
                    presentation = "",  // Enviar vacío ya que no se usa
                    quantity = cantidad,
                    comments = comentarios
                )

                if (response.isSuccessful) {
                    Toast.makeText(
                        this@AddProductActivity,
                        "Producto agregado correctamente",
                        Toast.LENGTH_SHORT
                    ).show()
                    setResult(RESULT_OK)
                    finish()
                } else {
                    Toast.makeText(
                        this@AddProductActivity,
                        "Error al agregar el producto",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            } catch (e: Exception) {
                Toast.makeText(
                    this@AddProductActivity,
                    "Error: ${e.message}",
                    Toast.LENGTH_SHORT
                ).show()
            } finally {
                binding.btnAgregar.isEnabled = true
                binding.progressBar.visibility = View.GONE
            }
        }
    }

    override fun onSupportNavigateUp(): Boolean {
        onBackPressed()
        return true
    }
}