<?php
/**
 * Instalador de IPS BIOMED · Historia Clínica Electrónica
 * Ejecútelo una sola vez desde el navegador: https://sudominio.com/install.php
 * Al terminar, borre este archivo del servidor.
 */
declare(strict_types=1);

define('BASE_PATH', __DIR__);

// En Vercel el filesystem es de solo lectura: este instalador no puede
// escribir config/config.php ni funcionaría entre invocaciones serverless.
// Ahí la configuración se pone por variables de entorno (ver
// config/config.vercel.php) y el esquema se importa una sola vez a mano
// contra la base de datos externa.
if (getenv('VERCEL') !== false) {
    http_response_code(404);
    echo 'El instalador web no está disponible en este entorno. La base de datos y la configuración ya están definidas por variables de entorno.';
    exit;
}

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

$configFile = BASE_PATH . '/config/config.php';
$yaInstalado = is_file($configFile);

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ejecutarSql(PDO $pdo, string $archivo): void
{
    $sql = (string) file_get_contents($archivo);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    foreach (preg_split('/;\s*[\r\n]+/', $sql) ?: [] as $sentencia) {
        $sentencia = trim($sentencia);
        if ($sentencia !== '') {
            $pdo->exec($sentencia);
        }
    }
}

$requisitos = [
    'PHP 8.1 o superior (actual ' . PHP_VERSION . ')' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'Extensión pdo_mysql'                             => extension_loaded('pdo_mysql'),
    'Extensión mbstring'                              => extension_loaded('mbstring'),
    'Extensión gd (logos y PDF)'                      => extension_loaded('gd'),
    'Extensión dom (PDF)'                             => extension_loaded('dom'),
    'Extensión openssl'                               => extension_loaded('openssl'),
    'Librerías instaladas (carpeta vendor/)'          => is_file(BASE_PATH . '/vendor/autoload.php'),
    'Carpeta config/ con permiso de escritura'        => is_dir(BASE_PATH . '/config') && is_writable(BASE_PATH . '/config'),
    'Carpeta storage/ con permiso de escritura'       => is_dir(BASE_PATH . '/storage') && is_writable(BASE_PATH . '/storage'),
    'Carpeta assets/uploads/ con permiso de escritura' => is_dir(BASE_PATH . '/assets/uploads') && is_writable(BASE_PATH . '/assets/uploads'),
];
$requisitosOk = !in_array(false, $requisitos, true);

if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(16));
}

$urlSugerida = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://'
    . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname((string) $_SERVER['SCRIPT_NAME'])), '/');

$v = [
    'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'app_url' => $urlSugerida, 'ips_nombre' => 'IPS BIOMED', 'ips_nit' => '',
    'admin_nombres' => '', 'admin_apellidos' => '', 'admin_documento' => '', 'admin_email' => '', 'admin_usuario' => 'admin',
];
$errores = [];
$listo = false;

if (!$yaInstalado && $requisitosOk && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!hash_equals($_SESSION['install_csrf'], (string) ($_POST['_csrf'] ?? ''))) {
        $errores[] = 'La sesión expiró. Recargue la página e intente de nuevo.';
    } else {
        foreach (array_keys($v) as $campo) {
            $v[$campo] = trim((string) ($_POST[$campo] ?? ''));
        }
        $v['db_pass'] = (string) ($_POST['db_pass'] ?? '');
        $password = (string) ($_POST['admin_password'] ?? '');
        $password2 = (string) ($_POST['admin_password_confirm'] ?? '');

        if ($v['db_name'] === '' || $v['db_user'] === '') {
            $errores[] = 'Indique el nombre y el usuario de la base de datos.';
        }
        if (!filter_var($v['app_url'], FILTER_VALIDATE_URL)) {
            $errores[] = 'La dirección del sitio no es válida (ejemplo: https://ipsbiomed.com).';
        }
        if ($v['ips_nombre'] === '') {
            $errores[] = 'Escriba el nombre de la IPS.';
        }
        if ($v['admin_nombres'] === '' || $v['admin_apellidos'] === '' || $v['admin_documento'] === '') {
            $errores[] = 'Complete los datos del administrador.';
        }
        if (!filter_var($v['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo del administrador no es válido.';
        }
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $v['admin_usuario'])) {
            $errores[] = 'El usuario del administrador solo admite minúsculas, números, punto, guion y guion bajo.';
        }
        if (mb_strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errores[] = 'La contraseña debe tener mínimo 8 caracteres, con letras y números.';
        }
        if ($password !== $password2) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        if (!$errores) {
            try {
                $dsn = 'mysql:host=' . $v['db_host'] . ';port=' . (int) $v['db_port'] . ';charset=utf8mb4';
                $pdo = new PDO($dsn, $v['db_user'], $v['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $baseSegura = str_replace('`', '', $v['db_name']);
                try {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$baseSegura}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (PDOException) {
                    // El hosting ya creó la base de datos y el usuario no tiene permiso CREATE DATABASE.
                }
                $pdo->exec("USE `{$baseSegura}`");
                $pdo->exec("SET time_zone = '-05:00'");

                $tieneUsuarios = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchColumn();
                if ($tieneUsuarios && (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
                    throw new RuntimeException('La base de datos ya contiene una instalación con usuarios. Use otra base de datos o restaure el archivo config/config.php.');
                }

                ejecutarSql($pdo, BASE_PATH . '/database/schema.sql');
                ejecutarSql($pdo, BASE_PATH . '/database/seed_datos.sql');
                ejecutarSql($pdo, BASE_PATH . '/database/seed_cie10.sql');

                $stmt = $pdo->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
                foreach ([
                    'ips_nombre' => $v['ips_nombre'],
                    'ips_nit' => $v['ips_nit'],
                    'ips_email' => $v['admin_email'],
                    'sesion_minutos' => '60',
                    'pie_reportes' => 'Documento confidencial sujeto a reserva legal (Ley 23 de 1981 y Resolución 1995 de 1999).',
                ] as $clave => $valor) {
                    $stmt->execute([$clave, $valor]);
                }

                $pdo->prepare(
                    'INSERT INTO usuarios (tipo_documento, documento, nombres, apellidos, email, usuario, password_hash, rol, especialidad, activo)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
                )->execute([
                    'CC', $v['admin_documento'], $v['admin_nombres'], $v['admin_apellidos'],
                    mb_strtolower($v['admin_email']), $v['admin_usuario'], password_hash($password, PASSWORD_DEFAULT),
                    'administrador', 'Administración',
                ]);

                $config = [
                    'db' => [
                        'host' => $v['db_host'], 'port' => (int) $v['db_port'], 'name' => $v['db_name'],
                        'user' => $v['db_user'], 'pass' => $v['db_pass'],
                    ],
                    'app' => [
                        'url' => rtrim($v['app_url'], '/'), 'timezone' => 'America/Bogota',
                        'debug' => false, 'key' => bin2hex(random_bytes(32)),
                    ],
                ];
                $contenido = "<?php\n// Generado por el instalador el " . date('Y-m-d H:i:s') . "\nreturn " . var_export($config, true) . ";\n";
                if (file_put_contents($configFile, $contenido) === false) {
                    throw new RuntimeException('No fue posible escribir config/config.php. Verifique los permisos de la carpeta config/.');
                }

                foreach (['storage/logs', 'storage/tmp', 'storage/fonts', 'storage/firmas'] as $dir) {
                    if (!is_dir(BASE_PATH . '/' . $dir)) {
                        @mkdir(BASE_PATH . '/' . $dir, 0755, true);
                    }
                }
                $listo = true;
            } catch (Throwable $e) {
                $errores[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Instalación · IPS BIOMED</title>
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="py-5" style="background:#F5F7FA">
<div class="container" style="max-width: 860px">
  <div class="text-center mb-4">
    <img src="assets/img/logo-color.png" alt="IPS BIOMED" style="height:52px">
    <h1 class="h4 mt-3 mb-1">Instalación del sistema de historia clínica</h1>
    <p class="text-muted">Configure la base de datos y el usuario administrador.</p>
  </div>

  <?php if ($yaInstalado): ?>
    <div class="card"><div class="card-body text-center p-5">
      <i class="bi bi-check-circle text-turquesa" style="font-size:2.5rem"></i>
      <h2 class="h5 mt-3">El sistema ya está instalado</h2>
      <p class="text-muted">Existe el archivo <code>config/config.php</code>. Por seguridad, borre <code>install.php</code> del servidor.</p>
      <a href="index.php" class="btn btn-primary">Ir al ingreso</a>
    </div></div>

  <?php elseif ($listo): ?>
    <div class="card"><div class="card-body p-5 text-center">
      <i class="bi bi-check-circle-fill text-turquesa" style="font-size:3rem"></i>
      <h2 class="h5 mt-3">¡Instalación completada!</h2>
      <p class="text-muted mb-4">Ya puede ingresar con el usuario <strong><?= h($v['admin_usuario']) ?></strong>.</p>
      <div class="alert alert-warning text-start">
        <strong>Importante:</strong> borre ahora el archivo <code>install.php</code> del servidor. Después entre a
        <em>Administración → Configuración</em> para cargar los datos de la IPS y el correo SMTP (necesario para recuperar contraseñas).
      </div>
      <a href="index.php" class="btn btn-primary btn-lg">Ingresar al sistema</a>
    </div></div>

  <?php else: ?>
    <div class="card mb-3">
      <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-list-check"></i> Requisitos del servidor</h2></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($requisitos as $nombre => $ok): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= h($nombre) ?></span>
            <?php if ($ok): ?>
              <span class="badge-status badge-status-success"><i class="bi bi-check2"></i>Correcto</span>
            <?php else: ?>
              <span class="badge-status" style="background:#FDECEE;color:#B5101D"><i class="bi bi-x-lg"></i>Falta</span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php foreach ($errores as $error): ?>
      <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= h($error) ?></div>
    <?php endforeach; ?>

    <?php if (!$requisitosOk): ?>
      <div class="alert alert-warning">Corrija los requisitos marcados antes de continuar. Si falta la carpeta <code>vendor/</code>, súbala completa desde el proyecto.</div>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= h($_SESSION['install_csrf']) ?>">

        <div class="card mb-3">
          <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-database"></i> Base de datos MySQL</h2></div>
          <div class="card-body">
            <p class="text-muted small">Cree la base de datos y el usuario en el panel del hosting (cPanel/hPanel) y copie aquí esos datos.</p>
            <div class="row g-3">
              <div class="col-md-8"><label class="form-label">Servidor</label><input name="db_host" value="<?= h($v['db_host']) ?>" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Puerto</label><input name="db_port" value="<?= h($v['db_port']) ?>" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Nombre de la base</label><input name="db_name" value="<?= h($v['db_name']) ?>" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Usuario</label><input name="db_user" value="<?= h($v['db_user']) ?>" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Contraseña</label><input type="password" name="db_pass" class="form-control"></div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-building"></i> Datos de la IPS</h2></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Nombre de la IPS</label><input name="ips_nombre" value="<?= h($v['ips_nombre']) ?>" class="form-control" required></div>
              <div class="col-md-3"><label class="form-label">NIT</label><input name="ips_nit" value="<?= h($v['ips_nit']) ?>" class="form-control"></div>
              <div class="col-md-3"><label class="form-label">Dirección del sitio</label><input name="app_url" value="<?= h($v['app_url']) ?>" class="form-control" required></div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-person-gear"></i> Usuario administrador</h2></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label">Nombres</label><input name="admin_nombres" value="<?= h($v['admin_nombres']) ?>" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Apellidos</label><input name="admin_apellidos" value="<?= h($v['admin_apellidos']) ?>" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Documento</label><input name="admin_documento" value="<?= h($v['admin_documento']) ?>" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Correo electrónico</label><input type="email" name="admin_email" value="<?= h($v['admin_email']) ?>" class="form-control" required>
                <div class="form-text">Se usa para recuperar la contraseña.</div></div>
              <div class="col-md-6"><label class="form-label">Usuario</label><input name="admin_usuario" value="<?= h($v['admin_usuario']) ?>" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Contraseña</label><input type="password" name="admin_password" class="form-control" required minlength="8">
                <div class="form-text">Mínimo 8 caracteres con letras y números.</div></div>
              <div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input type="password" name="admin_password_confirm" class="form-control" required minlength="8"></div>
            </div>
          </div>
        </div>

        <div class="d-grid"><button class="btn btn-primary btn-lg"><i class="bi bi-gear"></i> Instalar el sistema</button></div>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
