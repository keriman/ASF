package mx.com.ksolutions.quinagro_pedidos

import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.isVisible
import androidx.lifecycle.ViewModelProvider
import com.bumptech.glide.Glide
import mx.com.ksolutions.quinagro_pedidos.databinding.ActivityProfileBinding

class ProfileActivity : AppCompatActivity() {
    private lateinit var binding: ActivityProfileBinding
    private lateinit var viewModel: ProfileViewModel

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityProfileBinding.inflate(layoutInflater)
        setContentView(binding.root)

        viewModel = ViewModelProvider(this)[ProfileViewModel::class.java]

        setupObservers()
        viewModel.loadProfile()
    }

    private fun setupObservers() {
        viewModel.profileData.observe(this) { profileData ->
            updateUI(profileData)
        }

        viewModel.isLoading.observe(this) { isLoading ->
            binding.progressBar.isVisible = isLoading
        }

        viewModel.error.observe(this) { errorMessage ->
            showError(errorMessage)
        }
    }

    private fun updateUI(profile: ProfileData) {
        binding.apply {
            nameInput.setText(profile.nombre ?: "")
            lastNamePInput.setText(profile.apellidoP ?: "")
            lastNameMInput.setText(profile.apellidoM ?: "")
            streetInput.setText(profile.calle ?: "")
            numberInput.setText(profile.numero ?: "")
            coloniaInput.setText(profile.colonia ?: "")
            municipioInput.setText(profile.municipio ?: "")
            estadoInput.setText(profile.estado ?: "")
            phoneInput.setText(profile.celular ?: "")
            emergencyContactInput.setText(profile.contactoEmergencia ?: "")
            emergencyPhoneInput.setText(profile.telefonoEmergencia ?: "")

            profile.foto?.let { photoUrl ->
                Glide.with(this@ProfileActivity)
                    .load(photoUrl)
                    .placeholder(R.mipmap.perfil)
                    .error(R.mipmap.perfil)
                    .into(profileImage)
            }
        }
    }

    private fun showError(message: String) {
        Toast.makeText(this, message, Toast.LENGTH_LONG).show()
    }
}