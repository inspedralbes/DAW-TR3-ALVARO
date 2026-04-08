# API Contract: Seat Reservation

## POST `/api/seats/reserve`

Reserva un asiento para un usuario identificado por su `session_id`.

**Request Body**:
```json
{
  "seat_id": 123,
  "session_id": "uuid-v4-string"
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Seat reserved successfully",
  "data": {
    "seat_id": 123,
    "status": "reserved",
    "expires_at": "2026-04-07T12:05:00Z"
  }
}
```

**Response 409 Conflict**:
```json
{
  "success": false,
  "error": "Seat already reserved or sold"
}
```

**Response 422 Unprocessable Entity**:
```json
{
  "success": false,
  "error": "Invalid seat_id or session_id format"
}
```

## GET `/api/seats`

Lista todos los asientos con sus estados actuales.

**Response 200 OK**:
```json
[
  {
    "id": 1,
    "row": "A",
    "number": 1,
    "status": "available",
    "price": 50.00
  },
  ...
]
```
