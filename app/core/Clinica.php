<?php
declare(strict_types=1);

/** Carga de la información clínica compartida por la vista web y los PDF. */
final class Clinica
{
    public const ANTECEDENTES = [
        'patologicos'        => 'Patológicos',
        'quirurgicos'        => 'Quirúrgicos',
        'farmacologicos'     => 'Farmacológicos',
        'alergicos'          => 'Alérgicos',
        'traumaticos'        => 'Traumáticos',
        'toxicos'            => 'Tóxicos / hábitos',
        'gineco_obstetricos' => 'Gineco-obstétricos',
        'familiares'         => 'Familiares',
        'inmunologicos'      => 'Inmunológicos / vacunación',
        'otros'              => 'Otros',
    ];

    public const EXAMEN = [
        'estado_general'       => 'Estado general',
        'cabeza_cuello'        => 'Cabeza y cuello',
        'torax_cardiopulmonar' => 'Tórax y cardiopulmonar',
        'abdomen'              => 'Abdomen',
        'genitourinario'       => 'Genitourinario',
        'extremidades'         => 'Extremidades',
        'piel_faneras'         => 'Piel y faneras',
        'neurologico'          => 'Neurológico',
        'examen_otros'         => 'Otros hallazgos',
    ];

    public static function consulta(int $id): ?array
    {
        $c = DB::row(
            "SELECT c.*, a.fecha_hora AS admision_fecha, a.tipo_consulta, a.modalidad, a.finalidad, a.causa_externa,
                    a.numero_autorizacion, a.valor_copago, a.regimen AS adm_regimen, a.motivo AS adm_motivo, a.prioridad,
                    ea.nombre AS adm_eps, u.nombres AS med_nombres, u.apellidos AS med_apellidos,
                    u.tipo_documento AS med_tipo_doc, u.documento AS med_documento, u.registro_medico,
                    u.especialidad, u.firma AS med_firma
             FROM consultas c
             JOIN admisiones a ON a.id = c.admision_id
             LEFT JOIN eps ea ON ea.id = a.eps_id
             JOIN usuarios u ON u.id = c.medico_id
             WHERE c.id = ?",
            [$id]
        );
        if (!$c) {
            return null;
        }

        $p = DB::row('SELECT p.*, e.nombre AS eps_nombre FROM pacientes p LEFT JOIN eps e ON e.id = p.eps_id WHERE p.id = ?', [$c['paciente_id']]);

        // Una historia finalizada muestra los antecedentes tal como estaban al cerrarla.
        if ($c['estado'] === 'cerrada' && $c['antecedentes_snapshot']) {
            $ant = json_decode($c['antecedentes_snapshot'], true) ?: [];
        } else {
            $ant = DB::row('SELECT * FROM antecedentes WHERE paciente_id = ?', [$c['paciente_id']]) ?? [];
        }

        $dx = DB::all('SELECT * FROM consulta_diagnosticos WHERE consulta_id = ? ORDER BY orden, id', [$id]);
        $formula = DB::row('SELECT * FROM formulas WHERE consulta_id = ?', [$id]);
        $items = $formula ? DB::all('SELECT * FROM formula_items WHERE formula_id = ? ORDER BY orden, id', [$formula['id']]) : [];
        $ordenes = DB::all('SELECT * FROM ordenes WHERE consulta_id = ? ORDER BY orden, id', [$id]);
        $notas = DB::all(
            'SELECT n.*, u.nombres, u.apellidos FROM notas_aclaratorias n JOIN usuarios u ON u.id = n.usuario_id
             WHERE n.consulta_id = ? ORDER BY n.creado_en',
            [$id]
        );

        return compact('c', 'p', 'ant', 'dx', 'formula', 'items', 'ordenes', 'notas');
    }
}
