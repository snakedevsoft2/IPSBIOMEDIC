<?php
declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        $u = user();
        $mes = input('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            $mes = date('Y-m');
        }
        $ini = $mes . '-01 00:00:00';
        $fin = date('Y-m-d 00:00:00', strtotime($mes . '-01 +1 month'));
        $rango = [$ini, $fin];

        $esMedico = $u['rol'] === 'medico';
        $fm = $esMedico ? ' AND c.medico_id = ' . (int) $u['id'] : '';
        $cerradasMes = "c.estado = 'cerrada' AND c.cerrada_en >= ? AND c.cerrada_en < ?{$fm}";

        $kpi = [
            'hoy'           => (int) DB::value("SELECT COUNT(*) FROM consultas c WHERE c.estado = 'cerrada' AND c.cerrada_en >= CURDATE(){$fm}"),
            'espera'        => notificaciones_espera()[0],
            'en_atencion'   => (int) DB::value("SELECT COUNT(*) FROM admisiones a WHERE a.estado = 'en_atencion'" . ($esMedico ? ' AND a.medico_id = ' . (int) $u['id'] : '')),
            'mes'           => (int) DB::value("SELECT COUNT(*) FROM consultas c WHERE {$cerradasMes}", $rango),
            'formulas'      => (int) DB::value("SELECT COUNT(*) FROM formulas f JOIN consultas c ON c.id = f.consulta_id WHERE {$cerradasMes}", $rango),
            'pacientes'     => (int) DB::value('SELECT COUNT(*) FROM pacientes'),
            'pacientes_mes' => (int) DB::value('SELECT COUNT(*) FROM pacientes WHERE creado_en >= ? AND creado_en < ?', $rango),
        ];

        // Atenciones por día del mes
        $porDia = array_column(
            DB::all("SELECT DAY(c.cerrada_en) AS d, COUNT(*) AS n FROM consultas c WHERE {$cerradasMes} GROUP BY DAY(c.cerrada_en)", $rango),
            'n',
            'd'
        );
        $diasMes = (int) date('t', strtotime($ini));
        $dias = [];
        $serie = [];
        for ($d = 1; $d <= $diasMes; $d++) {
            $dias[] = (string) $d;
            $serie[] = (int) ($porDia[$d] ?? 0);
        }

        $topDx = DB::all(
            "SELECT d.codigo, MAX(d.descripcion) AS descripcion, COUNT(*) AS n
             FROM consulta_diagnosticos d JOIN consultas c ON c.id = d.consulta_id
             WHERE d.tipo = 'Principal' AND {$cerradasMes}
             GROUP BY d.codigo ORDER BY n DESC LIMIT 8",
            $rango
        );

        $porMedico = $esMedico ? [] : array_map(
            static fn ($r) => [$r['nombres'] . ' ' . $r['apellidos'], (int) $r['n']],
            DB::all("SELECT u.nombres, u.apellidos, COUNT(*) n FROM consultas c JOIN usuarios u ON u.id = c.medico_id WHERE {$cerradasMes} GROUP BY u.id, u.nombres, u.apellidos ORDER BY n DESC", $rango)
        );

        $paramsHoy = $esMedico ? [$u['id']] : [];
        $hoy = DB::all(
            "SELECT a.id, a.fecha_hora, a.estado, a.prioridad, a.tipo_consulta, a.medico_id,
                    p.id AS paciente_id, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                    p.tipo_documento, p.numero_documento, p.fecha_nacimiento,
                    u.nombres AS med_nombres, u.apellidos AS med_apellidos, c.id AS consulta_id
             FROM admisiones a
             JOIN pacientes p ON p.id = a.paciente_id
             LEFT JOIN usuarios u ON u.id = a.medico_id
             LEFT JOIN consultas c ON c.admision_id = a.id
             WHERE a.fecha_hora >= CURDATE() AND a.estado IN ('en_espera', 'en_atencion')"
            . ($esMedico ? ' AND (a.medico_id = ? OR a.medico_id IS NULL)' : '') .
            " ORDER BY (a.estado = 'en_atencion') DESC, (a.prioridad = 'Prioritaria') DESC, a.fecha_hora LIMIT 8",
            $paramsHoy
        );

        $meses = [];
        for ($i = 0; $i < 12; $i++) {
            $t = strtotime(date('Y-m-01') . " -{$i} month");
            $meses[date('Y-m', $t)] = nombre_mes((int) date('n', $t)) . ' ' . date('Y', $t);
        }

        $this->view('dashboard/index', [
            'title'      => 'Inicio',
            'u'          => $u,
            'mes'        => $mes,
            'meses'      => $meses,
            'nombreMes'  => nombre_mes((int) substr($mes, 5, 2)),
            'kpi'        => $kpi,
            'dias'       => $dias,
            'serie'      => $serie,
            'topDx'      => $topDx,
            'porMedico'  => $porMedico,
            'hoy'        => $hoy,
        ]);
    }
}
