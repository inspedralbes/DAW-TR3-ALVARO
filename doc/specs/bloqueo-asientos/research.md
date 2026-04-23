# Research: Bloqueo de Asientos en Tiempo Real

## Comunicación Entre Servicios (Laravel → Node.js)

- **Decisión**: Redis Pub/Sub.
- **Racional**: Laravel tiene soporte nativo para eventos y broadcasting mediante Redis. Node.js (Socket.IO) puede suscribirse de forma eficiente al canal de Redis y retransmitir los mensajes a los clientes web. Esto evita acoplamiento HTTP directo y escala mejor.
- **Alternativas consideradas**:
  - **Llamadas HTTP directas**: Sencillo para prototipos, pero añade latencia y complejidad de gestión de timeouts en el proceso de reserva.
  - **Polling en Node.js**: Descartado por ineficiencia (demasiadas lecturas a DB).

## Gestión de Concurrencia (Race Conditions)

- **Decisión**: Atomic Updates con condiciones.
- **Racional**: La query `UPDATE seats SET status = 'reserved', ... WHERE id = ? AND status = 'available'` garantiza que solo el primer request que encuentre el asiento disponible lo modifique. El driver de SQL (MySQL/MariaDB) gestiona el bloqueo de fila de forma nativa.
- **Alternativas consideradas**:
  - **SELECT FOR UPDATE**: Útil si se requiere lógica de negocio compleja antes del update, pero para una reserva simple, el atomic update es más rápido.

## Identificación de Sesión y Seguridad WS

- **Decisión**: `session_id` persistido en LocalStorage/Cookies y enviado en el handshake de Socket.IO.
- **Racional**: Permite que el servidor de Node identifique qué cliente tiene qué asiento sin necesidad de una sesión de Laravel completa (que requeriría compartir estados de sesión complejos entre PHP y JS).
- **Alternativas consideradas**:
  - **JWT**: Ideal para sistemas con usuarios registrados, pero excesivo para un sistema de reserva por sesión anónima.

## Limpieza de Reservas Expiradas

- **Decisión**: Laravel Scheduler (cron).
- **Racional**: Laravel centraliza la lógica de negocio y tiene acceso directo a la base de datos de asientos. Un comando `php artisan seats:release-expired` corriendo cada minuto es suficiente.
- **Alternativas consideradas**:
  - **Node.js cron**: Duplicaría lógica de conexión a DB y modelos.
  - **Redis EXPIRE**: Los asientos son filas de SQL, no claves simples de Redis, por lo que el TTL nativo de Redis no es aplicable directamente sin desincronización.
