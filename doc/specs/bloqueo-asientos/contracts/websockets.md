# WebSocket Contract: Seat Updates

## Servidor a Cliente (Emit)

### Evento: `seat_updated`

Enviado cada vez que un asiento cambia de estado (`available`, `reserved`, `sold`).

**Payload**:
```json
{
  "seat_id": 123,
  "status": "reserved",
  "session_id": "uuid-v4-of-owner" (opcional)
}
```

## Cliente a Servidor (On)

### Evento: `subscribe`

Permite al cliente declarar que desea recibir actualizaciones para un conjunto de asientos (por defecto: todos).

**Payload**:
```json
{
  "view": "main_stadium"
}
```

## Seguridad

- El servidor de Node valida que el handshake contenga una cabecera `X-Session-ID` válida.
- Los eventos de `seat_updated` son públicos para todos los clientes conectados a la misma vista.
