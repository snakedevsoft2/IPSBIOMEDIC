<?php
declare(strict_types=1);

final class PacientesController extends Controller
{
    private const CAMPOS = [
        'tipo_documento', 'numero_documento', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido',
        'fecha_nacimiento', 'sexo', 'estado_civil', 'ocupacion', 'escolaridad', 'grupo_sanguineo', 'etnia', 'nacionalidad',
        'direccion', 'barrio', 'municipio', 'departamento', 'zona', 'telefono', 'telefono2', 'email', 'eps_id', 'regimen',
        'tipo_afiliado', 'acompanante_nombre', 'acompanante_telefono', 'acompanante_parentesco', 'responsable_nombre',
        'responsable_telefono', 'responsable_parentesco', 'observaciones',
    ];

    private static function filtroBusqueda(string $q): array
    {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        return [
            "(p.numero_documento LIKE ? OR CONCAT_WS(' ', p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido) LIKE ?
              OR CONCAT_WS(' ', p.primer_nombre, p.primer_apellido) LIKE ?)",
            [addcslashes($q, '%_\\') . '%', $like, $like],
        ];
    }

    public function index(): void
    {
        $q = input('q');
        [$where, $params] = $q !== '' ? self::filtroBusqueda($q) : ['1 = 1', []];

        $total = (int) DB::value("SELECT COUNT(*) FROM pacientes p WHERE {$where}", $params);
        $pg = paginar($total, 20);
        $rows = DB::all(
            "SELECT p.*, e.nombre AS eps_nombre,
                    (SELECT MAX(c.cerrada_en) FROM consultas c WHERE c.paciente_id = p.id AND c.estado = 'cerrada') AS ultima_atencion
             FROM pacientes p LEFT JOIN eps e ON e.id = p.eps_id
             WHERE {$where}
             ORDER BY p.primer_apellido, p.primer_nombre
             LIMIT {$pg['limit']} OFFSET {$pg['offset']}",
            $params
        );

        $this->view('pacientes/index', ['title' => 'Pacientes', 'q' => $q, 'rows' => $rows, 'pg' => $pg]);
    }

    public function buscar(): void
    {
        $q = input('q');
        if (mb_strlen($q) < 2) {
            json_response([]);
        }
        [$where, $params] = self::filtroBusqueda($q);
        $rows = DB::all(
            "SELECT p.id, p.tipo_documento, p.numero_documento, p.primer_nombre, p.segundo_nombre, p.primer_apellido,
                    p.segundo_apellido, p.fecha_nacimiento, e.nombre AS eps
             FROM pacientes p LEFT JOIN eps e ON e.id = p.eps_id
             WHERE {$where} ORDER BY p.primer_apellido, p.primer_nombre LIMIT 10",
            $params
        );
        json_response(array_map(static fn ($p) => [
            'id'        => (int) $p['id'],
            'nombre'    => nombre_paciente($p),
            'documento' => $p['tipo_documento'] . ' ' . $p['numero_documento'],
            'edad'      => edad($p['fecha_nacimiento']),
            'eps'       => $p['eps'],
        ], $rows));
    }

    public function crear(): void
    {
        $this->form(null);
    }

    public function editar(): void
    {
        $paciente = DB::row('SELECT * FROM pacientes WHERE id = ?', [$this->id()]) ?? abort(404, 'Paciente no encontrado.');
        $this->form($paciente);
    }

    private function form(?array $paciente): void
    {
        $volver = input('volver') === 'admision' ? 'admision' : '';
        $data = $paciente ?? [
            'tipo_documento' => 'CC', 'numero_documento' => strtoupper(input('documento')),
            'nacionalidad' => 'Colombia', 'zona' => 'U',
        ];
        $errors = [];

        if (is_post()) {
            foreach (self::CAMPOS as $campo) {
                $data[$campo] = input($campo);
            }
            $data['numero_documento'] = strtoupper((string) preg_replace('/[\s.]+/', '', $data['numero_documento']));
            foreach (['primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido'] as $campo) {
                $data[$campo] = mb_convert_case(mb_strtolower($data[$campo]), MB_CASE_TITLE);
            }
            $data['email'] = mb_strtolower($data['email']);

            $errors = $this->validar($data, $paciente ? (int) $paciente['id'] : 0);

            if (!$errors) {
                $row = [];
                foreach (self::CAMPOS as $campo) {
                    $row[$campo] = nullable($data[$campo]);
                }
                $row['eps_id'] = $data['eps_id'] !== '' ? (int) $data['eps_id'] : null;

                if ($paciente) {
                    $id = (int) $paciente['id'];
                    DB::update('pacientes', $row, 'id = ?', [$id]);
                    Audit::log('paciente_actualizado', 'pacientes', $id);
                    flash('success', 'Datos del paciente actualizados.');
                } else {
                    $row['creado_por'] = user()['id'];
                    $id = DB::transaction(static function () use ($row): int {
                        $id = DB::insert('pacientes', $row);
                        DB::insert('antecedentes', ['paciente_id' => $id]);
                        return $id;
                    });
                    Audit::log('paciente_creado', 'pacientes', $id);
                    flash('success', 'Paciente registrado correctamente.');
                }

                if ($volver === 'admision') {
                    redirect('admisiones/crear', ['paciente_id' => $id]);
                }
                redirect('pacientes/ver', ['id' => $id]);
            }
        }

        $this->view('pacientes/form', [
            'title'    => $paciente ? 'Editar paciente' : 'Registrar paciente',
            'paciente' => $paciente,
            'data'     => $data,
            'errors'   => $errors,
            'volver'   => $volver,
            'epsLista' => eps_lista(),
        ]);
    }

    private function validar(array $d, int $id): array
    {
        $e = [];
        if (!array_key_exists($d['tipo_documento'], catalogo('tipo_documento'))) {
            $e['tipo_documento'] = 'Seleccione el tipo de documento.';
        }
        if (!preg_match('/^[A-Z0-9-]{3,20}$/', $d['numero_documento'])) {
            $e['numero_documento'] = 'Número de documento inválido (3 a 20 caracteres, sin espacios).';
        }
        foreach (['primer_nombre' => 'el primer nombre', 'primer_apellido' => 'el primer apellido', 'telefono' => 'un teléfono de contacto'] as $campo => $label) {
            if ($d[$campo] === '') {
                $e[$campo] = 'Ingrese ' . $label . '.';
            }
        }
        if (!fecha_valida($d['fecha_nacimiento']) || $d['fecha_nacimiento'] > date('Y-m-d') || $d['fecha_nacimiento'] < '1900-01-01') {
            $e['fecha_nacimiento'] = 'Fecha de nacimiento inválida.';
        }
        if (!array_key_exists($d['sexo'], catalogo('sexo'))) {
            $e['sexo'] = 'Seleccione el sexo.';
        }
        if (!in_array($d['regimen'], catalogo('regimen'), true)) {
            $e['regimen'] = 'Seleccione el régimen de afiliación.';
        }
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Correo electrónico inválido.';
        }
        if ($d['eps_id'] !== '' && !DB::value('SELECT 1 FROM eps WHERE id = ?', [(int) $d['eps_id']])) {
            $e['eps_id'] = 'Seleccione una EPS válida.';
        }
        if (!isset($e['numero_documento'])) {
            $dup = DB::row(
                'SELECT id FROM pacientes WHERE tipo_documento = ? AND numero_documento = ? AND id <> ?',
                [$d['tipo_documento'], $d['numero_documento'], $id]
            );
            if ($dup) {
                $e['numero_documento'] = 'Ya existe un paciente con este documento.';
            }
        }
        return $e;
    }

    public function ver(): void
    {
        $p = DB::row('SELECT p.*, e.nombre AS eps_nombre FROM pacientes p LEFT JOIN eps e ON e.id = p.eps_id WHERE p.id = ?', [$this->id()])
            ?? abort(404, 'Paciente no encontrado.');

        $clinico = has_role('administrador', 'medico', 'auxiliar');
        $ant = $clinico ? DB::row('SELECT * FROM antecedentes WHERE paciente_id = ?', [$p['id']]) : null;
        $consultas = $clinico ? DB::all(
            "SELECT c.id, c.estado, c.creado_en, c.cerrada_en, c.medico_id, u.nombres, u.apellidos, a.tipo_consulta,
                    (SELECT CONCAT(d.codigo, ' · ', d.descripcion) FROM consulta_diagnosticos d WHERE d.consulta_id = c.id AND d.tipo = 'Principal' LIMIT 1) AS dx,
                    (SELECT COUNT(*) FROM formulas f WHERE f.consulta_id = c.id) AS tiene_formula
             FROM consultas c JOIN usuarios u ON u.id = c.medico_id JOIN admisiones a ON a.id = c.admision_id
             WHERE c.paciente_id = ? ORDER BY c.creado_en DESC",
            [$p['id']]
        ) : [];
        $admisiones = DB::all(
            'SELECT a.*, u.nombres, u.apellidos FROM admisiones a LEFT JOIN usuarios u ON u.id = a.medico_id
             WHERE a.paciente_id = ? ORDER BY a.fecha_hora DESC LIMIT 15',
            [$p['id']]
        );

        if ($clinico) {
            Audit::log('paciente_consultado', 'pacientes', (int) $p['id']);
        }

        $this->view('pacientes/ver', [
            'title' => nombre_paciente($p), 'p' => $p, 'ant' => $ant, 'consultas' => $consultas,
            'admisiones' => $admisiones, 'clinico' => $clinico,
        ]);
    }
}
