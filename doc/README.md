# TicketMonster - Manual d'Instal·lació i Configuració

Aquest manual proporciona els passos per tenir el projecte aixecat en qualsevol entorn de desenvolupament, juntament amb els detalls arquitectònics clau del mateix (diagrames i fluxos).

## 1. Requisits Previs

- **Docker Desktop** o `docker compose` (v2+) instal·lat nativament al sistema operable host.
- **Git** instal·lat prèviament.

## 2. Passos de Llançament

```bash
# 1. Clonar el repositori
git clone https://github.com/inspedralbes/prj-entrades-varito9.git
cd prj-entrades-varito9

# 2. Re-construir i aixecar l'orquestració de microserveis (Nuxt, Pdo_MySQL, Redis, Node.js WebSocket)
docker compose up --build -d

# 3. Accedir a la terminal interna del motor Laravel
docker exec -it ticket_api sh

# 4. (Una vegada dins del ticket_api), executar les migracions i introduir la dada massiva:
php artisan migrate:fresh --seed
```

### Accessos Directes (Ports):

- **Frontend Nuxt.js (TicketMonster UI):** `http://localhost:3000`
- **Backend Laravel API:** `http://localhost:8000`
- **Node.js WebSockets:** `http://localhost:3001`
- **PhPMyAdmin (Opcional si està activat):** `http://localhost:8080`

### Credencials per defecte

Després d'executar el comandament de _seeding_ (`php artisan migrate:fresh --seed`), el sistema comptarà amb un usuari Administrador per defecte:

- **Usuari Admin:** `admin@ticketmonster.com`
- **Contrasenya:** `admin1234`

### 1. Diagrama de Casos d'Ús (Actors i Opcions)

![Diagrama de Casos d'Ús](img/Concert%20Diagrama_casos_usos.drawio.png)
