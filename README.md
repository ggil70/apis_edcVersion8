# API Estado de Cuenta TDC – Credicard (Laravel 11)

Expone como API REST las funciones `obtener_meses_credicard()` y `obtener_movimientos_credicard()`
que consumen los servicios del proveedor Credicard.

## Endpoints

Todos requieren el header `X-API-KEY` (valor de `API_EDC_CLIENT_KEY` en `.env`).

| Método | Ruta | Body JSON | Función original |
|---|---|---|---|
| POST | `/api/v1/edc/meses` | `{"tarjeta":"4111111111111111"}` | `obtener_meses_credicard` |
| POST | `/api/v1/edc/movimientos` | `{"tarjeta":"4111111111111111","fecha":"2026-09-30"}` | `obtener_movimientos_credicard` |

> Se usa POST para que el número de tarjeta no viaje en la URL (queda en logs de servidores/proxies).
> Hacia el proveedor se sigue llamando con **GET + cuerpo JSON + header `apikey`**, igual que el `curl` original.

### Respuestas

| HTTP | `codigo` | Significado |
|---|---|---|
| 200 | `0` | Éxito. La respuesta del proveedor viene en `data`. |
| 422 | `2` | El proveedor devolvió `msg` (antes `return "2"`). El mensaje viene en `detalle`. |
| 502 | `1` | No hubo conexión con el proveedor (antes `return "1"`). |
| 502 | `3` | El proveedor respondió algo que no es JSON. |
| 500 | `4` | Falta configurar URL o apikey del proveedor en `.env`. |
| 422 | `VALIDACION` | Tarjeta (13-19 dígitos) o fecha (`AAAA-MM-DD`) inválidas. Detalle en `errores`. |
| 401 | `NO_AUTORIZADO` | Falta o es incorrecto el header `X-API-KEY`. |
| 429 | – | Más de 60 peticiones por minuto desde la misma IP. |

Ejemplo de éxito:
```json
{ "success": true, "codigo": "0", "mensaje": "Consulta exitosa.", "data": { ... } }
```

## Instalación en el servidor

Requisitos: PHP 8.2+ (extensiones curl, mbstring, openssl, json), Composer.
El servidor debe tener acceso de red al proveedor (`10.96.10.246:31850`).

```bash
# 1. Copiar la carpeta al servidor y entrar en ella
composer install --no-dev --optimize-autoloader
cp .env.example .env          # solo si no se copió el .env
php artisan key:generate      # solo si .env es nuevo
```

2. Editar `.env`:
   - `CREDICARD_API_CONSULTAR_MESES`, `CREDICARD_API_MOVIMIENTOS` y sus apikeys.
   - `API_EDC_CLIENT_KEY` → generar una: `php -r "echo bin2hex(random_bytes(24));"`
   - En producción: `APP_ENV=production` y `APP_DEBUG=false`.

```bash
# 3. Optimizar y dar permisos
php artisan config:cache
php artisan route:cache
chmod -R 775 storage bootstrap/cache   # Linux
```

4. El DocumentRoot de Apache/Nginx debe apuntar a la carpeta **`public/`**.
   Para pruebas rápidas: `php artisan serve` → `http://localhost:8000`.

> Cada vez que cambie el `.env` con la caché activa, ejecute `php artisan config:cache` de nuevo.

## Postman

Importar `postman/API_EDC_Credicard.postman_collection.json` y, en las variables de la colección,
poner `base_url` y `api_key` (= `API_EDC_CLIENT_KEY`).

## Archivos principales

- `config/credicard.php` – configuración (antes `core01.php`).
- `app/Services/CredicardService.php` – llamadas al proveedor (antes `edc.php`).
- `app/Http/Controllers/EstadoCuentaController.php` – endpoints y validaciones.
- `app/Http/Middleware/ValidarApiKeyCliente.php` – protección con `X-API-KEY`.
- `app/Exceptions/CredicardException.php` – códigos de error.
- `routes/api.php` – rutas.
- `tests/Feature/EstadoCuentaTest.php` – pruebas (`php artisan test`).

Los errores con el proveedor se registran en `storage/logs/laravel.log` con la tarjeta enmascarada.
