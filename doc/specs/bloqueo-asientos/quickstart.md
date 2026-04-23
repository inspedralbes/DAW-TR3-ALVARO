# Quickstart: Bloqueo de Asientos

## Requisitos Previos

- Docker / Docker Compose (recomendado).
- Node.js 20+ y PHP 8.3 local (si no se usa Docker).
- Redis server corriendo (para broadcasting).

## Pasos de Instalación

1.  **Backend Laravel**:
    ```bash
    cd api
    composer install
    php artisan migrate
    php artisan db:seed --class=SeatSeeder
    php artisan serve
    ```

2.  **WebSocket Node.js**:
    ```bash
    cd websockets
    npm install
    npm run dev
    ```

3.  **Frontend Nuxt**:
    ```bash
    cd frontend
    npm install
    npm run dev
    ```

## Flujo de Prueba (Happy Path)

1.  Abrir dos pestañas del navegador en `http://localhost:3000`.
2.  En la Pestaña A, hacer clic en un asiento verde (`available`).
3.  Observar cómo se vuelve gris (`reserved`) instantáneamente en la Pestaña B.
4.  Esperar 5 minutos (o forzar el comando `php artisan seats:release-expired`).
5.  El asiento vuelve a verde en ambas pestañas.

## Comando de Limpieza Manual

```bash
php artisan seats:release-expired
```
