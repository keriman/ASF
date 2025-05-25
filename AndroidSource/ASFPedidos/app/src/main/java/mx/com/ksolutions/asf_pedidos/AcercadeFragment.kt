package mx.com.ksolutions.asf_pedidos

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import mx.com.ksolutions.asf_pedidos.databinding.FragmentAcercadeBinding

class AcercadeFragment : Fragment() {
    private var _binding: FragmentAcercadeBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAcercadeBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        // Podemos personalizar los datos del desarrollador si es necesario
        // Por ejemplo, obtener la versión de la app
        try {
            val packageInfo = requireContext().packageManager.getPackageInfo(requireContext().packageName, 0)
            binding.tvVersion.text = "Versión ${packageInfo.versionName}"
        } catch (e: Exception) {
            binding.tvVersion.text = "Versión 1.0"
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}