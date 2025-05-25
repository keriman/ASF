package mx.com.ksolutions.asf_pedidos

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.launch

class ProfileViewModel : ViewModel() {
    private val repository = ProfileRepository()

    private val _profileData = MutableLiveData<ProfileData>()
    val profileData: LiveData<ProfileData> = _profileData

    private val _isLoading = MutableLiveData<Boolean>()
    val isLoading: LiveData<Boolean> = _isLoading

    private val _error = MutableLiveData<String>()
    val error: LiveData<String> = _error

    fun loadProfile() {
        viewModelScope.launch {
            try {
                _isLoading.value = true
                val response = repository.getProfile()

                if (response.isSuccessful) {
                    response.body()?.let { profileResponse ->
                        if (!profileResponse.error) {
                            profileResponse.data?.let {
                                _profileData.value = it
                            } ?: run {
                                _error.value = "Datos de perfil no disponibles"
                            }
                        } else {
                            _error.value = profileResponse.message ?: "Error desconocido"
                        }
                    } ?: run {
                        _error.value = "Respuesta vacía del servidor"
                    }
                } else {
                    _error.value = "Error: ${response.code()} - ${response.message()}"
                }
            } catch (e: Exception) {
                _error.value = e.message ?: "Error desconocido"
            } finally {
                _isLoading.value = false
            }
        }
    }
}