const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const WebSocket = require('ws');
const app = express();
const port = 3000;

app.use(cors());
app.use(express.json());

mongoose.connect('mongodb://127.0.0.1:27017/fertilizer_factory');

const orderSchema = new mongoose.Schema({
  fecha: Date,
  numeroPedido: String,
  producto: String,
  presentacion: String,
  cantidad: Number,
  confirmado: { type: Number, default: 0 },
  createdAt: { type: Date, default: Date.now }
});

orderSchema.index({ confirmado: 1, createdAt: 1 });

const Order = mongoose.model('Order', orderSchema);

// Función para eliminar pedidos confirmados antiguos
async function deleteOldConfirmedOrders() {
  const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000);
  try {
    const result = await Order.deleteMany({
      confirmado: 1,
      createdAt: { $lt: twoHoursAgo }
    });
    console.log(`${result.deletedCount} pedidos antiguos eliminados`);
  } catch (error) {
    console.error('Error al eliminar pedidos antiguos:', error);
  }
}

setInterval(deleteOldConfirmedOrders, 1 * 60 * 1000);


const wss = new WebSocket.Server({ port: 8080, host: '0.0.0.0' });

wss.on('connection', (ws) => {
  console.log('Client connected to WebSocket');

  ws.on('message', async (message) => {
    try {
      const orderData = JSON.parse(message);

      const newOrder = new Order({
        fecha: new Date(orderData.fecha),
        numeroPedido: orderData.numeroPedido,
        producto: orderData.producto,
        presentacion: orderData.presentacion,
        cantidad: orderData.cantidad,
        confirmado: 0
      });

      await newOrder.save();
      console.log('Order saved to database');
      wss.clients.forEach((client) => {
        if (client !== ws && client.readyState === WebSocket.OPEN) {
          client.send(JSON.stringify(newOrder));
        }
      });
    } catch (error) {
      console.error('Error processing order:', error);
      ws.send(JSON.stringify({ error: 'Invalid order data or database error' }));
    }
  });

  ws.on('error', (error) => {
    console.error('WebSocket error:', error);
  });

  ws.on('close', () => {
    console.log('Client disconnected from WebSocket');
  });
});


app.get('/api/orders', async (req, res) => {
  try {
    const orders = await Order.find().sort({ createdAt: -1 });
    res.json(orders);
  } catch (error) {
    console.error('Error fetching orders:', error);
    res.status(500).json({ message: 'Error fetching orders from database' });
  }
});

app.put('/api/orders/:orderId/toggleConfirmation', async (req, res) => {
  try {
    const orderId = req.params.orderId;
    const { confirmado } = req.body;

    const updatedOrder = await Order.findByIdAndUpdate(
      orderId,
      { confirmado: confirmado },
      { new: true }
    );

    if (!updatedOrder) {
      return res.status(404).json({ success: false, message: 'Orden no encontrada' });
    }

    res.json({ success: true, message: 'Estado de confirmación actualizado', order: updatedOrder });
  } catch (error) {
    console.error('Error al actualizar el estado de confirmación:', error);
    res.status(500).json({ success: false, message: 'Error al actualizar el estado de confirmación' });
  }
});

app.listen(port, () => {
  console.log(`Backend server running at http://localhost:${port}`);
});
