<?php
declare(strict_types=1);

final class ConfiguracionController extends Controller
{
    private const CAMPOS_IPS = ['ips_nombre', 'ips_nit', 'ips_codigo_habilitacion', 'ips_direccion', 'ips_ciudad',
        'ips_telefono', 'ips_email', 'ips_web', 'pie_reportes'];

    public function index(): void
    {
        $tab = input('tab', 'ips');
        if (!in_array($tab, ['ips', 'logos', 'correo', 'seguridad', 'eps'], true)) {
            $tab = 'ips';
        }
        $this->view('configuracion/index', [
            'title' => 'Configuración de la IPS',
            'tab'   => $tab,
            's'     => settings(true),
            'eps'   => DB::all('SELECT * FROM eps ORDER BY activo DESC, nombre'),
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $seccion = input('seccion');

        if ($seccion === 'ips') {
            if (input('ips_nombre') === '') {
                flash('danger', 'El nombre de la IPS es obligatorio.');
                redirect('configuracion');
            }
            foreach (self::CAMPOS_IPS as $clave) {
                set_setting($clave, mb_substr(input($clave), 0, 500));
            }
            flash('success', 'Datos de la IPS actualizados. Ya aparecen en los reportes PDF.');
        } elseif ($seccion === 'logos') {
            foreach (['logo_claro' => 'claro', 'logo_oscuro' => 'oscuro'] as $clave => $etiqueta) {
                if (input('quitar_' . $clave) !== '') {
                    $anterior = setting($clave);
                    if ($anterior && is_file(BASE_PATH . '/' . $anterior)) {
                        @unlink(BASE_PATH . '/' . $anterior);
                    }
                    set_setting($clave, '');
                    continue;
                }
                if (($_FILES[$clave]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                [$ok, $resultado] = guardar_imagen($_FILES[$clave], 'assets/uploads', $clave, 1400, 3_000_000);
                if (!$ok) {
                    flash('danger', 'Logo (' . $etiqueta . '): ' . $resultado);
                    continue;
                }
                $anterior = setting($clave);
                if ($anterior && is_file(BASE_PATH . '/' . $anterior)) {
                    @unlink(BASE_PATH . '/' . $anterior);
                }
                set_setting($clave, $resultado);
            }
            flash('success', 'Logos actualizados.');
        } elseif ($seccion === 'correo') {
            set_setting('smtp_host', input('smtp_host'));
            set_setting('smtp_puerto', (string) max(0, (int) input('smtp_puerto')));
            set_setting('smtp_seguridad', in_array(input('smtp_seguridad'), ['ssl', 'tls', 'none'], true) ? input('smtp_seguridad') : 'ssl');
            set_setting('smtp_usuario', input('smtp_usuario'));
            set_setting('smtp_remitente', input('smtp_remitente'));
            $clave = is_string($_POST['smtp_clave'] ?? null) ? $_POST['smtp_clave'] : '';
            if ($clave !== '') {
                set_setting('smtp_clave', encrypt_value($clave));
            }
            flash('success', 'Configuración de correo guardada. Pruébela con el botón «Enviar correo de prueba».');
        } elseif ($seccion === 'seguridad') {
            set_setting('sesion_minutos', (string) min(480, max(5, (int) input('sesion_minutos'))));
            flash('success', 'Configuración de seguridad guardada.');
        }

        Audit::log('configuracion_actualizada', null, null, $seccion);
        redirect('configuracion', ['tab' => $seccion === 'logos' ? 'logos' : ($seccion ?: 'ips')]);
    }

    public function probarCorreo(): void
    {
        $this->requirePost();
        $destino = mb_strtolower(input('destino'));
        if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Escriba un correo válido para la prueba.');
            redirect('configuracion', ['tab' => 'correo']);
        }
        [$ok, $error] = Mailer::send(
            $destino,
            'Prueba',
            'Correo de prueba · ' . setting('ips_nombre', 'IPS BIOMED'),
            '<p>Este es un correo de prueba enviado desde el sistema de historia clínica.</p>'
            . '<p>Si lo recibió, la recuperación de contraseñas funcionará correctamente.</p>'
        );
        flash($ok ? 'success' : 'danger', $ok ? 'Correo de prueba enviado a ' . $destino . '.' : 'No se pudo enviar: ' . $error);
        redirect('configuracion', ['tab' => 'correo']);
    }

    public function eps(): void
    {
        $this->requirePost();
        $accion = input('accion');

        if ($accion === 'agregar') {
            $nombre = mb_substr(input('nombre'), 0, 150);
            if ($nombre === '') {
                flash('danger', 'Escriba el nombre de la EPS.');
            } elseif (DB::value('SELECT 1 FROM eps WHERE nombre = ?', [$nombre])) {
                flash('warning', 'Esa EPS ya está registrada.');
            } else {
                $id = DB::insert('eps', ['nombre' => $nombre, 'codigo' => nullable(input('codigo'))]);
                Audit::log('eps_creada', 'eps', $id, $nombre);
                flash('success', 'EPS agregada.');
            }
        } elseif ($accion === 'estado') {
            $id = $this->id();
            $eps = DB::row('SELECT * FROM eps WHERE id = ?', [$id]) ?? abort(404, 'EPS no encontrada.');
            DB::update('eps', ['activo' => (int) $eps['activo'] === 1 ? 0 : 1], 'id = ?', [$id]);
            flash('success', 'EPS actualizada.');
        }
        redirect('configuracion', ['tab' => 'eps']);
    }
}
