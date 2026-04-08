const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const Redis = require('ioredis');
const db = require('./db');

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST']
  }
});

// Configuración de Redis
const redis = new Redis({
  host: process.env.REDIS_HOST || '127.0.0.1',
  port: process.env.REDIS_PORT || 6379,
});

// Suscribirse a los canales de Laravel
// El prefijo por defecto de Laravel es laravel-database- (slug del APP_NAME)
const REDIS_PREFIX = 'laravel-database-';
redis.subscribe(`${REDIS_PREFIX}seats`, (err, count) => {
  if (err) {
    console.error('Error al suscribirse a Redis:', err.message);
  } else {
    console.log(`Suscrito a ${count} canales de Redis.`);
  }
});

redis.on('message', (channel, message) => {
  console.log(`Mensaje recibido en canal ${channel}`);
  
  try {
    const data = JSON.parse(message);
    // Laravel envía el evento envuelto en un objeto con 'event' y 'data'
    if (data.event === 'seat_updated') {
      console.log('Emitiendo seat_updated a todos los clientes:', data.data);
      io.emit('seat_updated', data.data);
    }
  } catch (error) {
    console.error('Error al procesar mensaje de Redis:', error.message);
  }
});

// Ruta de health check para validar conexión a DB
app.get('/api/health', async (req, res) => {
  try {
    const [rows] = await db.query('SELECT 1');
    res.json({ status: 'ok', db: 'connected', message: 'API funcionando correctamente' });
  } catch (error) {
    console.error('Error DB:', error);
    res.status(500).json({ status: 'error', db: 'disconnected', error: error.message });
  }
});

// Manejo de conexiones WebSocket
io.on('connection', (socket) => {
  console.log(`Cliente conectado: ${socket.id}`);

  socket.on('disconnect', () => {
    console.log(`Cliente desconectado: ${socket.id}`);
  });
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
  console.log(`🚀 Servidor WebSocket ejecutándose en el puerto ${PORT}`);
});
