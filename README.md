# IPS BIOMED · Historia Clínica Electrónica

Aplicación web en PHP + MySQL para una IPS colombiana con el módulo de **medicina general**:
recepción registra al paciente y lo admite, el médico continúa con la historia clínica,
expide fórmula médica, órdenes e incapacidad, y la administración consulta indicadores e informes.

## Módulos

| Módulo | Contenido |
|---|---|
| **Ingreso** | Login por usuario o correo, bloqueo tras 5 intentos fallidos, recuperación de contraseña por correo, cierre de sesión por inactividad. |
| **Panel (dashboard)** | Atendidos hoy y del mes, pacientes en sala de espera, fórmulas emitidas, atenciones por día, curso de vida, diagnósticos frecuentes, atenciones por sexo, régimen y médico. |
| **Pacientes** | Registro completo (identificación, datos personales, residencia, aseguramiento, acompañante y responsable), búsqueda y ficha con historial. |
| **Admisiones** | Recepción admite al paciente (tipo de consulta, finalidad, causa externa, prioridad, EPS, autorización y copago) y lo envía a la sala de espera. |
| **Historia clínica** | Anamnesis, antecedentes, revisión por sistemas, signos vitales con IMC automático, examen físico, paraclínicos, diagnósticos CIE-10, análisis y plan, fórmula médica, órdenes e incapacidad. Al finalizar queda bloqueada y solo admite notas aclaratorias. |
| **PDF** | Historia clínica, fórmula médica (cantidades en números y letras), órdenes, incapacidad e informe de atenciones. Todos con el logo y los datos de la IPS. |
| **Informes** | Filtros por fecha, médico, EPS y régimen; exportación a Excel (CSV) y PDF. |
| **Administración** | Usuarios y perfiles, configuración de la IPS y logos, correo SMTP, catálogo CIE-10 (con importación del CSV oficial), EPS, auditoría de accesos y respaldo de la base de datos. |

## Perfiles de usuario

- **Administrador:** todo el sistema, usuarios, configuración, auditoría y respaldos.
- **Médico:** sala de espera, historia clínica, fórmulas, informes propios.
- **Recepción:** pacientes y admisiones (no accede a las historias clínicas).
- **Auxiliar / enfermería:** consulta de pacientes e historias, sin editar.

## Requisitos

- PHP 8.1 o superior con `pdo_mysql`, `mbstring`, `gd`, `dom` y `openssl`
- MySQL 5.7+ o MariaDB 10.3+
- Apache o LiteSpeed con soporte de `.htaccess` (hosting compartido tipo Hostinger o cPanel)

## Instalación en el hosting

1. En el panel del hosting cree la **base de datos MySQL** y un usuario con todos los permisos sobre ella.
2. Suba todo el contenido de este proyecto a `public_html` (incluida la carpeta `vendor/`).
3. Abra `https://sudominio.com/install.php` y complete el formulario (base de datos, datos de la IPS y usuario administrador).
4. **Borre `install.php`** del servidor cuando termine.
5. Active el certificado SSL gratuito del hosting para que el sitio funcione con `https://`.
6. Entre como administrador y configure:
   - *Administración → Configuración → Datos de la IPS* (NIT, código de habilitación, dirección, teléfono; salen en los PDF).
   - *Logos*, si desea reemplazar los incluidos.
   - *Correo (SMTP)*, necesario para la recuperación de contraseñas (en Hostinger: `smtp.hostinger.com`, puerto 465, SSL).
   - *Usuarios*: cree los médicos (con registro médico) y el personal de recepción.
   - Cada médico debe cargar su firma en *Mi perfil*.

### Instalación local (XAMPP)

```bash
composer install
# Inicie Apache y MySQL en XAMPP y abra http://localhost/IPSBIOMEDIC/install.php
```

## Estructura

```
app/
  bootstrap.php        arranque, sesión y seguridad
  routes.php           rutas -> controlador y perfiles autorizados
  core/                DB, Auth, Audit, Mailer, Pdf, Clinica, helpers
  controllers/         un controlador por módulo
  views/               vistas y plantillas PDF
assets/                css, js, logos e imágenes subidas
config/                config.php (generado por el instalador; no se versiona)
database/              schema.sql y datos iniciales (EPS, medicamentos, CIE-10)
storage/               logs, temporales y firmas (no público)
vendor/                Dompdf y PHPMailer
```

## Seguridad y cumplimiento

- Contraseñas con `password_hash`, tokens de recuperación de un solo uso con vencimiento de 60 minutos.
- Protección CSRF en todos los formularios y consultas SQL preparadas.
- Historia clínica no modificable una vez finalizada; correcciones por nota aclaratoria (Resolución 1995 de 1999).
- Auditoría de accesos, consultas e impresiones de historias clínicas.
- Fórmula médica con denominación común internacional y cantidades en números y letras.
- Carpetas internas bloqueadas por `.htaccess` y redirección forzada a HTTPS.

## Respaldos

*Administración → Respaldo* descarga un archivo `.sql` con toda la base de datos.
Se recomienda hacerlo semanalmente y mantener además las copias automáticas del hosting.
