<?php
declare(strict_types=1);

final class ConsultasController extends Controller
{
    private const TEXTOS = [
        'motivo_consulta', 'enfermedad_actual', 'revision_sistemas', 'estado_general', 'cabeza_cuello', 'torax_cardiopulmonar',
        'abdomen', 'genitourinario', 'extremidades', 'piel_faneras', 'neurologico', 'examen_otros', 'paraclinicos', 'analisis',
        'plan_manejo', 'recomendaciones', 'proximo_control',
    ];

    public function index(): void
    {
        $u = user();
        $desde = input('desde', date('Y-m-01'));
        $hasta = input('hasta', date('Y-m-d'));
        if (!fecha_valida($desde)) {
            $desde = date('Y-m-01');
        }
        if (!fecha_valida($hasta)) {
            $hasta = date('Y-m-d');
        }
        $medico = input('medico', $u['rol'] === 'medico' ? (string) $u['id'] : '');
        $estado = input('estado', 'cerrada');
        $q = input('q');

        $where = 'c.creado_en >= ? AND c.creado_en < ?';
        $params = [$desde . ' 00:00:00', date('Y-m-d 00:00:00', strtotime($hasta . ' +1 day'))];
        if (ctype_digit($medico)) {
            $where .= ' AND c.medico_id = ?';
            $params[] = (int) $medico;
        }
        if (in_array($estado, ['borrador', 'cerrada'], true)) {
            $where .= ' AND c.estado = ?';
            $params[] = $estado;
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where .= " AND (p.numero_documento LIKE ? OR CONCAT_WS(' ', p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido) LIKE ?)";
            array_push($params, $like, $like);
        }

        $total = (int) DB::value("SELECT COUNT(*) FROM consultas c JOIN pacientes p ON p.id = c.paciente_id WHERE {$where}", $params);
        $pg = paginar($total, 25);
        $rows = DB::all(
            "SELECT c.id, c.estado, c.creado_en, c.cerrada_en, c.medico_id, c.incapacidad_dias,
                    p.id AS paciente_id, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                    p.tipo_documento, p.numero_documento, p.fecha_nacimiento, p.sexo,
                    u.nombres AS med_nombres, u.apellidos AS med_apellidos, a.tipo_consulta,
                    (SELECT CONCAT(d.codigo, ' · ', d.descripcion) FROM consulta_diagnosticos d WHERE d.consulta_id = c.id AND d.tipo = 'Principal' LIMIT 1) AS dx,
                    (SELECT COUNT(*) FROM formulas f WHERE f.consulta_id = c.id) AS tiene_formula,
                    (SELECT COUNT(*) FROM ordenes o WHERE o.consulta_id = c.id) AS tiene_ordenes
             FROM consultas c
             JOIN pacientes p ON p.id = c.paciente_id
             JOIN usuarios u ON u.id = c.medico_id
             JOIN admisiones a ON a.id = c.admision_id
             WHERE {$where}
             ORDER BY c.creado_en DESC
             LIMIT {$pg['limit']} OFFSET {$pg['offset']}",
            $params
        );

        $this->view('consultas/index', [
            'title' => 'Historias clínicas', 'rows' => $rows, 'pg' => $pg, 'medicos' => medicos_lista(),
            'f' => compact('desde', 'hasta', 'medico', 'estado', 'q'),
        ]);
    }

    public function atender(): void
    {
        $this->requirePost();
        $u = user();
        $admisionId = $this->id('admision_id');

        [$ok, $resultado] = DB::transaction(static function () use ($admisionId, $u): array {
            $a = DB::row('SELECT * FROM admisiones WHERE id = ? FOR UPDATE', [$admisionId]);
            if (!$a) {
                return [false, 'Admisión no encontrada.'];
            }
            $existente = DB::row('SELECT id, medico_id FROM consultas WHERE admision_id = ?', [$admisionId]);
            if ($existente) {
                return (int) $existente['medico_id'] === (int) $u['id']
                    ? [true, (int) $existente['id']]
                    : [false, 'Este paciente ya está siendo atendido por otro médico.'];
            }
            if ($a['estado'] !== 'en_espera') {
                return [false, 'Esta admisión ya no está en espera.'];
            }
            if ($a['medico_id'] !== null && (int) $a['medico_id'] !== (int) $u['id']) {
                return [false, 'Este paciente está asignado a otro médico.'];
            }
            DB::update('admisiones', ['estado' => 'en_atencion', 'medico_id' => $u['id']], 'id = ?', [$admisionId]);
            $id = DB::insert('consultas', [
                'admision_id'     => $admisionId,
                'paciente_id'     => $a['paciente_id'],
                'medico_id'       => $u['id'],
                'motivo_consulta' => $a['motivo'],
            ]);
            return [true, $id];
        });

        if (!$ok) {
            flash('warning', $resultado);
            redirect('admisiones');
        }
        Audit::log('atencion_iniciada', 'consultas', $resultado);
        redirect('consultas/editar', ['id' => $resultado]);
    }

    public function editar(): void
    {
        $u = user();
        $bundle = Clinica::consulta($this->id()) ?? abort(404, 'Atención no encontrada.');
        $c = $bundle['c'];

        if ($c['estado'] === 'cerrada') {
            flash('info', 'Esta historia clínica ya fue finalizada. Solo se pueden agregar notas aclaratorias.');
            redirect('consultas/ver', ['id' => $c['id']]);
        }
        if ((int) $c['medico_id'] !== (int) $u['id']) {
            abort(403, 'Solo el médico que inició la atención puede diligenciar esta historia clínica.');
        }

        if (is_post()) {
            $accion = input('accion');

            if ($accion === 'liberar') {
                DB::transaction(static function () use ($c): void {
                    DB::run("DELETE FROM consultas WHERE id = ? AND estado = 'borrador'", [$c['id']]);
                    DB::update('admisiones', ['estado' => 'en_espera', 'medico_id' => null], 'id = ?', [$c['admision_id']]);
                });
                Audit::log('atencion_liberada', 'admisiones', (int) $c['admision_id']);
                flash('info', 'El paciente volvió a la sala de espera.');
                redirect('admisiones');
            }

            $datos = $this->recolectar();
            $this->guardar($c, $datos);

            if ($accion === 'finalizar') {
                $faltantes = $this->validarCierre($datos);
                if ($faltantes) {
                    flash('danger', 'No se pudo finalizar. Falta: ' . implode('; ', $faltantes) . '. Los cambios quedaron guardados como borrador.');
                    redirect('consultas/editar', ['id' => $c['id']]);
                }
                DB::transaction(static function () use ($c, $datos): void {
                    $ahora = date('Y-m-d H:i:s');
                    DB::update('consultas', [
                        'antecedentes_snapshot' => json_encode($datos['antecedentes'], JSON_UNESCAPED_UNICODE),
                        'estado'                => 'cerrada',
                        'cerrada_en'            => $ahora,
                    ], "id = ? AND estado = 'borrador'", [$c['id']]);
                    DB::update('admisiones', ['estado' => 'atendida', 'atendida_en' => $ahora], 'id = ?', [$c['admision_id']]);
                });
                Audit::log('historia_finalizada', 'consultas', (int) $c['id']);
                flash('success', 'Atención finalizada. Ya puede imprimir la historia clínica y la fórmula médica.');
                redirect('consultas/ver', ['id' => $c['id']]);
            }

            Audit::log('historia_guardada', 'consultas', (int) $c['id']);
            flash('success', 'Borrador guardado a las ' . date('h:i a') . '.');
            redirect('consultas/editar', ['id' => $c['id']]);
        }

        $previas = DB::all(
            "SELECT c.id, c.cerrada_en, u.nombres, u.apellidos,
                    (SELECT CONCAT(d.codigo, ' · ', d.descripcion) FROM consulta_diagnosticos d WHERE d.consulta_id = c.id AND d.tipo = 'Principal' LIMIT 1) AS dx
             FROM consultas c JOIN usuarios u ON u.id = c.medico_id
             WHERE c.paciente_id = ? AND c.estado = 'cerrada' ORDER BY c.cerrada_en DESC LIMIT 5",
            [$c['paciente_id']]
        );

        $this->view('consultas/editar', $bundle + [
            'title'        => 'Atención médica',
            'previas'      => $previas,
            'medicamentos' => DB::all('SELECT nombre, concentracion, forma_farmaceutica FROM medicamentos WHERE activo = 1 ORDER BY nombre'),
        ]);
    }

    private static function campoTexto(array $r, string $k, int $len): ?string
    {
        return nullable(mb_substr(is_string($r[$k] ?? null) ? trim(utf8_limpio($r[$k])) : '', 0, $len));
    }

    private function parseDiagnosticos(): array
    {
        $dx = [];
        foreach (input_array('dx') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $codigo = strtoupper(str_replace(['.', ' '], '', (string) self::campoTexto($row, 'codigo', 12)));
            $descripcion = (string) self::campoTexto($row, 'descripcion', 255);
            if ($codigo === '' && $descripcion === '') {
                continue;
            }
            if ($codigo !== '' && ($oficial = DB::value('SELECT descripcion FROM cie10 WHERE codigo = ?', [$codigo]))) {
                $descripcion = $oficial;
            }
            $clase = self::campoTexto($row, 'clase', 40);
            $dx[] = [
                'codigo'      => mb_substr($codigo, 0, 10),
                'descripcion' => $descripcion,
                'tipo'        => $dx ? 'Relacionado' : 'Principal',
                'clase'       => in_array($clase, catalogo('clase_diagnostico'), true) ? $clase : 'Impresión diagnóstica',
                'orden'       => count($dx),
            ];
        }
        return $dx;
    }

    private function parseMedicamentos(): array
    {
        $items = [];
        foreach (input_array('med') as $row) {
            if (!is_array($row) || self::campoTexto($row, 'medicamento', 200) === null) {
                continue;
            }
            $cantidad = max(1, min(9999, (int) ($row['cantidad'] ?? 1)));
            $items[] = [
                'medicamento'        => self::campoTexto($row, 'medicamento', 200),
                'concentracion'      => self::campoTexto($row, 'concentracion', 60),
                'forma_farmaceutica' => self::campoTexto($row, 'forma_farmaceutica', 60),
                'via'                => self::campoTexto($row, 'via', 40),
                'dosis'              => self::campoTexto($row, 'dosis', 80),
                'frecuencia'         => self::campoTexto($row, 'frecuencia', 80),
                'duracion'           => self::campoTexto($row, 'duracion', 60),
                'cantidad'           => $cantidad,
                'cantidad_letras'    => numero_letras($cantidad),
                'indicaciones'       => self::campoTexto($row, 'indicaciones', 1000),
                'orden'              => count($items),
            ];
        }
        return $items;
    }

    private function recolectar(): array
    {
        $consulta = [];
        foreach (self::TEXTOS as $k) {
            $consulta[$k] = nullable(input($k));
        }
        $consulta['proximo_control'] = $consulta['proximo_control'] !== null ? mb_substr($consulta['proximo_control'], 0, 120) : null;

        $enteros = ['ta_sistolica' => [40, 300], 'ta_diastolica' => [20, 200], 'frecuencia_cardiaca' => [20, 250],
            'frecuencia_respiratoria' => [5, 80], 'saturacion' => [40, 100], 'glucometria' => [10, 900], 'incapacidad_dias' => [1, 180]];
        foreach ($enteros as $k => [$min, $max]) {
            $v = input_int($k);
            $consulta[$k] = ($v !== null && $v >= $min && $v <= $max) ? $v : null;
        }
        $decimales = ['temperatura' => [30, 45], 'peso' => [0.3, 400], 'talla' => [20, 250], 'perimetro_abdominal' => [20, 250]];
        foreach ($decimales as $k => [$min, $max]) {
            $v = input_decimal($k);
            $consulta[$k] = ($v !== null && $v >= $min && $v <= $max) ? $v : null;
        }
        $consulta['imc'] = ($consulta['peso'] && $consulta['talla']) ? round($consulta['peso'] / (($consulta['talla'] / 100) ** 2), 2) : null;
        $desde = input('incapacidad_desde');
        $consulta['incapacidad_desde'] = $consulta['incapacidad_dias'] ? (fecha_valida($desde) ? $desde : date('Y-m-d')) : null;

        $antecedentes = [];
        foreach (array_keys(Clinica::ANTECEDENTES) as $k) {
            $antecedentes[$k] = nullable(input('ant_' . $k));
        }

        $dx = $this->parseDiagnosticos();
        $items = $this->parseMedicamentos();

        $ordenes = [];
        foreach (input_array('ord') as $row) {
            if (!is_array($row) || self::campoTexto($row, 'descripcion', 255) === null) {
                continue;
            }
            $tipo = self::campoTexto($row, 'tipo', 40);
            $ordenes[] = [
                'tipo'        => in_array($tipo, catalogo('tipo_orden'), true) ? $tipo : 'Otro',
                'codigo'      => self::campoTexto($row, 'codigo', 12),
                'descripcion' => self::campoTexto($row, 'descripcion', 255),
                'cantidad'    => max(1, min(999, (int) ($row['cantidad'] ?? 1))),
                'observacion' => self::campoTexto($row, 'observacion', 1000),
                'orden'       => count($ordenes),
            ];
        }

        return [
            'consulta'     => $consulta,
            'antecedentes' => $antecedentes,
            'dx'           => $dx,
            'items'        => $items,
            'formula_obs'  => nullable(input('formula_observaciones')),
            'ordenes'      => $ordenes,
        ];
    }

    private function guardar(array $c, array $d): void
    {
        DB::transaction(static function () use ($c, $d): void {
            DB::update('consultas', $d['consulta'], 'id = ?', [$c['id']]);

            $ant = $d['antecedentes'] + ['actualizado_por' => user()['id'], 'actualizado_en' => date('Y-m-d H:i:s')];
            if (DB::value('SELECT 1 FROM antecedentes WHERE paciente_id = ?', [$c['paciente_id']])) {
                DB::update('antecedentes', $ant, 'paciente_id = ?', [$c['paciente_id']]);
            } else {
                DB::insert('antecedentes', ['paciente_id' => $c['paciente_id']] + $ant);
            }

            DB::run('DELETE FROM consulta_diagnosticos WHERE consulta_id = ?', [$c['id']]);
            foreach ($d['dx'] as $row) {
                DB::insert('consulta_diagnosticos', ['consulta_id' => $c['id']] + $row);
            }

            DB::run('DELETE FROM formulas WHERE consulta_id = ?', [$c['id']]);
            if ($d['items']) {
                $formulaId = DB::insert('formulas', ['consulta_id' => $c['id'], 'observaciones' => $d['formula_obs']]);
                foreach ($d['items'] as $item) {
                    DB::insert('formula_items', ['formula_id' => $formulaId] + $item);
                }
            }

            DB::run('DELETE FROM ordenes WHERE consulta_id = ?', [$c['id']]);
            foreach ($d['ordenes'] as $orden) {
                DB::insert('ordenes', ['consulta_id' => $c['id']] + $orden);
            }
        });
    }

    private function validarCierre(array $d): array
    {
        $faltantes = [];
        if (!$d['consulta']['motivo_consulta']) {
            $faltantes[] = 'motivo de consulta';
        }
        if (!$d['consulta']['enfermedad_actual']) {
            $faltantes[] = 'enfermedad actual';
        }
        if (!$d['dx']) {
            $faltantes[] = 'al menos un diagnóstico CIE-10';
        } else {
            foreach ($d['dx'] as $x) {
                if (!preg_match('/^[A-Z]\d{2}[0-9A-Z]{0,2}$/', $x['codigo']) || $x['descripcion'] === '') {
                    $faltantes[] = 'código CIE-10 y descripción válidos en todos los diagnósticos';
                    break;
                }
            }
        }
        if (!$d['consulta']['plan_manejo']) {
            $faltantes[] = 'plan de manejo / conducta';
        }
        return $faltantes;
    }

    public function ver(): void
    {
        $bundle = Clinica::consulta($this->id()) ?? abort(404, 'Atención no encontrada.');
        Audit::log('historia_consultada', 'consultas', (int) $bundle['c']['id']);
        $this->view('consultas/ver', $bundle + ['title' => 'Historia clínica']);
    }

    public function rapida(): void
    {
        $u = user();
        $pacienteId = $this->id('paciente_id');
        $documento = strtoupper(input('documento'));
        $noEncontrado = '';

        if (!$pacienteId && $documento !== '') {
            $encontrados = DB::all('SELECT id FROM pacientes WHERE numero_documento = ? LIMIT 2', [$documento]);
            if (count($encontrados) === 1) {
                redirect('consultas/rapida', ['paciente_id' => $encontrados[0]['id']]);
            }
            $noEncontrado = $documento;
        }

        $paciente = $pacienteId
            ? DB::row('SELECT p.*, e.nombre AS eps_nombre FROM pacientes p LEFT JOIN eps e ON e.id = p.eps_id WHERE p.id = ?', [$pacienteId])
            : null;
        if ($pacienteId && !$paciente) {
            abort(404, 'Paciente no encontrado.');
        }

        $activa = $paciente ? DB::value(
            "SELECT id FROM admisiones WHERE paciente_id = ? AND estado IN ('en_espera', 'en_atencion') AND fecha_hora >= CURDATE()",
            [$paciente['id']]
        ) : null;

        $data = ['motivo_consulta' => '', 'enfermedad_actual' => '', 'plan_manejo' => '', 'proximo_control' => ''];
        $errors = [];
        $dx = [];
        $items = [];

        if (is_post() && $paciente && !$activa) {
            foreach (array_keys($data) as $k) {
                $data[$k] = nullable(mb_substr(trim(utf8_limpio(input($k))), 0, $k === 'proximo_control' ? 120 : 6000));
            }
            $dx = $this->parseDiagnosticos();
            $items = $this->parseMedicamentos();
            $formulaObs = nullable(input('formula_observaciones'));

            if (!$data['motivo_consulta']) {
                $errors['motivo_consulta'] = 'Describa el motivo de la consulta.';
            }
            if (!$data['enfermedad_actual']) {
                $errors['enfermedad_actual'] = 'Describa la evolución del paciente.';
            }
            if (!$dx) {
                $errors['dx'] = 'Agregue al menos un diagnóstico CIE-10.';
            } else {
                foreach ($dx as $x) {
                    if (!preg_match('/^[A-Z]\d{2}[0-9A-Z]{0,2}$/', $x['codigo']) || $x['descripcion'] === '') {
                        $errors['dx'] = 'Ingrese un código CIE-10 y descripción válidos en todos los diagnósticos.';
                        break;
                    }
                }
            }
            if (!$data['plan_manejo']) {
                $errors['plan_manejo'] = 'Describa el plan de manejo / conducta.';
            }

            if (!$errors) {
                $consultaId = DB::transaction(function () use ($u, $paciente, $data, $dx, $items, $formulaObs): int {
                    $ahora = date('Y-m-d H:i:s');
                    $admisionId = DB::insert('admisiones', [
                        'paciente_id'   => $paciente['id'],
                        'medico_id'     => $u['id'],
                        'fecha_hora'    => $ahora,
                        'tipo_consulta' => 'Control',
                        'modalidad'     => 'Intramural',
                        'motivo'        => $data['motivo_consulta'],
                        'finalidad'     => 'Control y seguimiento',
                        'causa_externa' => 'Enfermedad general',
                        'eps_id'        => $paciente['eps_id'],
                        'regimen'       => $paciente['regimen'],
                        'estado'        => 'atendida',
                        'creado_por'    => $u['id'],
                        'atendida_en'   => $ahora,
                    ]);
                    $consultaId = DB::insert('consultas', [
                        'admision_id'       => $admisionId,
                        'paciente_id'       => $paciente['id'],
                        'medico_id'         => $u['id'],
                        'motivo_consulta'   => $data['motivo_consulta'],
                        'enfermedad_actual' => $data['enfermedad_actual'],
                        'plan_manejo'       => $data['plan_manejo'],
                        'proximo_control'   => $data['proximo_control'],
                        'estado'            => 'cerrada',
                        'cerrada_en'        => $ahora,
                    ]);
                    foreach ($dx as $row) {
                        DB::insert('consulta_diagnosticos', ['consulta_id' => $consultaId] + $row);
                    }
                    if ($items) {
                        $formulaId = DB::insert('formulas', ['consulta_id' => $consultaId, 'observaciones' => $formulaObs]);
                        foreach ($items as $item) {
                            DB::insert('formula_items', ['formula_id' => $formulaId] + $item);
                        }
                    }
                    return $consultaId;
                });
                Audit::log('evolucion_rapida', 'consultas', $consultaId, nombre_paciente($paciente));
                flash('success', 'Evolución registrada. Ya puede imprimir la historia clínica y la fórmula.');
                redirect('consultas/ver', ['id' => $consultaId]);
            }
        }

        $previas = $paciente ? DB::all(
            "SELECT c.id, c.cerrada_en, u.nombres, u.apellidos,
                    (SELECT CONCAT(d.codigo, ' · ', d.descripcion) FROM consulta_diagnosticos d WHERE d.consulta_id = c.id AND d.tipo = 'Principal' LIMIT 1) AS dx
             FROM consultas c JOIN usuarios u ON u.id = c.medico_id
             WHERE c.paciente_id = ? AND c.estado = 'cerrada' ORDER BY c.cerrada_en DESC LIMIT 5",
            [$paciente['id']]
        ) : [];

        $this->view('consultas/rapida', [
            'title'        => 'Evolución y fórmula rápida',
            'paciente'     => $paciente,
            'noEncontrado' => $noEncontrado,
            'activa'       => $activa,
            'data'         => $data,
            'errors'       => $errors,
            'dx'           => $dx,
            'items'        => $items,
            'previas'      => $previas,
            'medicamentos' => DB::all('SELECT nombre, concentracion, forma_farmaceutica FROM medicamentos WHERE activo = 1 ORDER BY nombre'),
        ]);
    }

    public function nota(): void
    {
        $this->requirePost();
        $c = DB::row('SELECT id, estado FROM consultas WHERE id = ?', [$this->id('consulta_id')]) ?? abort(404, 'Atención no encontrada.');
        $nota = input('nota');
        if ($c['estado'] !== 'cerrada' || mb_strlen($nota) < 5) {
            flash('warning', 'Escriba la nota aclaratoria (mínimo 5 caracteres).');
        } else {
            $id = DB::insert('notas_aclaratorias', ['consulta_id' => $c['id'], 'usuario_id' => user()['id'], 'nota' => $nota]);
            Audit::log('nota_aclaratoria', 'consultas', (int) $c['id'], 'Nota #' . $id);
            flash('success', 'Nota aclaratoria agregada a la historia clínica.');
        }
        redirect('consultas/ver', ['id' => $c['id']]);
    }
}
