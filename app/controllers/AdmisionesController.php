<?php
declare(strict_types=1);

final class AdmisionesController extends Controller
{
    public function index(): void
    {
        $u = user();
        $fecha = input('fecha', date('Y-m-d'));
        if (!fecha_valida($fecha)) {
            $fecha = date('Y-m-d');
        }
        $estado = input('estado', 'activos');
        $medico = input('medico', $u['rol'] === 'medico' ? 'mios' : '');

        $where = 'a.fecha_hora >= ? AND a.fecha_hora < ?';
        $params = [$fecha . ' 00:00:00', date('Y-m-d 00:00:00', strtotime($fecha . ' +1 day'))];
        if ($medico === 'mios') {
            $where .= ' AND (a.medico_id = ? OR a.medico_id IS NULL)';
            $params[] = $u['id'];
        } elseif (ctype_digit($medico)) {
            $where .= ' AND a.medico_id = ?';
            $params[] = (int) $medico;
        }

        $conteos = array_map('intval', array_column(
            DB::all("SELECT a.estado, COUNT(*) AS n FROM admisiones a WHERE {$where} GROUP BY a.estado", $params),
            'n',
            'estado'
        ));

        $whereLista = $where;
        $paramsLista = $params;
        if ($estado === 'activos') {
            $whereLista .= " AND a.estado IN ('en_espera', 'en_atencion')";
        } elseif (in_array($estado, ['en_espera', 'en_atencion', 'atendida', 'cancelada'], true)) {
            $whereLista .= ' AND a.estado = ?';
            $paramsLista[] = $estado;
        } else {
            $estado = 'todos';
        }

        $rows = DB::all(
            "SELECT a.*, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido, p.tipo_documento,
                    p.numero_documento, p.fecha_nacimiento, p.sexo, e.nombre AS eps_nombre,
                    u.nombres AS med_nombres, u.apellidos AS med_apellidos, c.id AS consulta_id
             FROM admisiones a
             JOIN pacientes p ON p.id = a.paciente_id
             LEFT JOIN eps e ON e.id = a.eps_id
             LEFT JOIN usuarios u ON u.id = a.medico_id
             LEFT JOIN consultas c ON c.admision_id = a.id
             WHERE {$whereLista}
             ORDER BY FIELD(a.estado, 'en_atencion', 'en_espera', 'atendida', 'cancelada'), (a.prioridad = 'Prioritaria') DESC, a.fecha_hora",
            $paramsLista
        );

        $this->view('admisiones/index', [
            'title'   => 'Sala de espera',
            'u'       => $u,
            'fecha'   => $fecha,
            'estado'  => $estado,
            'medico'  => $medico,
            'conteos' => $conteos,
            'rows'    => $rows,
            'medicos' => medicos_lista(),
        ]);
    }

    public function crear(): void
    {
        $pacienteId = $this->id('paciente_id');
        $documento = strtoupper(input('documento'));
        $noEncontrado = '';

        if (!$pacienteId && $documento !== '') {
            $encontrados = DB::all('SELECT id FROM pacientes WHERE numero_documento = ? LIMIT 2', [$documento]);
            if (count($encontrados) === 1) {
                redirect('admisiones/crear', ['paciente_id' => $encontrados[0]['id']]);
            }
            $noEncontrado = $documento;
        }

        $paciente = $pacienteId
            ? DB::row('SELECT p.*, e.nombre AS eps_nombre FROM pacientes p LEFT JOIN eps e ON e.id = p.eps_id WHERE p.id = ?', [$pacienteId])
            : null;
        if ($pacienteId && !$paciente) {
            abort(404, 'Paciente no encontrado.');
        }

        $data = [
            'medico_id' => '', 'tipo_consulta' => 'Primera vez', 'modalidad' => 'Intramural', 'motivo' => '',
            'finalidad' => 'Atención de enfermedad general', 'causa_externa' => 'Enfermedad general',
            'eps_id' => (string) ($paciente['eps_id'] ?? ''), 'regimen' => $paciente['regimen'] ?? '',
            'numero_autorizacion' => '', 'valor_copago' => '0', 'prioridad' => 'Normal',
        ];
        if ($paciente && DB::value("SELECT 1 FROM consultas WHERE paciente_id = ? AND estado = 'cerrada' LIMIT 1", [$paciente['id']])) {
            $data['tipo_consulta'] = 'Control';
        }

        $errors = [];
        if (is_post() && $paciente) {
            foreach (array_keys($data) as $k) {
                $data[$k] = input($k);
            }
            $data['valor_copago'] = preg_replace('/\D/', '', $data['valor_copago']) ?: '0';

            foreach (['tipo_consulta', 'modalidad', 'finalidad', 'causa_externa', 'prioridad', 'regimen'] as $campo) {
                if (!in_array($data[$campo], catalogo($campo), true)) {
                    $errors[$campo] = 'Seleccione una opción válida.';
                }
            }
            if ($data['medico_id'] !== '' && !array_key_exists((int) $data['medico_id'], medicos_lista())) {
                $errors['medico_id'] = 'Seleccione un médico activo.';
            }
            if ($data['eps_id'] !== '' && !DB::value('SELECT 1 FROM eps WHERE id = ?', [(int) $data['eps_id']])) {
                $errors['eps_id'] = 'Seleccione una EPS válida.';
            }
            if (mb_strlen($data['motivo']) < 3) {
                $errors['motivo'] = 'Describa brevemente el motivo de la consulta.';
            }
            $activa = DB::value(
                "SELECT id FROM admisiones WHERE paciente_id = ? AND estado IN ('en_espera', 'en_atencion') AND fecha_hora >= CURDATE()",
                [$paciente['id']]
            );
            if ($activa) {
                $errors['general'] = 'El paciente ya tiene una admisión activa hoy (en espera o en atención).';
            }

            if (!$errors) {
                $id = DB::insert('admisiones', [
                    'paciente_id'         => $paciente['id'],
                    'medico_id'           => $data['medico_id'] !== '' ? (int) $data['medico_id'] : null,
                    'fecha_hora'          => date('Y-m-d H:i:s'),
                    'tipo_consulta'       => $data['tipo_consulta'],
                    'modalidad'           => $data['modalidad'],
                    'motivo'              => $data['motivo'],
                    'finalidad'           => $data['finalidad'],
                    'causa_externa'       => $data['causa_externa'],
                    'eps_id'              => $data['eps_id'] !== '' ? (int) $data['eps_id'] : null,
                    'regimen'             => $data['regimen'],
                    'numero_autorizacion' => nullable($data['numero_autorizacion']),
                    'valor_copago'        => (int) $data['valor_copago'],
                    'prioridad'           => $data['prioridad'],
                    'creado_por'          => user()['id'],
                ]);
                Audit::log('admision_creada', 'admisiones', $id, nombre_paciente($paciente));
                flash('success', nombre_paciente($paciente) . ' fue admitido y enviado a la sala de espera.');
                redirect('admisiones');
            }
        }

        $this->view('admisiones/crear', [
            'title'        => 'Nueva admisión',
            'paciente'     => $paciente,
            'data'         => $data,
            'errors'       => $errors,
            'noEncontrado' => $noEncontrado,
            'medicos'      => medicos_lista(),
            'epsLista'     => eps_lista(),
        ]);
    }

    public function cancelar(): void
    {
        $this->requirePost();
        $a = DB::row('SELECT * FROM admisiones WHERE id = ?', [$this->id()]) ?? abort(404, 'Admisión no encontrada.');
        $motivo = mb_substr(input('motivo'), 0, 255);
        $filas = DB::update(
            'admisiones',
            ['estado' => 'cancelada', 'motivo_cancelacion' => $motivo !== '' ? $motivo : 'Sin motivo registrado'],
            "id = ? AND estado = 'en_espera'",
            [$a['id']]
        );
        if ($filas) {
            Audit::log('admision_cancelada', 'admisiones', (int) $a['id'], $motivo);
            flash('success', 'Admisión cancelada.');
        } else {
            flash('warning', 'Solo se pueden cancelar admisiones que siguen en espera.');
        }
        redirect('admisiones', ['fecha' => substr($a['fecha_hora'], 0, 10)]);
    }
}
