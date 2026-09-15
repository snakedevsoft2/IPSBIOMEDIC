<?php
declare(strict_types=1);

final class ReportesController extends Controller
{
    private const FROM = 'FROM consultas c
        JOIN admisiones a ON a.id = c.admision_id
        JOIN pacientes p ON p.id = c.paciente_id
        JOIN usuarios u ON u.id = c.medico_id
        LEFT JOIN eps e ON e.id = a.eps_id';

    private function filtros(): array
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
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $medico = $u['rol'] === 'medico' ? (string) $u['id'] : input('medico');
        $regimen = input('regimen');
        $eps = input('eps_id');

        $where = "c.estado = 'cerrada' AND c.cerrada_en >= ? AND c.cerrada_en < ?";
        $params = [$desde . ' 00:00:00', date('Y-m-d 00:00:00', strtotime($hasta . ' +1 day'))];
        if (ctype_digit($medico)) {
            $where .= ' AND c.medico_id = ?';
            $params[] = (int) $medico;
        }
        if (in_array($regimen, catalogo('regimen'), true)) {
            $where .= ' AND a.regimen = ?';
            $params[] = $regimen;
        }
        if (ctype_digit($eps)) {
            $where .= ' AND a.eps_id = ?';
            $params[] = (int) $eps;
        }
        return compact('desde', 'hasta', 'medico', 'regimen', 'eps', 'where', 'params');
    }

    private function filas(array $f, int $limit = 0, int $offset = 0): array
    {
        $lim = $limit > 0 ? " LIMIT {$limit} OFFSET {$offset}" : '';
        return DB::all(
            "SELECT c.id AS consulta_id, c.cerrada_en, c.incapacidad_dias,
                    p.tipo_documento, p.numero_documento, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                    p.fecha_nacimiento, p.sexo, p.municipio, p.zona,
                    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, c.cerrada_en) AS edad_anios,
                    e.nombre AS eps, a.regimen, a.tipo_consulta, a.modalidad, a.finalidad, a.causa_externa, a.numero_autorizacion, a.valor_copago,
                    u.nombres AS med_nombres, u.apellidos AS med_apellidos, u.registro_medico,
                    (SELECT d.codigo FROM consulta_diagnosticos d WHERE d.consulta_id = c.id AND d.tipo = 'Principal' LIMIT 1) AS dx_codigo,
                    (SELECT d.descripcion FROM consulta_diagnosticos d WHERE d.consulta_id = c.id AND d.tipo = 'Principal' LIMIT 1) AS dx_descripcion,
                    (SELECT GROUP_CONCAT(d.codigo SEPARATOR ', ') FROM consulta_diagnosticos d WHERE d.consulta_id = c.id AND d.tipo = 'Relacionado') AS dx_relacionados,
                    (SELECT COUNT(*) FROM formula_items fi JOIN formulas fo ON fo.id = fi.formula_id WHERE fo.consulta_id = c.id) AS medicamentos
             " . self::FROM . "
             WHERE {$f['where']}
             ORDER BY c.cerrada_en" . $lim,
            $f['params']
        );
    }

    public function index(): void
    {
        $f = $this->filtros();
        $total = (int) DB::value('SELECT COUNT(*) ' . self::FROM . " WHERE {$f['where']}", $f['params']);
        $pacientes = (int) DB::value('SELECT COUNT(DISTINCT c.paciente_id) ' . self::FROM . " WHERE {$f['where']}", $f['params']);

        $agrupar = function (string $campo, string $alias) use ($f): array {
            return array_map(
                static fn ($r) => [$r['k'] ?: 'Sin dato', (int) $r['n']],
                DB::all("SELECT {$campo} AS k, COUNT(*) AS n " . self::FROM . " WHERE {$f['where']} GROUP BY {$campo} ORDER BY n DESC LIMIT 12", $f['params'])
            );
        };

        $sexos = catalogo('sexo');
        $porSexo = array_map(static fn ($r) => [$sexos[$r[0]] ?? $r[0], $r[1]], $agrupar('p.sexo', 'sexo'));

        $topDx = DB::all(
            "SELECT d.codigo, MAX(d.descripcion) AS descripcion, COUNT(*) AS n
             FROM consulta_diagnosticos d JOIN consultas c ON c.id = d.consulta_id
             JOIN admisiones a ON a.id = c.admision_id
             WHERE d.tipo = 'Principal' AND {$f['where']}
             GROUP BY d.codigo ORDER BY n DESC LIMIT 10",
            $f['params']
        );

        $pg = paginar($total, 25);
        $rows = $this->filas($f, $pg['limit'], $pg['offset']);

        $this->view('reportes/index', [
            'title' => 'Informes', 'f' => $f, 'total' => $total, 'pacientes' => $pacientes,
            'porMedico' => $agrupar("CONCAT(u.nombres, ' ', u.apellidos)", 'medico'),
            'porSexo' => $porSexo,
            'porRegimen' => $agrupar('a.regimen', 'regimen'),
            'porEps' => $agrupar('e.nombre', 'eps'),
            'porTipo' => $agrupar('a.tipo_consulta', 'tipo'),
            'topDx' => $topDx, 'rows' => $rows, 'pg' => $pg,
            'medicos' => medicos_lista(), 'epsLista' => eps_lista(),
        ]);
    }

    public function excel(): void
    {
        $f = $this->filtros();
        $rows = $this->filas($f);
        Audit::log('reporte_exportado', null, null, 'Excel/CSV ' . $f['desde'] . ' a ' . $f['hasta'] . ' (' . count($rows) . ' filas)');

        $nombre = 'atenciones-' . $f['desde'] . '-a-' . $f['hasta'] . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para Excel
        $limpiar = static function ($v): string {
            $v = (string) $v;
            return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; // evita fórmulas en Excel
        };
        fputcsv($out, ['Fecha', 'Hora', 'Tipo doc', 'Documento', 'Primer apellido', 'Segundo apellido', 'Primer nombre', 'Segundo nombre',
            'Fecha nacimiento', 'Edad', 'Sexo', 'Municipio', 'Zona', 'EPS', 'Regimen', 'Tipo consulta', 'Modalidad', 'Finalidad',
            'Causa externa', 'CIE-10', 'Diagnostico principal', 'Diagnosticos relacionados', 'Medicamentos', 'Dias incapacidad',
            'Autorizacion', 'Copago', 'Medico', 'Registro medico'], ';');
        foreach ($rows as $r) {
            fputcsv($out, array_map($limpiar, [
                date('d/m/Y', strtotime($r['cerrada_en'])), date('H:i', strtotime($r['cerrada_en'])),
                $r['tipo_documento'], $r['numero_documento'], $r['primer_apellido'], $r['segundo_apellido'],
                $r['primer_nombre'], $r['segundo_nombre'], date('d/m/Y', strtotime($r['fecha_nacimiento'])), $r['edad_anios'],
                $r['sexo'], $r['municipio'], $r['zona'], $r['eps'], $r['regimen'], $r['tipo_consulta'], $r['modalidad'],
                $r['finalidad'], $r['causa_externa'], $r['dx_codigo'], $r['dx_descripcion'], $r['dx_relacionados'],
                $r['medicamentos'], $r['incapacidad_dias'], $r['numero_autorizacion'], $r['valor_copago'],
                $r['med_nombres'] . ' ' . $r['med_apellidos'], $r['registro_medico'],
            ]), ';');
        }
        fclose($out);
        exit;
    }

    public function pdf(): void
    {
        $f = $this->filtros();
        $rows = $this->filas($f, 1500);
        $total = (int) DB::value('SELECT COUNT(*) ' . self::FROM . " WHERE {$f['where']}", $f['params']);
        Audit::log('reporte_pdf', null, null, $f['desde'] . ' a ' . $f['hasta']);

        $medicoNombre = ctype_digit($f['medico']) ? (medicos_lista()[(int) $f['medico']] ?? '') : 'Todos';
        Pdf::render('atenciones', [
            'rows' => $rows, 'f' => $f, 'total' => $total, 'medicoNombre' => $medicoNombre,
            'epsNombre' => ctype_digit($f['eps']) ? (eps_lista()[(int) $f['eps']] ?? '') : 'Todas',
            'landscape' => true,
        ], 'informe-atenciones-' . $f['desde'] . '.pdf', 'landscape');
    }
}
