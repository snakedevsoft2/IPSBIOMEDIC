<?php
declare(strict_types=1);

final class AuditoriaController extends Controller
{
    public const ACCIONES = [
        'login' => 'Ingreso al sistema', 'logout' => 'Cierre de sesión', 'login_fallido' => 'Intento fallido de ingreso',
        'cuenta_bloqueada' => 'Cuenta bloqueada', 'password_restablecida' => 'Contraseña restablecida',
        'password_cambiada' => 'Contraseña cambiada', 'recuperacion_enviada' => 'Correo de recuperación enviado',
        'recuperacion_error' => 'Error al enviar recuperación', 'paciente_creado' => 'Paciente registrado',
        'paciente_actualizado' => 'Paciente actualizado', 'paciente_consultado' => 'Ficha de paciente consultada',
        'admision_creada' => 'Admisión creada', 'admision_cancelada' => 'Admisión cancelada',
        'atencion_iniciada' => 'Atención iniciada', 'atencion_liberada' => 'Paciente devuelto a sala de espera',
        'historia_guardada' => 'Historia clínica guardada', 'historia_finalizada' => 'Historia clínica finalizada',
        'historia_consultada' => 'Historia clínica consultada', 'nota_aclaratoria' => 'Nota aclaratoria agregada',
        'pdf_historia' => 'Impresión de historia clínica', 'pdf_formula' => 'Impresión de fórmula médica',
        'pdf_ordenes' => 'Impresión de órdenes', 'pdf_incapacidad' => 'Impresión de incapacidad',
        'reporte_exportado' => 'Informe exportado a Excel', 'reporte_pdf' => 'Informe exportado a PDF',
        'usuario_creado' => 'Usuario creado', 'usuario_actualizado' => 'Usuario actualizado',
        'usuario_activado' => 'Usuario activado', 'usuario_desactivado' => 'Usuario desactivado',
        'perfil_actualizado' => 'Perfil actualizado', 'firma_actualizada' => 'Firma actualizada',
        'configuracion_actualizada' => 'Configuración actualizada', 'cie10_guardado' => 'CIE-10 actualizado',
        'cie10_importado' => 'CIE-10 importado', 'eps_creada' => 'EPS creada', 'respaldo_descargado' => 'Respaldo descargado',
    ];

    public function index(): void
    {
        $desde = input('desde', date('Y-m-d', strtotime('-7 days')));
        $hasta = input('hasta', date('Y-m-d'));
        if (!fecha_valida($desde)) {
            $desde = date('Y-m-d', strtotime('-7 days'));
        }
        if (!fecha_valida($hasta)) {
            $hasta = date('Y-m-d');
        }
        $usuario = input('usuario_id');
        $accion = input('accion');

        $where = 'a.creado_en >= ? AND a.creado_en < ?';
        $params = [$desde . ' 00:00:00', date('Y-m-d 00:00:00', strtotime($hasta . ' +1 day'))];
        if (ctype_digit($usuario)) {
            $where .= ' AND a.usuario_id = ?';
            $params[] = (int) $usuario;
        }
        if (array_key_exists($accion, self::ACCIONES)) {
            $where .= ' AND a.accion = ?';
            $params[] = $accion;
        }

        $total = (int) DB::value("SELECT COUNT(*) FROM auditoria a WHERE {$where}", $params);
        $pg = paginar($total, 50);
        $rows = DB::all(
            "SELECT a.*, u.nombres, u.apellidos, u.usuario, u.rol FROM auditoria a
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE {$where} ORDER BY a.creado_en DESC, a.id DESC LIMIT {$pg['limit']} OFFSET {$pg['offset']}",
            $params
        );

        $usuarios = array_column(
            DB::all("SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre FROM usuarios ORDER BY nombres"),
            'nombre',
            'id'
        );

        $this->view('auditoria/index', [
            'title' => 'Auditoría de accesos', 'rows' => $rows, 'pg' => $pg, 'usuarios' => $usuarios,
            'acciones' => self::ACCIONES, 'f' => compact('desde', 'hasta', 'usuario', 'accion'),
        ]);
    }
}
