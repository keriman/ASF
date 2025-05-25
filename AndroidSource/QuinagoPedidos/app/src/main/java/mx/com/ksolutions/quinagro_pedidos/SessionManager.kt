package mx.com.ksolutions.quinagro_pedidos

import android.content.Context

class SessionManager(context: Context) {
    private val prefs = context.getSharedPreferences("QuinagroSession", Context.MODE_PRIVATE)
    private val KEY_IS_LOGGED_IN = "isLoggedIn"
    private val KEY_USER_TYPE = "userType"
    private val KEY_VENDOR_NAME = "vendorName"
    private val KEY_VENDOR_USERNAME = "vendorUsername"

    fun setLoggedIn(isLoggedIn: Boolean) {
        prefs.edit().putBoolean(KEY_IS_LOGGED_IN, isLoggedIn).apply()
    }

    fun isLoggedIn(): Boolean {
        return prefs.getBoolean(KEY_IS_LOGGED_IN, false)
    }

    fun setUserType(type: String) {
        prefs.edit().putString(KEY_USER_TYPE, type).apply()
    }

    fun getUserType(): String? {
        return prefs.getString(KEY_USER_TYPE, null)
    }

    fun setVendorInfo(name: String, username: String) {
        prefs.edit()
            .putString(KEY_VENDOR_NAME, name)
            .putString(KEY_VENDOR_USERNAME, username)
            .apply()
    }

    fun getVendorName(): String? {
        return prefs.getString(KEY_VENDOR_NAME, null)
    }

    fun getVendorUsername(): String? {
        return prefs.getString(KEY_VENDOR_USERNAME, null)
    }

    fun clearSession() {
        prefs.edit().clear().apply()
    }

}