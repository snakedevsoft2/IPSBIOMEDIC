<?php
declare(strict_types=1);

final class AuthController extends Controller
{
    public function login(): void
    {
        if (user()) {
            redirect('dashboard');
        }

        $error = '';
        $login = '';
        if (is_post()) {
            $login = input('usuario');
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if ($login === '' || $password === '') {
                $error = 'Ingrese su usuario y contraseña.';
            } else {
                [$ok, $msg] = Auth::attempt($login, $password);
                if ($ok) {
                    $intended = (string) ($_SESSION['_intended'] ?? '');
                    unset($_SESSION['_intended']);
                    if (str_starts_with($intended, 'r=') && !str_starts_with($intended, 'r=log')) {
                        redirect_to('index.php?' . $intended);
                    }
                    redirect('dashboard');
                }
                $error = $msg;
            }
        }

        $this->view('auth/login', ['title' => 'Ingresar', 'error' => $error, 'login' => $login], 'auth');
    }

    public function logout(): void
    {
        $this->requirePost();
        Audit::log('logout', 'usuarios', (int) user()['id']);
        Auth::logout();
        session_start();
        flash('success', 'Sesión cerrada correctamente.');
        redirect('login');
    }

    public function forgot(): void
    {
        if (user()) {
            redirect('dashboard');
        }

        $enviado = false;
        $error = '';
        $email = '';

        if (is_post()) {
            $email = mb_strtolower(input('email'));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ingrese un correo electrónico válido.';
            } else {
                $u = DB::row('SELECT * FROM usuarios WHERE email = ? AND activo = 1', [$email]);
                if ($u) {
                    $recientes = (int) DB::value(
                        'SELECT COUNT(*) FROM password_resets WHERE usuario_id = ? AND creado_en > (NOW() - INTERVAL 15 MINUTE)',
                        [$u['id']]
                    );
                    if ($recientes < 3) {
                        $token = bin2hex(random_bytes(32));
                        DB::insert('password_resets', [
                            'usuario_id' => $u['id'],
                            'token_hash' => hash('sha256', $token),
                            'expira_en'  => date('Y-m-d H:i:s', time() + 3600),
                            'ip'         => client_ip(),
                        ]);
                        $link = absolute_url('restablecer', ['token' => $token]);
                        $body = '<p>Hola <strong>' . e($u['nombres']) . '</strong>,</p>'
                            . '<p>Recibimos una solicitud para restablecer la contraseña del usuario <strong>' . e($u['usuario']) . '</strong>.</p>'
                            . '<p style="margin:24px 0"><a href="' . e($link) . '" style="background:#008C95;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">Restablecer contraseña</a></p>'
                            . '<p>El enlace vence en 60 minutos. Si usted no hizo esta solicitud, ignore este mensaje; su contraseña actual seguirá funcionando.</p>'
                            . '<p style="font-size:12px;color:#64748b">Si el botón no funciona, copie este enlace en el navegador:<br>' . e($link) . '</p>';
                        [$ok, $err] = Mailer::send($u['email'], nombre_usuario($u), 'Restablecer contraseña', $body);
                        Audit::log($ok ? 'recuperacion_enviada' : 'recuperacion_error', 'usuarios', (int) $u['id'], $ok ? '' : $err, (int) $u['id']);
                        if (!$ok && config('app.debug')) {
                            $error = 'No se pudo enviar el correo: ' . $err;
                        }
                    }
                }
                // Mismo mensaje exista o no el correo, para no revelar usuarios registrados.
                $enviado = $error === '';
            }
        }

        $this->view('auth/forgot', ['title' => 'Recuperar contraseña', 'enviado' => $enviado, 'error' => $error, 'email' => $email], 'auth');
    }

    public function reset(): void
    {
        $token = input('token');
        $reset = null;
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            $reset = DB::row(
                'SELECT r.*, u.nombres, u.usuario FROM password_resets r JOIN usuarios u ON u.id = r.usuario_id
                 WHERE r.token_hash = ? AND r.usado = 0 AND r.expira_en > NOW() AND u.activo = 1',
                [hash('sha256', $token)]
            );
        }

        if (!$reset) {
            $this->view('auth/reset', ['title' => 'Enlace no válido', 'valido' => false, 'token' => '', 'error' => '', 'reset' => null], 'auth');
            return;
        }

        $error = '';
        if (is_post()) {
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
            $error = password_policy_error($password, $confirm) ?? '';
            if ($error === '') {
                DB::transaction(static function () use ($reset, $password): void {
                    DB::run(
                        'UPDATE usuarios SET password_hash = ?, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?',
                        [password_hash($password, PASSWORD_DEFAULT), $reset['usuario_id']]
                    );
                    DB::run('UPDATE password_resets SET usado = 1 WHERE usuario_id = ?', [$reset['usuario_id']]);
                });
                Audit::log('password_restablecida', 'usuarios', (int) $reset['usuario_id'], '', (int) $reset['usuario_id']);
                flash('success', 'Su contraseña fue actualizada. Ya puede ingresar.');
                redirect('login');
            }
        }

        $this->view('auth/reset', ['title' => 'Nueva contraseña', 'valido' => true, 'token' => $token, 'error' => $error, 'reset' => $reset], 'auth');
    }
}
