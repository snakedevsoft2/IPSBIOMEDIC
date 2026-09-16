<?php
declare(strict_types=1);

final class PerfilController extends Controller
{
    public function index(): void
    {
        $u = user();

        if (is_post()) {
            $accion = input('accion');

            if ($accion === 'datos') {
                $email = mb_strtolower(input('email'));
                $telefono = nullable(input('telefono'));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    flash('danger', 'Correo electrónico inválido.');
                } elseif (DB::value('SELECT 1 FROM usuarios WHERE email = ? AND id <> ?', [$email, $u['id']])) {
                    flash('danger', 'Ese correo ya está registrado en otro usuario.');
                } else {
                    DB::update('usuarios', ['email' => $email, 'telefono' => $telefono], 'id = ?', [$u['id']]);
                    Audit::log('perfil_actualizado', 'usuarios', (int) $u['id']);
                    flash('success', 'Datos actualizados.');
                }
            } elseif ($accion === 'password') {
                $actual = is_string($_POST['actual'] ?? null) ? $_POST['actual'] : '';
                $nueva = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                $confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
                if (!password_verify($actual, $u['password_hash'])) {
                    flash('danger', 'La contraseña actual no es correcta.');
                } elseif ($error = password_policy_error($nueva, $confirm)) {
                    flash('danger', $error);
                } else {
                    DB::update('usuarios', ['password_hash' => password_hash($nueva, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
                    Audit::log('password_cambiada', 'usuarios', (int) $u['id']);
                    flash('success', 'Contraseña actualizada.');
                }
            } elseif ($accion === 'firma' && $u['rol'] === 'medico') {
                [$ok, $resultado] = guardar_imagen($_FILES['firma'] ?? [], 'storage/firmas', 'firma' . $u['id'], 600, 2_000_000);
                if (!$ok) {
                    flash('danger', $resultado);
                } else {
                    if ($u['firma']) {
                        Storage::delete($u['firma']);
                    }
                    DB::update('usuarios', ['firma' => $resultado], 'id = ?', [$u['id']]);
                    Audit::log('firma_actualizada', 'usuarios', (int) $u['id']);
                    flash('success', 'Firma cargada. Aparecerá en las historias clínicas y fórmulas que finalice.');
                }
            } elseif ($accion === 'quitar_firma') {
                if ($u['firma']) {
                    Storage::delete($u['firma']);
                }
                DB::update('usuarios', ['firma' => null], 'id = ?', [$u['id']]);
                flash('success', 'Firma eliminada.');
            }
            redirect('perfil');
        }

        $this->view('perfil/index', [
            'title' => 'Mi perfil',
            'u' => $u,
            'firma' => $u['firma'] ? image_data_uri($u['firma']) : '',
        ]);
    }
}
