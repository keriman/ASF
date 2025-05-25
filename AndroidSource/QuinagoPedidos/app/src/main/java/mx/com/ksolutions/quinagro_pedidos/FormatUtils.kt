package mx.com.ksolutions.quinagro_pedidos

import java.text.ParseException
import java.text.SimpleDateFormat
import java.util.*

/**
 * Clase de utilidad para formatear datos
 */
object FormatUtils {

    /**
     * Formatea una fecha de BD (YYYY-MM-DD) a formato legible (DD/MM/YYYY)
     */
    fun formatDate(dateStr: String): String {
        return try {
            val inputFormat = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
            val outputFormat = SimpleDateFormat("dd/MM/yyyy", Locale.getDefault())

            val date = inputFormat.parse(dateStr)
            date?.let { outputFormat.format(it) } ?: dateStr
        } catch (e: ParseException) {
            // Si hay un error de formato, devolvemos la cadena original
            dateStr
        }
    }

    /**
     * Obtiene el estado del pedido en formato legible
     */
    fun getStatusText(statusCode: Int): String {
        return when (statusCode) {
            10 -> "Pendiente"
            20 -> "En proceso"
            30 -> "Completado"
            40 -> "Cancelado"
            else -> "Desconocido"
        }
    }

    /**
     * Obtiene el color según el estado del pedido
     * Los colores se devuelven en formato hexadecimal
     */
    fun getStatusColor(statusCode: Int): String {
        return when (statusCode) {
            10 -> "#FFA500" // Naranja para pendiente
            20 -> "#1E90FF" // Azul para en proceso
            30 -> "#32CD32" // Verde para completado
            40 -> "#FF6347" // Rojo para cancelado
            else -> "#808080" // Gris para desconocido
        }
    }

    /**
     * Formatea la hora de la base de datos (HH:MM:SS) a formato legible (HH:MM)
     */
    fun formatTime(timeStr: String): String {
        return try {
            val inputFormat = SimpleDateFormat("HH:mm:ss", Locale.getDefault())
            val outputFormat = SimpleDateFormat("HH:mm", Locale.getDefault())

            val time = inputFormat.parse(timeStr)
            time?.let { outputFormat.format(it) } ?: timeStr
        } catch (e: ParseException) {
            // Si hay un error de formato, devolvemos la cadena original
            timeStr
        }
    }

    /**
     * Formatea una fecha y hora completa de BD (YYYY-MM-DD HH:MM:SS) a formato legible
     */
    fun formatDateTime(dateTimeStr: String): String {
        return try {
            val inputFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
            val outputFormat = SimpleDateFormat("dd/MM/yyyy HH:mm", Locale.getDefault())

            val dateTime = inputFormat.parse(dateTimeStr)
            dateTime?.let { outputFormat.format(it) } ?: dateTimeStr
        } catch (e: ParseException) {
            // Si hay un error de formato, devolvemos la cadena original
            dateTimeStr
        }
    }
}