package mx.com.ksolutions.asf_pedidos

import android.content.Intent
import android.os.Bundle
import android.util.Log
import android.view.View
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import mx.com.ksolutions.asf_pedidos.databinding.ActivityLoginBinding

class LoginActivity : AppCompatActivity() {
    private val TAG = "LoginActivity"
    private lateinit var binding: ActivityLoginBinding
    private lateinit var vendorRepository: VendorRepository
    private var vendorList = mutableListOf<Vendor>()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Verificar si ya hay un vendedor guardado
        val sharedPref = getSharedPreferences("ASFPrefs", MODE_PRIVATE)
        val savedVendorName = sharedPref.getString("NOMBRE_VENDEDOR", null)
        val savedVendorUsername = sharedPref.getString("USERNAME_VENDEDOR", null)
        val mostrarLogin = sharedPref.getBoolean("MOSTRAR_LOGIN", false)

        // Si existe un vendedor guardado y no se ha solicitado mostrar el login, ir directo a DrawerActivity
        if (!savedVendorName.isNullOrEmpty() && !savedVendorUsername.isNullOrEmpty() && !mostrarLogin) {
            startDrawerActivity(savedVendorName, savedVendorUsername)
            return
        }

        // Si llegamos aquí, es porque:
        // 1. No hay vendedor guardado, o
        // 2. Se ha solicitado explícitamente mostrar el login

        // Resetear la bandera de mostrar login
        if (mostrarLogin) {
            with(sharedPref.edit()) {
                putBoolean("MOSTRAR_LOGIN", false)
                apply()
            }
        }

        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Inicializar RetrofitClient primero
        RetrofitClient.init(this)

        // Ahora es seguro inicializar el repositorio
        vendorRepository = VendorRepository()

        // Mostrar indicador de carga
        binding.progressBar.visibility = View.VISIBLE

        // Inicialmente, cargar lista estática mientras se obtienen los datos del servidor
        vendorList.clear()
        vendorList.addAll(Vendor.VENDORS)
        setupVendorSpinner()

        // Cargar vendedores desde el servidor
        loadVendorsFromServer()

        setupListeners()
    }

    private fun loadVendorsFromServer() {
        binding.progressBar.visibility = View.VISIBLE

        lifecycleScope.launch {
            try {
                Log.d(TAG, "Iniciando carga de vendedores del servidor")

                // Obtener vendedores desde el servidor
                val vendors = vendorRepository.getVendedores()

                Log.d(TAG, "Vendedores obtenidos: $vendors")

                // Actualizar UI en el hilo principal
                withContext(Dispatchers.Main) {
                    if (vendors.isNotEmpty()) {
                        // Limpiar y actualizar la lista
                        vendorList.clear()
                        vendorList.addAll(vendors)

                        // Actualizar el spinner con los nuevos datos
                        Log.d(TAG, "Actualizando spinner con ${vendorList.size} vendedores")
                        updateVendorSpinner()
                    } else {
                        Log.e(TAG, "Lista de vendedores vacía del servidor")
                        Toast.makeText(
                            this@LoginActivity,
                            "No se pudieron cargar los vendedores desde el servidor",
                            Toast.LENGTH_SHORT
                        ).show()
                    }

                    binding.progressBar.visibility = View.GONE
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error al cargar vendedores: ${e.message}", e)

                withContext(Dispatchers.Main) {
                    binding.progressBar.visibility = View.GONE
                    Toast.makeText(
                        this@LoginActivity,
                        "Error: ${e.message}",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            }
        }
    }

    private fun setupVendorSpinner() {
        val vendorNames = vendorList.map { it.name }

        Log.d(TAG, "Configurando spinner con: $vendorNames")

        val adapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_item,
            vendorNames
        )
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)

        binding.spinnerVendor.adapter = adapter
    }

    private fun updateVendorSpinner() {
        val vendorNames = vendorList.map { it.name }

        Log.d(TAG, "Actualizando spinner con: $vendorNames")

        // Crear un nuevo adaptador con los datos actualizados
        val adapter = ArrayAdapter(
            this,
            android.R.layout.simple_spinner_item,
            vendorNames
        )
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)

        // Establecer el nuevo adaptador
        binding.spinnerVendor.adapter = adapter
    }

    private fun setupListeners() {
        binding.btnIngresar.setOnClickListener {
            val selectedPosition = binding.spinnerVendor.selectedItemPosition

            if (selectedPosition < 0 || selectedPosition >= vendorList.size) {
                Toast.makeText(this, "Por favor seleccione un vendedor", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            val selectedVendor = vendorList[selectedPosition]
            Log.d(TAG, "Vendedor seleccionado: ${selectedVendor.name} (${selectedVendor.username})")

            // Guardar información del vendedor
            val sharedPref = getSharedPreferences("ASFPrefs", MODE_PRIVATE)
            with(sharedPref.edit()) {
                putString("NOMBRE_VENDEDOR", selectedVendor.name)
                putString("USERNAME_VENDEDOR", selectedVendor.username)
                apply()
            }

            // Iniciar DrawerActivity
            startDrawerActivity(selectedVendor.name, selectedVendor.username)
        }
    }

    private fun startDrawerActivity(vendorName: String, vendorUsername: String) {
        val intent = Intent(this, DrawerActivity::class.java).apply {
            putExtra("NOMBRE_VENDEDOR", vendorName)
            putExtra("USERNAME_VENDEDOR", vendorUsername)
        }
        startActivity(intent)
        finish()
    }

    // Mantenemos el método original para compatibilidad con versiones anteriores
    private fun startMainActivity(vendorName: String, vendorUsername: String) {
        startDrawerActivity(vendorName, vendorUsername)
    }
}