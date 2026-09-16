<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
const APP_VERSION = '1.0.0';

// Vercel expone la variable de entorno VERCEL=1 en tiempo de ejecución.
// Ahí el filesystem es de solo lectura, así que config/config.php (que en el
// hosting compartido escribe install.php) no se puede crear: la configuración
// sale de variables de entorno del proyecto Vercel. Ver config/config.vercel.php.
if (is_file(BASE_PATH . '/config/config.php')) {
    $GLOBALS['config'] = require BASE_PATH . '/config/config.php';
} elseif (getenv('VERCEL') !== false) {
    $GLOBALS['config'] = require BASE_PATH . '/config/config.vercel.php';
} else {
    header('Location: install.php');
    exit;
}

require BASE_PATH . '/vendor/autoload.php';
require __DIR__ . '/core/helpers.php';
require __DIR__ . '/core/DB.php';
require __DIR__ . '/core/Storage.php';
require __DIR__ . '/core/DbSessionHandler.php';
require __DIR__ . '/core/Auth.php';
require __DIR__ . '/core/Audit.php';
require __DIR__ . '/core/Mailer.php';
require __DIR__ . '/core/Pdf.php';
require __DIR__ . '/core/Clinica.php';
require __DIR__ . '/core/Controller.php';

date_default_timezone_set((string) config('app.timezone', 'America/Bogota'));
mb_internal_encoding('UTF-8');

$debug = (bool) config('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
if (!Storage::enVercel()) {
    ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
}

set_exception_handler(static function (Throwable $e) use ($debug): void {
    error_log('[' . date('c') . '] ' . $e);
    if (ob_get_level()) {
        ob_end_clean();
    }
    abort(500, $debug ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
                      : 'Ocurrió un error inesperado. Si persiste, contacte al administrador.');
});

session_name('IPSBIOMED_SID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
if (Storage::enVercel()) {
    session_set_save_handler(new DbSessionHandler(), true);
}
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
