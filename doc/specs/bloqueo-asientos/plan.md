# Implementation Plan: Bloqueo de Asientos en Tiempo Real

**Branch**: `implementacio-speckit` | **Date**: 2026-04-07 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `doc/specs/bloqueo-asientos/spec.md`

## Summary
Implementación de un sistema de reserva de asientos en tiempo real utilizando una arquitectura híbrida: Laravel (PHP 8.3) para la lógica transaccional y persistencia en MySQL, Node.js (Express) para la comunicación bidireccional vía Socket.IO, y Nuxt 4 para la interfaz de usuario. El sistema garantiza la integridad de las reservas mediante transacciones SQL y libera automáticamente los asientos tras 5 minutos de inactividad.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 13), Node.js 20+, Nuxt 4 (Vue 3/TS)  
**Primary Dependencies**: Socket.IO, Laravel, Pinia, MySQL, Redis (NEEDS CLARIFICATION)  
**Storage**: MySQL (MariaDB 10.x/11.x)  
**Testing**: Pest (PHP), Vitest (Frontend/Node)  
**Target Platform**: Web Browser / Desktop  
**Project Type**: Web Application (Frontend + Backend + Microservice)  
**Performance Goals**: Latencia de actualización visual < 500ms tras reserva exitosa.  
**Constraints**: Timeout de 5m para reservas no pagadas.  
**Scale/Scope**: NEEDS CLARIFICATION (Carga esperada de usuarios concurrentes)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Gate | Status | Detail |
|------|--------|--------|
| TDD Mandatory | PLANNED | Se han definido las bases para los tests en Pest. |
| Transaction Safety | PASSED | Confirmado el uso de atomic updates/transacciones en research.md. |
| Event Schema | PASSED | Contrato definido en doc/specs/bloqueo-asientos/contracts/websockets.md. |
| Automated Cleanup | PASSED | Estrategia de limpieza vía Laravel Scheduler confirmada. |

## Project Structure

### Documentation (this feature)

```text
doc/specs/bloqueo-asientos/
├── plan.md              # Este archivo
├── research.md          # Resultado de Fase 0
├── data-model.md        # Resultado de Fase 1
├── quickstart.md        # Resultado de Fase 1
├── contracts/           # Resultado de Fase 1 (WebSocket + API)
└── tasks.md             # Tareas detalladas
```

### Source Code (repository root)

```text
api/                     # Laravel Backend
├── app/Http/Controllers/SeatController.php
├── app/Models/Seat.php
├── database/migrations/
└── tests/Feature/SeatReservationTest.php

frontend/                # Nuxt Frontend
├── components/SeatMap.vue
├── stores/seatStore.ts
└── plugins/socket.io.client.ts

websockets/              # Node.js WebSocket Server
├── server.js
├── db.js
└── handlers/seatHandler.js
```

**Structure Decision**: Se mantiene la estructura multi-proyecto detectada (api, frontend, websockets).

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Dos backends (Laravel + Node) | Gestión eficiente de WebSockets en tiempo real. | Laravel Reverb/Pusher son alternativas, pero se prefiere Node.js por compatibilidad con el entorno educativo actual. |
