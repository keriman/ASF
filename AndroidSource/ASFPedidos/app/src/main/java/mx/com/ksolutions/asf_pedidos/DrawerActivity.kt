package mx.com.ksolutions.asf_pedidos

import android.os.Bundle
import android.content.Intent
import android.view.MenuItem
import android.widget.TextView
import androidx.appcompat.app.ActionBarDrawerToggle
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import androidx.fragment.app.Fragment
import com.google.android.material.navigation.NavigationView
import mx.com.ksolutions.asf_pedidos.databinding.ActivityDrawerBinding

class DrawerActivity : AppCompatActivity(), NavigationView.OnNavigationItemSelectedListener {
    private lateinit var binding: ActivityDrawerBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDrawerBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Inicializar RetrofitClient PRIMERO
        RetrofitClient.init(this)

        // Inicializar SessionManager
        sessionManager = SessionManager(this)

        // Obtener información del vendedor desde el intent o de preferencias guardadas
        val vendorName = intent.getStringExtra("NOMBRE_VENDEDOR")
            ?: getSharedPreferences("ASFPrefs", MODE_PRIVATE).getString("NOMBRE_VENDEDOR", "")
            ?: ""
        val vendorUsername = intent.getStringExtra("USERNAME_VENDEDOR")
            ?: getSharedPreferences("ASFPrefs", MODE_PRIVATE).getString("USERNAME_VENDEDOR", "")
            ?: ""

        // Guardar información del vendedor en SessionManager
        sessionManager.setVendorInfo(vendorName, vendorUsername)

        // Configurar Toolbar
        setSupportActionBar(binding.toolbar)

        // Configurar DrawerLayout
        val toggle = ActionBarDrawerToggle(
            this,
            binding.drawerLayout,
            binding.toolbar,
            R.string.navigation_drawer_open,
            R.string.navigation_drawer_close
        )
        binding.drawerLayout.addDrawerListener(toggle)
        toggle.syncState()

        // Configurar NavigationView
        binding.navView.setNavigationItemSelectedListener(this)

        // Configurar header del drawer con el nombre del vendedor
        val headerView = binding.navView.getHeaderView(0)
        val tvUserName = headerView.findViewById<TextView>(R.id.tvUserName)
        tvUserName.text = sessionManager.getVendorName()

        // Por defecto, mostrar el fragmento de Registro
        if (savedInstanceState == null) {
            supportFragmentManager.beginTransaction()
                .replace(R.id.content_frame, RegistroFragment())
                .commit()
            binding.navView.setCheckedItem(R.id.nav_registro)
            supportActionBar?.title = "Registro"
        }
    }

    override fun onNavigationItemSelected(item: MenuItem): Boolean {
        // Manejar selección de ítem en el menú
        when (item.itemId) {
            R.id.nav_registro -> {
                loadFragment(RegistroFragment())
                supportActionBar?.title = "Registro"
            }
            R.id.nav_ventas -> {
                loadFragment(VentasFragment())
                supportActionBar?.title = "Ventas"
            }
            R.id.nav_acerca -> {
                loadFragment(AcercadeFragment())
                supportActionBar?.title = "Acerca de"
            }
            R.id.nav_cambiar_vendedor -> {
                // Mostrar diálogo de confirmación
                AlertDialog.Builder(this)
                    .setTitle("Cambiar Vendedor")
                    .setMessage("¿Estás seguro que deseas cambiar de vendedor? Se perderán los datos no guardados.")
                    .setPositiveButton("Sí") { _, _ ->
                        cerrarSesionYVolverALogin()
                    }
                    .setNegativeButton("No", null)
                    .show()

                // Cerrar el drawer después de seleccionar esta opción
                binding.drawerLayout.closeDrawer(GravityCompat.START)
                return true
            }
        }

        // Cerrar el drawer después de seleccionar una opción
        binding.drawerLayout.closeDrawer(GravityCompat.START)
        return true
    }

    private fun loadFragment(fragment: Fragment) {
        supportFragmentManager.beginTransaction()
            .replace(R.id.content_frame, fragment)
            .commit()
    }

    override fun onBackPressed() {
        // Si el drawer está abierto, cerrarlo al presionar atrás
        if (binding.drawerLayout.isDrawerOpen(GravityCompat.START)) {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
        } else {
            // Si no está en el fragmento de registro, ir ahí, de lo contrario, comportamiento normal
            val currentFragment = supportFragmentManager.findFragmentById(R.id.content_frame)
            if (currentFragment !is RegistroFragment) {
                loadFragment(RegistroFragment())
                binding.navView.setCheckedItem(R.id.nav_registro)
                supportActionBar?.title = "Registro"
            } else {
                super.onBackPressed()
            }
        }
    }

    /**
     * Método para cerrar sesión y volver a la pantalla de login
     */
    private fun cerrarSesionYVolverALogin() {
        // Limpiar datos de sesión
        val sharedPref = getSharedPreferences("ASFPrefs", MODE_PRIVATE)
        with(sharedPref.edit()) {
            // No eliminamos completamente las credenciales, solo marcamos que se debe mostrar el login
            putBoolean("MOSTRAR_LOGIN", true)
            apply()
        }

        // Volver a la pantalla de login
        val intent = Intent(this, LoginActivity::class.java)
        // Limpiar el stack de actividades para que el usuario no pueda volver atrás
        intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        startActivity(intent)
        finish()
    }
}