package mx.com.ksolutions.quinagro_pedidos

import android.content.Context
import android.util.Log
import com.google.gson.GsonBuilder
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl

object RetrofitClient {
        private const val BASE_URL = "https://santafe.ngrok.app/PedidosASF/API_Quinagro/"
    private lateinit var sessionManager: SessionManager

    // Implementación personalizada de PersistentCookieJar
    class PersistentCookieJar(private val context: Context) : CookieJar {
        private val TAG = "PersistentCookieJar"
        private val cookiePrefs = context.getSharedPreferences("COOKIE_PREFS", Context.MODE_PRIVATE)
        private val cookieMap = mutableMapOf<String, MutableList<Cookie>>()

        init {
            // Cargar cookies guardadas al iniciar
            loadCookies()
        }

        override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
            // Guardar cookies por dominio
            val host = url.host
            val cookiesForHost = cookieMap.getOrPut(host) { mutableListOf() }

            // Añadir nuevas cookies o actualizar existentes
            for (cookie in cookies) {
                // Eliminar cookie existente con el mismo nombre
                val existingCookieIndex = cookiesForHost.indexOfFirst { it.name == cookie.name }
                if (existingCookieIndex != -1) {
                    cookiesForHost.removeAt(existingCookieIndex)
                }
                cookiesForHost.add(cookie)

                // Guardar en SharedPreferences
                val cookieString = encodeCookie(cookie)
                cookiePrefs.edit().putString("${host}_${cookie.name}", cookieString).apply()

                Log.d(TAG, "Guardada cookie: ${cookie.name}=${cookie.value} para $host")
            }
        }

        override fun loadForRequest(url: HttpUrl): List<Cookie> {
            val host = url.host
            val cookies = cookieMap[host] ?: mutableListOf()

            // Eliminar cookies expiradas
            val iterator = cookies.iterator()
            while (iterator.hasNext()) {
                val cookie = iterator.next()
                if (cookie.expiresAt < System.currentTimeMillis()) {
                    iterator.remove()
                    cookiePrefs.edit().remove("${host}_${cookie.name}").apply()
                    Log.d(TAG, "Cookie expirada eliminada: ${cookie.name}")
                }
            }

            Log.d(TAG, "Enviando ${cookies.size} cookies para $host")
            return cookies
        }

        private fun loadCookies() {
            val allCookies = cookiePrefs.all
            for ((key, value) in allCookies) {
                try {
                    val parts = key.split("_", limit = 2)
                    if (parts.size == 2) {
                        val host = parts[0]
                        val cookieString = value as String
                        val cookie = decodeCookie(cookieString)

                        cookie?.let {
                            val cookiesForHost = cookieMap.getOrPut(host) { mutableListOf() }
                            cookiesForHost.add(it)
                            Log.d(TAG, "Cargada cookie: ${it.name}=${it.value} para $host")
                        }
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "Error al cargar cookie: $e")
                    // Eliminar cookie corrupta
                    cookiePrefs.edit().remove(key).apply()
                }
            }

            Log.d(TAG, "Cargadas ${cookieMap.values.sumOf { it.size }} cookies guardadas")
        }

        private fun encodeCookie(cookie: Cookie): String {
            return try {
                val builder = StringBuilder()
                builder.append(cookie.name).append("=").append(cookie.value).append(";")

                if (cookie.expiresAt > 0) {
                    builder.append(" expires=").append(cookie.expiresAt).append(";")
                }

                builder.append(" domain=").append(cookie.domain).append(";")
                builder.append(" path=").append(cookie.path).append(";")

                if (cookie.secure) {
                    builder.append(" secure;")
                }

                if (cookie.httpOnly) {
                    builder.append(" httponly;")
                }

                builder.toString()
            } catch (e: Exception) {
                Log.e(TAG, "Error al codificar cookie: $e")
                ""
            }
        }

        private fun decodeCookie(cookieString: String): Cookie? {
            return try {
                // Formato ejemplo: name=value; domain=example.com; path=/; expires=12345678
                val mainParts = cookieString.split(";", limit = 2)
                if (mainParts.isEmpty()) return null

                val nameValue = mainParts[0].split("=", limit = 2)
                if (nameValue.size != 2) return null

                val name = nameValue[0].trim()
                val value = nameValue[1].trim()

                // Extraer otros parámetros
                var domain = ""
                var path = "/"
                var expiresAt = 0L
                var secure = false
                var httpOnly = false

                val parts = if (mainParts.size > 1) mainParts[1].split(";") else emptyList()
                for (part in parts) {
                    val trimmedPart = part.trim()
                    when {
                        trimmedPart.startsWith("domain=") -> {
                            domain = trimmedPart.substring(7).trim()
                        }
                        trimmedPart.startsWith("path=") -> {
                            path = trimmedPart.substring(5).trim()
                        }
                        trimmedPart.startsWith("expires=") -> {
                            val expString = trimmedPart.substring(8).trim()
                            expiresAt = expString.toLongOrNull() ?: 0L
                        }
                        trimmedPart == "secure" -> {
                            secure = true
                        }
                        trimmedPart == "httponly" -> {
                            httpOnly = true
                        }
                    }
                }

                // Si no se encontró un dominio, no podemos crear la cookie
                if (domain.isEmpty()) return null

                val builder = Cookie.Builder()
                    .name(name)
                    .value(value)
                    .domain(domain)
                    .path(path)

                if (expiresAt > 0) {
                    builder.expiresAt(expiresAt)
                }

                if (secure) {
                    builder.secure()
                }

                // httpOnly no se puede establecer en Cookie.Builder

                builder.build()
            } catch (e: Exception) {
                Log.e(TAG, "Error al decodificar cookie: $e")
                null
            }
        }

        fun clearCookies() {
            cookieMap.clear()
            cookiePrefs.edit().clear().apply()
            Log.d(TAG, "Todas las cookies eliminadas")
        }
    }

    // Importante: cambiamos de lateinit a lazy para evitar problemas de inicialización
    private var cookieJar: PersistentCookieJar? = null

    private val loggingInterceptor = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BODY
    }

    private val responseLoggingInterceptor = Interceptor { chain ->
        val request = chain.request()
        val response = chain.proceed(request)

        // Log de la respuesta completa
        Log.d("RetrofitClient", "URL: ${request.url}")
        Log.d("RetrofitClient", "Response Code: ${response.code}")
        Log.d("RetrofitClient", "Response Body: ${response.peekBody(Long.MAX_VALUE).string()}")

        response
    }

    private val headerInterceptor = Interceptor { chain ->
        val original = chain.request()
        val requestBuilder = original.newBuilder()
            .header("Content-Type", "application/x-www-form-urlencoded")
            .method(original.method, original.body)

        chain.proceed(requestBuilder.build())
    }

    // Creación perezosa del cliente OkHttp para evitar el error de inicialización
    private val okHttpClient by lazy {
        OkHttpClient.Builder()
            .addInterceptor(loggingInterceptor)
            .addInterceptor(responseLoggingInterceptor)
            .addInterceptor(headerInterceptor)
            .cookieJar(cookieJar ?: throw IllegalStateException("RetrofitClient must be initialized before use"))
            .connectTimeout(60, TimeUnit.SECONDS)
            .readTimeout(60, TimeUnit.SECONDS)
            .writeTimeout(60, TimeUnit.SECONDS)
            .build()
    }

    // Creación perezosa de la API para evitar el error de inicialización
    val apiService: ApiService by lazy {
        val gson = GsonBuilder()
            .setLenient()
            .create()

        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(ApiService::class.java)
    }

    fun init(context: Context) {
        sessionManager = SessionManager(context)
        cookieJar = PersistentCookieJar(context)
    }

    fun clearCookies() {
        cookieJar?.clearCookies()
    }

    fun getSessionManager(): SessionManager {
        if (!::sessionManager.isInitialized) {
            throw IllegalStateException("RetrofitClient must be initialized before use")
        }
        return sessionManager
    }
}