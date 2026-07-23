# Entorno de desarrollo — Sistema Contable

Stack: **PHP 8.4 (FPM) + Nginx + PostgreSQL 17 + pgAdmin**, todo en Docker.

## Estructura

```
sistema-contable/
├── docker-compose.yml        # Define los 4 contenedores
├── .env.example              # Variables de entorno (copiar a .env)
├── docker/
│   ├── php/Dockerfile        # Imagen PHP con extensiones (pgsql, intl, bcmath...)
│   └── nginx/default.conf    # Configuración del servidor web
└── src/                      # ← AQUÍ VA TU CÓDIGO
    └── public/index.php      # Página de prueba del entorno
```

## Primeros pasos

```bash
# 1. Entrar a la carpeta del proyecto
cd sistema-contable

# 2. Crear el archivo de variables de entorno
cp .env.example .env          # en Windows: copy .env.example .env

# 3. Construir y levantar todo (la primera vez tarda unos minutos)
docker compose up -d --build

# 4. Abrir en el navegador
#    App:      http://localhost:8080   → debe mostrar "Entorno Docker funcionando"
#    pgAdmin:  http://localhost:8081   → admin@local.dev / admin123
```

## Comandos útiles

```bash
docker compose ps                 # ver estado de los contenedores
docker compose logs -f php        # ver logs de PHP en vivo
docker compose exec php sh        # entrar al contenedor PHP (consola)
docker compose exec db psql -U contable_user -d contable   # consola SQL
docker compose down               # apagar todo (los datos de la BD se conservan)
docker compose down -v            # apagar Y BORRAR los datos de la BD (¡cuidado!)
```

## Conectar pgAdmin a la base de datos

En pgAdmin (localhost:8081) → *Add New Server*:
- **Name:** contable
- **Host:** `db`  ← nombre del servicio, no "localhost"
- **Port:** 5432
- **Username / Password:** los de tu `.env`

## Instalar Laravel (cuando estés listo)

```bash
docker compose exec php sh
composer create-project laravel/laravel .    # dentro de /var/www/html
exit
```

Nginx ya apunta a `src/public`, que es justo donde Laravel pone su `index.php`,
así que funcionará sin cambiar nada. Luego configura la BD en el `.env`
de Laravel con: `DB_CONNECTION=pgsql`, `DB_HOST=db`, `DB_PORT=5432`.

## Notas

- **Los contenedores se comunican por nombre de servicio**: desde PHP, la BD
  es `db`, no `localhost`. Desde tu PC (DBeaver, etc.) sí es `localhost:5432`.
- El código en `src/` está montado como volumen: editas en tu PC y los cambios
  se reflejan al instante, sin reconstruir nada.
- `bcmath` e `intl` ya vienen instaladas: úsalas para montos contables
  (nunca uses float para dinero) y formateo de pesos chilenos.
- Zona horaria configurada: `America/Santiago`.
