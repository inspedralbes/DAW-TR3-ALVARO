# Tasks: Bloqueo de Asientos en Tiempo Real

**Input**: Design documents from `doc/specs/bloqueo-asientos/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Se incluyen tests de Pest (Backend) siguiendo el mandato de TDD de la Constitución.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir la implementación y pruebas independientes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos diferentes, sin dependencias).
- **[Story]**: A qué historia de usuario pertenece (US1, US2, US3, US4).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Inicialización de los tres proyectos y configuración de Redis.

- [X] T001 [P] Configurar Redis en el entorno (Docker/Local) para Pub/Sub
- [X] T002 [P] Instalar dependencias de Socket.IO en `websockets/package.json`
- [X] T003 [P] Configurar el driver de Broadcast en `api/.env` a `redis`
- [X] T004 [P] Instalar `socket.io-client` en `frontend/package.json`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestructura básica de base de datos y modelos compartidos.

- [X] T005 Crear migración para la tabla `seats` en `api/database/migrations/` (según data-model.md)
- [X] T006 Implementar modelo `Seat` en `api/app/Models/Seat.php` con casts para `status`
- [X] T007 [P] Configurar conexión a DB en `websockets/db.js` (lectura compartida si es necesario)
- [X] T008 [P] Crear el plugin de Socket.IO en `frontend/plugins/socket.io.client.ts`
- [X] T009 [P] Inicializar el store de Pinia en `frontend/stores/seatStore.ts`

**Checkpoint**: Base lista - la implementación de historias de usuario puede comenzar.

---

## Phase 3: User Story 1 - Bloqueo Exitoso (Priority: P1) 🎯 MVP

**Goal**: Permitir que un usuario reserve un asiento y se notifique a los demás.

**Independent Test**: Ejecutar test de Pest para reserva y verificar emisión de evento en consola de Node.

### Tests for User Story 1 (TDD)

- [X] T010 [US1] Crear test de integración `api/tests/Feature/SeatReservationTest.php` (debe fallar)

### Implementation for User Story 1

- [X] T011 [US1] Implementar logic de reserva atómica en `api/app/Http/Controllers/SeatController.php`
- [X] T012 [US1] Crear Evento `SeatUpdated` en `api/app/Events/SeatUpdated.php` (Broadcast a Redis)
- [X] T013 [US1] Configurar el servidor Node.js en `websockets/server.js` para escuchar Redis y emitir vía Socket.IO
- [X] T014 [US1] Crear componente `frontend/components/SeatMap.vue` que consuma el `seatStore`
- [X] T015 [US1] Implementar acción `reserveSeat` en `frontend/stores/seatStore.ts` llamando a la API
- [X] T016 [US1] Escuchar evento `seat_updated` en el store y actualizar estado local

**Checkpoint**: US1 funcional - Un usuario bloquea, todos ven el cambio.

---

## Phase 4: User Story 2 - Carrera de Ratas / Concurrencia (Priority: P1)

**Goal**: Validar que dos usuarios no puedan reservar el mismo asiento a la vez.

**Independent Test**: Ejecutar dos requests simultáneos al API; uno debe dar 200 y otro 409.

### Tests for User Story 2

- [X] T017 [US2] Añadir caso de prueba de concurrencia en `api/tests/Feature/SeatReservationTest.php`

### Implementation for User Story 2

- [X] T018 [US2] Refinar manejo de excepciones en `SeatController.php` para retornar error 409 en fallo de update
- [X] T019 [US2] Implementar feedback visual de error (Toast/Alert) en `frontend/components/SeatMap.vue`

---

## Phase 5: User Story 3 - Expiración del Bloqueo (Priority: P2)

**Goal**: Liberar asientos automáticamente tras 5 minutos.

**Independent Test**: Ejecutar el comando artisan y verificar que los asientos `reserved` antiguos pasan a `available`.

### Tests for User Story 3

- [X] T020 [US3] Crear test unitario para el comando de limpieza en `api/tests/Feature/SeatCleanupTest.php`

### Implementation for User Story 3

- [X] T021 [US3] Crear comando `php artisan seats:release-expired` en `api/app/Console/Commands/ReleaseExpiredSeats.php`
- [X] T022 [US3] Registrar el comando en el scheduler de `api/routes/console.php` para ejecución cada minuto
- [X] T023 [US3] Asegurar que el comando también dispare el evento `SeatUpdated` para cada asiento liberado

---

## Phase 6: User Story 4 - Confirmación de Pago (Priority: P2)

**Goal**: Consolidar la venta del asiento.

**Independent Test**: Llamar al endpoint de checkout y verificar estado `sold` en DB.

### Implementation for User Story 4

- [X] T024 [US4] Crear endpoint `POST /api/seats/checkout` en `SeatController.php`
- [X] T025 [US4] Validar que el `session_id` coincida con la reserva activa
- [X] T026 [US4] Actualizar estado a `sold` y emitir evento final `SeatUpdated`

---

## Phase N: Polish & Cross-Cutting Concerns

- [X] T027 [P] Crear Seeder de asientos `api/database/seeders/SeatSeeder.php` con datos de prueba
- [X] T028 [P] Documentar variables de entorno necesarias en `README.md`
- [X] T029 [P] Añadir logs de auditoría en Laravel para cada reserva exitosa/fallida
- [X] T030 Validar flujo completo con `quickstart.md`

---

## Dependencies & Execution Order

1. **Fase 1 & 2**: Obligatorias. Bloquean todo el desarrollo.
2. **US1 (Fase 3)**: Es el MVP. Define el patrón de comunicación Redis -> Node -> Browser.
3. **US2 & US3**: Pueden desarrollarse en paralelo tras completar US1.
4. **US4**: Depende de tener el flujo de reserva (US1) estable.

### Parallel Opportunities

- El desarrollo del frontend (`SeatMap.vue`) puede empezar en paralelo con el backend una vez definidos los contratos de la API.
- La configuración de `websockets/server.js` puede hacerse independientemente de la lógica del controlador de Laravel siempre que se respete el canal de Redis.

---

## Implementation Strategy

### MVP First (User Story 1 Only)
1. Setup Redis + Migraciones.
2. Implementar `POST /reserve` + Evento Redis.
3. Servidor Node básico.
4. UI mínima en Nuxt para ver el cambio de color.

### Incremental Delivery
1. Añadir control de errores de concurrencia (US2).
2. Añadir el cron de limpieza (US3).
3. Añadir el flujo de cierre de venta (US4).
