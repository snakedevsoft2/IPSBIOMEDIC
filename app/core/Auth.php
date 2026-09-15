<?php
declare(strict_types=1);

final class Auth
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 15;

    private static ?array $user = null;
    private static bool $loaded = false;

    /** @return array{0: bool, 1: string} */
    public static function attempt(string $login, string $password): array
    {
        $u = DB::row('SELECT * FROM usuarios WHERE usuario = ? OR email = ? LIMIT 1', [$login, $login]);

        if (!$u) {
            // Igualar tiempos de respuesta para no revelar si el usuario existe
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            Audit::log('login_fallido', 'usuarios', null, 'Usuario inexistente: ' . mb_substr($login, 0, 60));
            return [false, 'Usuario o contraseña incorrectos.'];
        }

        if ($u['bloqueado_hasta'] !== null && strtotime($u['bloqueado_hasta']) > time()) {
            $min = (int) ceil((strtotime($u['bloqueado_hasta']) - time()) / 60);
            return [false, "Cuenta bloqueada temporalmente por intentos fallidos. Intente de nuevo en {$min} minuto(s) o restablezca su contraseña."];
        }

        if (!password_verify($password, $u['password_hash'])) {
            $intentos = (int) $u['intentos_fallidos'] + 1;
            if ($intentos >= self::MAX_INTENTOS) {
                DB::run(
                    'UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = ? WHERE id = ?',
                    [date('Y-m-d H:i:s', time() + self::MINUTOS_BLOQUEO * 60), $u['id']]
                );
                Audit::log('cuenta_bloqueada', 'usuarios', (int) $u['id'], 'Bloqueo por intentos fallidos', (int) $u['id']);
                return [false, 'Demasiados intentos fallidos. La cuenta se bloqueó por ' . self::MINUTOS_BLOQUEO . ' minutos.'];
            }
            DB::run('UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?', [$intentos, $u['id']]);
            Audit::log('login_fallido', 'usuarios', (int) $u['id'], 'Contraseña incorrecta', (int) $u['id']);
            return [false, 'Usuario o contraseña incorrectos.'];
        }

        if (!(int) $u['activo']) {
            return [false, 'Su usuario está inactivo. Comuníquese con el administrador.'];
        }

        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::run('UPDATE usuarios SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];
        $_SESSION['last_activity'] = time();
        DB::run('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = ?', [$u['id']]);

        self::$loaded = false;
        Audit::log('login', 'usuarios', (int) $u['id']);
        return [true, ''];
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;

        if (empty($_SESSION['uid'])) {
            return null;
        }

        $timeout = max(5, (int) setting('sesion_minutos', '60')) * 60;
        if (time() - (int) ($_SESSION['last_activity'] ?? 0) > $timeout) {
            self::logout();
            session_start();
            flash('warning', 'Su sesión se cerró por inactividad.');
            return null;
        }

        $u = DB::row('SELECT * FROM usuarios WHERE id = ? AND activo = 1', [$_SESSION['uid']]);
        if (!$u) {
            self::logout();
            session_start();
            return null;
        }

        $_SESSION['last_activity'] = time();
        self::$user = $u;
        return $u;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$user = null;
    }
}
