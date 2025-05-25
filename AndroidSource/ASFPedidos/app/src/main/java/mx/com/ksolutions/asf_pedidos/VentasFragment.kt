package mx.com.ksolutions.asf_pedidos

import android.os.Bundle
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import androidx.recyclerview.widget.DividerItemDecoration
import androidx.recyclerview.widget.LinearLayoutManager
import mx.com.ksolutions.asf_pedidos.databinding.FragmentVentasBinding

class VentasFragment : Fragment() {
    private val TAG = "VentasFragment"

    private var _binding: FragmentVentasBinding? = null
    private val binding get() = _binding!!

    private lateinit var viewModel: VentasViewModel
    private lateinit var adapter: VentasAdapter
    private lateinit var sessionManager: SessionManager

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentVentasBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        Log.d(TAG, "onViewCreated called")

        try {
            // Inicializar SessionManager
            sessionManager = SessionManager(requireContext())

            // Configurar RecyclerView
            setupRecyclerView()

            // Inicializar ViewModel
            val factory = VentasViewModelFactory(sessionManager)
            viewModel = ViewModelProvider(this, factory)[VentasViewModel::class.java]

            // Configurar observadores
            setupObservers()

            // Configurar listeners de paginación
            setupListeners()

            // Cargar ventas
            viewModel.loadVentas()
        } catch (e: Exception) {
            Log.e(TAG, "Error al inicializar", e)
            Toast.makeText(requireContext(), "Error al inicializar: ${e.message}", Toast.LENGTH_LONG).show()
        }
    }

    private fun setupObservers() {
        viewModel.isLoading.observe(viewLifecycleOwner) { isLoading ->
            binding.progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
            if (isLoading) {
                binding.tvNoVentas.visibility = View.GONE
            }
        }

        viewModel.errorMessage.observe(viewLifecycleOwner) { errorMessage ->
            if (errorMessage.isNotEmpty()) {
                showError(errorMessage)
                viewModel.clearErrorMessage()
            }
        }

        viewModel.ventas.observe(viewLifecycleOwner) { ventas ->
            adapter.setVentas(ventas)

            if (ventas.isEmpty()) {
                binding.tvNoVentas.visibility = View.VISIBLE
                binding.rvVentas.visibility = View.GONE
            } else {
                binding.tvNoVentas.visibility = View.GONE
                binding.rvVentas.visibility = View.VISIBLE
            }
        }

        viewModel.pagination.observe(viewLifecycleOwner) { pagination ->
            pagination?.let {
                binding.tvPageInfo.text = "Página ${it.pagina_actual} de ${it.total_paginas}"

                // Habilitar/deshabilitar botones de paginación
                binding.btnPrevious.isEnabled = it.pagina_actual > 1
                binding.btnNext.isEnabled = it.pagina_actual < it.total_paginas
            }
        }
    }

    private fun setupListeners() {
        binding.btnNext.setOnClickListener {
            viewModel.loadNextPage()
        }

        binding.btnPrevious.setOnClickListener {
            viewModel.loadPreviousPage()
        }

        binding.btnRetry.setOnClickListener {
            viewModel.loadVentas()
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    private fun setupRecyclerView() {
        adapter = VentasAdapter(
            onClick = { venta ->
                showVentaOptionsDialog(venta)
            },
            onFolioClick = { folio ->
                navigateToPedidoDetalles(folio)
            }
        )

        binding.rvVentas.apply {
            layoutManager = LinearLayoutManager(requireContext())
            addItemDecoration(DividerItemDecoration(requireContext(), DividerItemDecoration.VERTICAL))
            adapter = this@VentasFragment.adapter
        }
    }

    private fun showVentaOptionsDialog(venta: ApiService.Venta) {
        val options = arrayOf("Ver detalles", "Cancelar")

        AlertDialog.Builder(requireContext())
            .setTitle("Pedido: ${venta.folio}")
            .setItems(options) { dialog, which ->
                when (which) {
                    0 -> {
                        try {
                            val folio = venta.folio.toInt()
                            navigateToPedidoDetalles(folio)
                        } catch (e: NumberFormatException) {
                            Toast.makeText(requireContext(), "Folio inválido", Toast.LENGTH_SHORT).show()
                        }
                    }
                }
                dialog.dismiss()
            }
            .show()
    }

    private fun navigateToPedidoDetalles(folio: Int) {
        try {
            Log.d(TAG, "Navegando a detalles de pedido con folio: $folio")

            // En lugar de usar un fragmento, iniciamos la actividad dedicada
            val intent = PedidoDetallesActivity.getIntent(requireContext(), folio)
            startActivity(intent)

            Log.d(TAG, "Actividad de detalles iniciada")
        } catch (e: Exception) {
            Log.e(TAG, "Error al navegar a detalles de pedido", e)
            showError("No se pudo abrir los detalles del pedido: ${e.message}")
        }
    }

    private fun showError(message: String) {
        Log.e(TAG, "Error: $message")
        AlertDialog.Builder(requireContext())
            .setTitle("Error")
            .setMessage(message)
            .setPositiveButton("Aceptar", null)
            .show()
    }
}