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

  // Permite solicitar activamente cuántos hay al entrar a un dashboard
  socket.on("request_clients_count", () => {
    socket.emit("clients_count", io.engine.clientsCount);
  });

  // --- WebRTC (1 a 1 en Directe) ---
  socket.on("support_request", (data) => {
    console.log(`[WebRTC] Client request support: ${socket.id}`);
    io.emit("support_request_received", {
      userId: socket.id,
      message: "Sol·licita assistència de pagament",
      userName: data?.userName || "Client Anònim",
    });
  });

  socket.on("support_cancel", () => {
    console.log(`[WebRTC] Client cancelled support request: ${socket.id}`);
    io.emit("support_request_cancelled", { userId: socket.id });
  });

  socket.on("support_accept", (data) => {
    console.log(
      `[WebRTC] Admin ${socket.id} accepted call from User ${data.targetId}`,
    );
    // Informem a l'usuari que ha estat acceptat
    io.to(data.targetId).emit("support_accepted", { adminId: socket.id });
    // Informem a la resta d'admins que ja no hi ha sol·licitud
    socket.broadcast.emit("support_request_handled", {
      userId: data.targetId,
      adminId: socket.id,
    });
  });

  socket.on("webrtc_offer", (payload) => {
    console.log(`[WebRTC] Offer from ${socket.id} to ${payload.target}`);
    io.to(payload.target).emit("webrtc_offer", {
      sdp: payload.sdp,
      caller: socket.id,
    });
  });

  socket.on("webrtc_answer", (payload) => {
    console.log(`[WebRTC] Answer from ${socket.id} to ${payload.target}`);
    io.to(payload.target).emit("webrtc_answer", {
      sdp: payload.sdp,
      answerer: socket.id,
    });
  });

  socket.on("webrtc_ice_candidate", (payload) => {
    io.to(payload.target).emit("webrtc_ice_candidate", {
      candidate: payload.candidate,
      sender: socket.id,
    });
  });

  socket.on("webrtc_end", (payload) => {
    console.log(`[WebRTC] Call ended by ${socket.id} to ${payload.target}`);
    io.to(payload.target).emit("webrtc_ended", { sender: socket.id });
  });
  // ----------------------------------------------

  socket.on("disconnect", () => {
    console.log(`Cliente desconectado: ${socket.id}`);
    io.emit("clients_count", io.engine.clientsCount);
    // Notifiquem
    io.emit("support_request_cancelled", { userId: socket.id });
    io.emit("webrtc_ended", { sender: socket.id });
  });
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
  console.log(
    `🚀 Servidor de Tiempo libre (Node.js) enlazado al puerto ${PORT}`,
  );
});
