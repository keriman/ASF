package mx.com.ksolutions.asf_pedidos

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch

class VentasViewModel(private val sessionManager: SessionManager) : ViewModel() {
    // Declaración de todas las variables LiveData necesarias
    private val _isLoading = MutableLiveData<Boolean>()
    val isLoading: LiveData<Boolean> = _isLoading

    private val _errorMessage = MutableLiveData<String>()
    val errorMessage: LiveData<String> = _errorMessage

    private val _ventas = MutableLiveData<List<ApiService.Venta>>()
    val ventas: LiveData<List<ApiService.Venta>> = _ventas

    private val _pagination = MutableLiveData<ApiService.Paginacion?>()
    val pagination: LiveData<ApiService.Paginacion?> = _pagination

    // Inicialización del repositorio
    private val repository = VentasRepository()

    private var currentPage = 1
    private val perPage = 10

    fun loadVentas(page: Int = 1) {
        currentPage = page
        _isLoading.value = true

        viewModelScope.launch {
            try {
                val username = sessionManager.getVendorName()
                if (username.isNullOrEmpty()) {
                    _errorMessage.postValue("No se ha seleccionado un vendedor válido")
                    _isLoading.postValue(false)
                    return@launch
                }

                val (ventasList, pagination) = repository.getVentas(username, page, perPage)
                _ventas.postValue(ventasList)
                _pagination.postValue(pagination)
                _isLoading.postValue(false)
            } catch (e: Exception) {
                _errorMessage.postValue("Error: ${e.message ?: "Desconocido"}")
                _isLoading.postValue(false)
            }
        }
    }

    fun loadNextPage() {
        _pagination.value?.let {
            if (currentPage < it.total_paginas) {
                loadVentas(currentPage + 1)
            }
        }
    }

    fun loadPreviousPage() {
        if (currentPage > 1) {
            loadVentas(currentPage - 1)
        }
    }

    fun clearErrorMessage() {
        _errorMessage.value = ""
    }
}

class VentasViewModelFactory(private val sessionManager: SessionManager) : ViewModelProvider.Factory {
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(VentasViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return VentasViewModel(sessionManager) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}