const express = require("express");
const http = require("http");
const { Server } = require("socket.io");
const cors = require("cors");
const Redis = require("ioredis");
const db = require("./db");

const app = express();
app.use(cors());
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
  },
});

// Configuración de Redis
const redis = new Redis({
  host: process.env.REDIS_HOST || "127.0.0.1",
  port: process.env.REDIS_PORT || 6379,
});

// Suscribirse a los canales de Laravel
const REDIS_PREFIX = "laravel-database-";
redis.subscribe(`${REDIS_PREFIX}seats`, (err, count) => {
  if (err) {
    console.error("Error al suscribirse a Redis:", err.message);
  } else {
    console.log(`Suscrito a ${count} canales de Redis (${REDIS_PREFIX}seats).`);
  }
});

// Mapa en memoria para el temporizador de bloques (Evitamos dependencias de CRON en DB)
const activeTimers = new Map();

redis.on("message", (channel, message) => {
  console.log(`Mensaje recibido en canal ${channel}`);

  try {
    const rawData = JSON.parse(message);

    // Laravel broadcast structure wraps the object
    if (
      rawData.event === "seat_updated" ||
      rawData.event === "App\\Events\\SeatUpdated"
    ) {
      const payload = rawData.data || {};
      console.log("Emitiendo seat_updated a todos los clientes:", payload);

      // Sincronizar clientes
      io.emit("seat_updated", payload);

      const seatId = payload.seat_id;
      const status = payload.status;

      // Limpiar timeout si este mismo asiento reingresa a Redis por confirmación "sold" o "available"
      if (activeTimers.has(seatId)) {
        clearTimeout(activeTimers.get(seatId));
        activeTimers.delete(seatId);
        console.log(
          `Timer cancelado para asiento ${seatId} porque ha cambiado a status: ${status}`,
        );
      }

      // Si el evento fue que se "Reservó"
      if (status === "reserved") {
        const timeoutId = setTimeout(async () => {
          console.log(
            `[TIMEOUT 15s] Timer expirado para asiento ${seatId}. Verificando BBDD...`,
          );
          try {
            // Revisamos en vivo localmente si sigue reservado
            const [rows] = await db.query(
              "SELECT status FROM seats WHERE id = ?",
              [seatId],
            );
            if (rows.length > 0 && rows[0].status === "reserved") {
              console.log(
                `Asiento ${seatId} seguía bloqueado. Forzando liberación (Timeout Inactividad).`,
              );

              await db.query(
                'UPDATE seats SET status = "available", session_id = NULL, user_id = NULL, reserved_at = NULL WHERE id = ?',
                [seatId],
              );

              // Disparamos evento inverso a los WebSockets sin pasar por backend PHP
              io.emit("seat_updated", {
                seat_id: seatId,
                status: "available",
                session_id: null,
              });
            }
          } catch (err) {
            console.error("Error DB MySQL Timeout:", err.message);
          } finally {
            activeTimers.delete(seatId);
          }
        }, 600000); // 10 MINUTOS

        // Registrar timer activo
        activeTimers.set(seatId, timeoutId);
      }
    }
  } catch (error) {
    console.error("Error fatal procesando mensaje desde Redis:", error.message);
  }
});

// Ruta de health check para validar conexión a DB
app.get("/api/health", async (req, res) => {
  try {
    const [rows] = await db.query("SELECT 1");
    res.json({
      status: "ok",
      db: "connected",
      message: "Node.js y DB conectados",
    });
  } catch (error) {
    console.error("Error DB:", error);
    res
      .status(500)
      .json({ status: "error", db: "disconnected", error: error.message });
  }
});

// Manejo de conexiones WebSocket
io.on("connection", (socket) => {
  console.log(`Cliente Nuxt conectado: ${socket.id}`);

  socket.on("disconnect", () => {
    console.log(`Cliente desconectado: ${socket.id}`);
  });
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
  console.log(
    `🚀 Servidor de Tiempo libre (Node.js) enlazado al puerto ${PORT}`,
  );
});
