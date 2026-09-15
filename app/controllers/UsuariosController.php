<?php
declare(strict_types=1);

final class UsuariosController extends Controller
{
    public function index(): void
    {
        $q = input('q');
        $rol = input('rol');
        $where = '1 = 1';
        $params = [];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= " AND (CONCAT_WS(' ', nombres, apellidos) LIKE ? OR usuario LIKE ? OR email LIKE ? OR documento LIKE ?)";
            array_push($params, $like, $like, $like, $like);
        }
        if (array_key_exists($rol, catalogo('roles'))) {
            $where .= ' AND rol = ?';
            $params[] = $rol;
        }

        $total = (int) DB::value("SELECT COUNT(*) FROM usuarios WHERE {$where}", $params);
        $pg = paginar($total, 25);
        $rows = DB::all("SELECT * FROM usuarios WHERE {$where} ORDER BY activo DESC, nombres LIMIT {$pg['limit']} OFFSET {$pg['offset']}", $params);

        $this->view('usuarios/index', ['title' => 'Usuarios', 'rows' => $rows, 'pg' => $pg, 'q' => $q, 'rol' => $rol]);
    }

    public function crear(): void
    {
        $this->form(null);
    }

    public function editar(): void
    {
        $usuario = DB::row('SELECT * FROM usuarios WHERE id = ?', [$this->id()]) ?? abort(404, 'Usuario no encontrado.');
        $this->form($usuario);
    }

    private function form(?array $usuario): void
    {
        $campos = ['tipo_documento', 'documento', 'nombres', 'apellidos', 'email', 'telefono', 'usuario', 'rol', 'especialidad', 'registro_medico'];
        $data = $usuario ?? ['tipo_documento' => 'CC', 'rol' => 'medico', 'activo' => 1];
        $errors = [];
        $notificar = false;

        if (is_post()) {
            foreach ($campos as $campo) {
                $data[$campo] = input($campo);
            }
            $data['email'] = mb_strtolower($data['email']);
            $data['usuario'] = mb_strtolower($data['usuario']);
            $data['activo'] = input('activo') !== '' ? 1 : 0;
            $notificar = input('notificar') !== '';
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
            $esYo = $usuario && (int) $usuario['id'] === (int) user()['id'];

            foreach (['documento' => 'el documento', 'nombres' => 'los nombres', 'apellidos' => 'los apellidos'] as $campo => $label) {
                if ($data[$campo] === '') {
                    $errors[$campo] = 'Ingrese ' . $label . '.';
                }
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Correo electrónico inválido (se usa para recuperar la contraseña).';
            }
            if (!preg_match('/^[a-z0-9._-]{3,50}$/', $data['usuario'])) {
                $errors['usuario'] = 'Use entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.';
            }
            if (!array_key_exists($data['rol'], catalogo('roles'))) {
                $errors['rol'] = 'Seleccione un perfil.';
            }
            if ($data['rol'] === 'medico' && $data['registro_medico'] === '') {
                $errors['registro_medico'] = 'El registro médico es obligatorio para el perfil Médico (aparece en fórmulas y certificados).';
            }
            if ($esYo && ($data['rol'] !== $usuario['rol'] || !$data['activo'])) {
                $errors['rol'] = 'No puede cambiar su propio perfil ni desactivar su usuario.';
            }
            $id = $usuario['id'] ?? 0;
            if (DB::value('SELECT 1 FROM usuarios WHERE usuario = ? AND id <> ?', [$data['usuario'], $id])) {
                $errors['usuario'] = 'Este nombre de usuario ya está en uso.';
            }
            if (DB::value('SELECT 1 FROM usuarios WHERE email = ? AND id <> ?', [$data['email'], $id])) {
                $errors['email'] = 'Este correo ya está registrado en otro usuario.';
            }
            if (!$usuario || $password !== '') {
                $error = password_policy_error($password, $confirm);
                if ($error) {
                    $errors['password'] = $error;
                }
            }

            if (!$errors) {
                $row = [
                    'tipo_documento' => $data['tipo_documento'], 'documento' => $data['documento'],
                    'nombres' => $data['nombres'], 'apellidos' => $data['apellidos'], 'email' => $data['email'],
                    'telefono' => nullable($data['telefono']), 'usuario' => $data['usuario'], 'rol' => $data['rol'],
                    'especialidad' => nullable($data['especialidad']), 'registro_medico' => nullable($data['registro_medico']),
                    'activo' => (int) $data['activo'],
                ];
                if ($password !== '') {
                    $row['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                    $row['intentos_fallidos'] = 0;
                    $row['bloqueado_hasta'] = null;
                }

                if ($usuario) {
                    DB::update('usuarios', $row, 'id = ?', [$usuario['id']]);
                    $nuevoId = (int) $usuario['id'];
                    Audit::log('usuario_actualizado', 'usuarios', $nuevoId, $data['usuario']);
                    flash('success', 'Usuario actualizado.');
                } else {
                    $nuevoId = DB::insert('usuarios', $row);
                    Audit::log('usuario_creado', 'usuarios', $nuevoId, $data['usuario'] . ' (' . $data['rol'] . ')');
                    flash('success', 'Usuario creado correctamente.');
                }

                if ($notificar) {
                    $body = '<p>Hola <strong>' . e($data['nombres']) . '</strong>,</p>'
                        . '<p>Se creó su acceso al sistema de historia clínica de ' . e(setting('ips_nombre', 'la IPS')) . '.</p>'
                        . '<p><strong>Usuario:</strong> ' . e($data['usuario']) . '<br><strong>Perfil:</strong> ' . e(catalogo('roles')[$data['rol']]) . '</p>'
                        . '<p>La contraseña se la entrega el administrador. Por seguridad, cámbiela desde «Mi perfil» la primera vez que ingrese.</p>'
                        . '<p style="margin:22px 0"><a href="' . e(absolute_url('login')) . '" style="background:#008C95;color:#ffffff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">Ingresar al sistema</a></p>';
                    [$ok, $err] = Mailer::send($data['email'], nombre_usuario($data), 'Acceso al sistema de historia clínica', $body);
                    flash($ok ? 'info' : 'warning', $ok ? 'Se envió el correo de bienvenida.' : 'No se pudo enviar el correo: ' . $err);
                }
                redirect('usuarios');
            }
        }

        $this->view('usuarios/form', [
            'title' => $usuario ? 'Editar usuario' : 'Nuevo usuario',
            'usuario' => $usuario, 'data' => $data, 'errors' => $errors, 'notificar' => $notificar,
        ]);
    }

    public function estado(): void
    {
        $this->requirePost();
        $id = $this->id();
        if ($id === (int) user()['id']) {
            flash('warning', 'No puede desactivar su propio usuario.');
            redirect('usuarios');
        }
        $u = DB::row('SELECT * FROM usuarios WHERE id = ?', [$id]) ?? abort(404, 'Usuario no encontrado.');
        $nuevo = (int) $u['activo'] === 1 ? 0 : 1;
        DB::update('usuarios', ['activo' => $nuevo, 'intentos_fallidos' => 0, 'bloqueado_hasta' => null], 'id = ?', [$id]);
        Audit::log($nuevo ? 'usuario_activado' : 'usuario_desactivado', 'usuarios', $id, $u['usuario']);
        flash('success', 'Usuario ' . ($nuevo ? 'activado' : 'desactivado') . '.');
        redirect('usuarios');
    }
}
