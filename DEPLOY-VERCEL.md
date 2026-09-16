# Desplegar en Vercel (entorno de pruebas para el cliente)

Este documento es solo para el **entorno temporal de pruebas** mientras se
sigue desarrollando. El destino final del proyecto sigue siendo el hosting
compartido de `ipsbiomed.com` (ver README.md); ahí no cambia nada.

Vercel es serverless: no tiene disco persistente ni MySQL propio, así que el
código detecta cuándo corre ahí (variable `VERCEL=1`, que Vercel define sola)
y cambia automáticamente:

| En el hosting compartido | En Vercel |
|---|---|
| `config/config.php` (creado por `install.php`) | Variables de entorno del proyecto Vercel |
| Sesión de archivos de PHP | Tabla `sesiones` en MySQL |
| Logos y fotos subidos en `assets/uploads/` y `storage/` | Tabla `archivos` en MySQL |
| `install.php` | Deshabilitado (responde 404) |

No hay que tocar nada de esto a mano: ya está resuelto en el código
(`app/core/Storage.php`, `app/core/DbSessionHandler.php`,
`config/config.vercel.php`).

## 1. Base de datos MySQL externa

Vercel no ofrece MySQL. Se necesita una base de datos MySQL accesible por
internet. Para un entorno de pruebas sirve cualquiera de estas (elige una):

- Railway (railway.app) — MySQL con plan de uso medido, fácil de crear.
- Aiven (aiven.io) — prueba gratuita de 30 días.
- Clever Cloud — plan gratuito pequeño, suficiente para probar.

Al crear la base de datos, anota: host, puerto, nombre de la base, usuario y
contraseña.

### Importar el esquema

Con un cliente MySQL (o el que traiga el panel del proveedor), ejecuta en ese
orden contra la base de datos nueva:

```
database/schema.sql
database/seed_cie10.sql
database/seed_datos.sql
```

### Crear el usuario administrador

El instalador web (`install.php`) está deshabilitado en Vercel, así que el
primer usuario se crea a mano. Genera el hash de una contraseña:

```
php -r "echo password_hash('TuClaveTemporal123', PASSWORD_DEFAULT), PHP_EOL;"
```

y ejecuta (reemplazando el hash y los datos):

```sql
INSERT INTO usuarios (tipo_documento, documento, nombres, apellidos, email, usuario, password_hash, rol, especialidad, activo)
VALUES ('CC', '0000000000', 'Admin', 'IPS BIOMED', 'admin@ipsbiomed.com', 'admin', '<HASH_GENERADO>', 'administrador', 'Administración', 1);
```

## 2. Proyecto en Vercel

1. En vercel.com, "Add New… → Project" e importa el repo
   `snakedevsoft2/IPSBIOMEDIC`.
2. Framework Preset: "Other" (no hay build, `vercel.json` ya define cómo se
   corre PHP).
3. En **Project Settings → Environment Variables**, agrega:

   | Variable | Valor |
   |---|---|
   | `DB_HOST` | host de la base de datos externa |
   | `DB_PORT` | normalmente `3306` |
   | `DB_NAME` | nombre de la base |
   | `DB_USER` | usuario |
   | `DB_PASS` | contraseña |
   | `APP_URL` | la URL que asigne Vercel, ej. `https://ipsbiomedic.vercel.app` |
   | `APP_KEY` | una cadena aleatoria larga: genera con `php -r "echo bin2hex(random_bytes(32));"` |
   | `APP_DEBUG` | `true` mientras se prueba (muestra errores detallados); pásalo a `false` cuando el cliente empiece a usarlo de verdad |

4. Deploy. Cada `git push` a `main` vuelve a desplegar automáticamente.

## 3. Después de cada cambio de código

No hay que hacer nada especial: se hace commit/push como siempre y Vercel
redespliega solo. Las fotos/logos y las sesiones quedan en la base de datos,
así que sobreviven a los redeploys (a diferencia del disco, que se reinicia
en cada uno).

## Limitaciones conocidas de este entorno temporal

- El primer PDF que se genera después de un tiempo sin uso puede tardar un
  poco más (Vercel "despierta" la función).
- `respaldo/descargar` (exportar SQL desde Configuración) funciona igual,
  pero en datasets muy grandes podría toparse con el límite de tiempo de
  ejecución de Vercel (60s en el plan Hobby).
- El instalador web y la edición directa de `config/config.php` no aplican
  aquí; todo se configura por variables de entorno como se explicó arriba.
