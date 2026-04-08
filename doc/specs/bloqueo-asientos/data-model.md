# Data Model: Bloqueo de Asientos

## Tabla: `seats`

| Campo | Tipo | Nullable | Default | Descripción |
|-------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | No | Auto-inc | Identificador único del asiento. |
| `row` | VARCHAR(10) | No | - | Fila del asiento (ej: 'A', '1'). |
| `number` | INT | No | - | Número del asiento en la fila. |
| `status` | ENUM | No | 'available' | Estados: `available`, `reserved`, `sold`. |
| `session_id` | VARCHAR(255) | Sí | NULL | UUID del cliente que posee la reserva actual. |
| `reserved_at` | DATETIME | Sí | NULL | Momento en que se realizó la reserva (para timeout). |
| `price` | DECIMAL(10,2) | No | 0.00 | Precio base del asiento. |
| `created_at` | TIMESTAMP | Sí | - | Laravel auto-managed. |
| `updated_at` | TIMESTAMP | Sí | - | Laravel auto-managed. |

## Reglas de Validación

- **Transición de Estado**:
  - `available` → `reserved`: Permitido si `status` == 'available'.
  - `reserved` → `sold`: Permitido si `session_id` coincide con el autor del pago.
  - `reserved` → `available`: Permitido tras 5m de inactividad o cancelación manual.
  - `sold` → `available`: No permitido (requiere proceso de reembolso fuera de scope).

- **Restricciones de Integridad**:
  - Un asiento solo puede estar reservado por un `session_id` a la vez.
  - No se permiten reservas cruzadas (dos `session_id` para el mismo `id`).

## Índices

- `INDEX(status)`: Para acelerar búsquedas de asientos libres.
- `INDEX(reserved_at)`: Para acelerar la limpieza de expirados.
- `UNIQUE(row, number)`: Para evitar duplicados físicos.
