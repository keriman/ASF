package mx.com.ksolutions.quinagro_pedidos

import android.app.Application
import androidx.appcompat.app.AppCompatDelegate

class ASFApplication : Application() {
    override fun onCreate() {
        super.onCreate()

        // Forzar modo oscuro en toda la aplicación
        AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_YES)
    }
}